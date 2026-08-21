<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class DoctorScheduleController extends Controller
{
    /**
     * List all doctor schedules
     */
    public function index(Request $request): JsonResponse
    {
        $query = DoctorSchedule::with('doctor:id,name');

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->input('day_of_week'));
        }

        $schedules = $query->orderBy('doctor_id')->orderBy('day_of_week')->get();

        return response()->json([
            'success' => true,
            'data' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'doctor_id' => $schedule->doctor_id,
                    'doctor_name' => $schedule->doctor?->name,
                    'day_of_week' => $schedule->day_of_week,
                    'day_name' => $schedule->day_name,
                    'start_time' => $schedule->start_time?->format('H:i'),
                    'end_time' => $schedule->end_time?->format('H:i'),
                    'is_active' => $schedule->is_active,
                    'created_at' => $schedule->created_at,
                    'updated_at' => $schedule->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Generate random schedules for one or more doctors within a working window.
     * Doctors that do not exist yet are created automatically.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'names' => 'required|string|max:5000',
            'from_time' => 'required|date_format:H:i',
            'to_time' => 'required|date_format:H:i|after:from_time',
            'days' => 'sometimes|integer|min:1|max:7',
        ]);

        $names = array_values(array_unique(array_filter(array_map('trim',
            preg_split('/[\r\n,]+/', $validated['names'])
        ))));

        if (empty($names)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one doctor name is required',
            ], 422);
        }

        $fromMinutes = ((int) substr($validated['from_time'], 0, 2)) * 60 + (int) substr($validated['from_time'], 3, 2);
        $toMinutes = ((int) substr($validated['to_time'], 0, 2)) * 60 + (int) substr($validated['to_time'], 3, 2);
        $windowMinutes = $toMinutes - $fromMinutes;

        if ($windowMinutes < 60) {
            return response()->json([
                'success' => false,
                'message' => 'Working window must be at least 1 hour',
            ], 422);
        }

        $results = [];

        foreach ($names as $name) {
            if (mb_strlen($name) > 255) {
                continue;
            }

            $doctor = User::where('role', 'doctor')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            $isNew = false;
            if (!$doctor) {
                $doctor = User::create([
                    'name' => $name,
                    'email' => $this->generateUniqueEmail($name),
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'doctor',
                    'is_active' => true,
                ]);
                $isNew = true;
            }

            DoctorSchedule::where('doctor_id', $doctor->id)->delete();

            $daysCount = $validated['days'] ?? random_int(3, 5);
            $allDays = [0, 1, 2, 3, 4, 5, 6];
            shuffle($allDays);
            $days = array_slice($allDays, 0, $daysCount);

            $minShift = 180;
            $maxShift = min(480, $windowMinutes);
            $created = [];

            foreach ($days as $day) {
                $shift = random_int($minShift, max($minShift, $maxShift));
                $latestStart = $windowMinutes - $shift;
                $startOffset = $latestStart > 0 ? random_int(0, $latestStart) : 0;

                $schedule = DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => sprintf('%02d:%02d', intdiv($fromMinutes + $startOffset, 60), ($fromMinutes + $startOffset) % 60),
                    'end_time' => sprintf('%02d:%02d', intdiv($fromMinutes + $startOffset + $shift, 60), ($fromMinutes + $startOffset + $shift) % 60),
                    'is_active' => true,
                ]);

                $created[] = [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'day_name' => $schedule->day_name,
                    'start_time' => $schedule->start_time?->format('H:i'),
                    'end_time' => $schedule->end_time?->format('H:i'),
                ];
            }

            $results[] = [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'is_new' => $isNew,
                'email' => $isNew ? $doctor->email : null,
                'schedules' => $created,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedules generated successfully',
            'data' => $results,
        ], 201);
    }

    /**
     * Generate a unique email for a new doctor based on their name
     */
    private function generateUniqueEmail(string $name): string
    {
        $slug = Str::slug($name, '.') ?: 'doctor';
        $email = $slug . '@hospital.com';
        $i = 1;

        while (User::where('email', $email)->exists()) {
            $email = $slug . $i . '@hospital.com';
            $i++;
        }

        return $email;
    }

    /**
     * Store a new schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'sometimes|boolean',
        ]);

        $doctor = User::findOrFail($validated['doctor_id']);
        if (!$doctor->hasRole('doctor')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a doctor',
            ], 422);
        }

        $existing = DoctorSchedule::where('doctor_id', $validated['doctor_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule already exists for this doctor on the selected day',
            ], 422);
        }

        $schedule = DoctorSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => [
                'id' => $schedule->id,
                'doctor_id' => $schedule->doctor_id,
                'doctor_name' => $schedule->doctor?->name,
                'day_of_week' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
                'is_active' => $schedule->is_active,
            ],
        ], 201);
    }

    /**
     * Show a single schedule
     */
    public function show(DoctorSchedule $schedule): JsonResponse
    {
        $schedule->load('doctor:id,name');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $schedule->id,
                'doctor_id' => $schedule->doctor_id,
                'doctor_name' => $schedule->doctor?->name,
                'day_of_week' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
                'is_active' => $schedule->is_active,
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at,
            ],
        ]);
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, DoctorSchedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['start_time'], $validated['end_time'])) {
            $start = \Carbon\Carbon::createFromFormat('H:i', $validated['start_time']);
            $end = \Carbon\Carbon::createFromFormat('H:i', $validated['end_time']);
            if ($end->lte($start)) {
                return response()->json([
                    'success' => false,
                    'message' => 'End time must be after start time',
                ], 422);
            }
        }

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => [
                'id' => $schedule->id,
                'doctor_id' => $schedule->doctor_id,
                'doctor_name' => $schedule->doctor?->name,
                'day_of_week' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
                'is_active' => $schedule->is_active,
            ],
        ]);
    }

    /**
     * Delete a schedule
     */
    public function destroy(DoctorSchedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }

    /**
     * Check doctor availability for a given date and time
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

        $doctor = User::findOrFail($validated['doctor_id']);
        if (!$doctor->hasRole('doctor')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a doctor',
            ], 422);
        }

        $date = \Carbon\Carbon::parse($validated['date']);
        $time = $validated['time'];
        $dayOfWeek = (int) $date->format('w');

        $schedule = DoctorSchedule::where('doctor_id', $validated['doctor_id'])
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => true,
                'available' => false,
                'message' => 'Doctor does not have a schedule for this day',
            ]);
        }

        $start = $schedule->start_time->format('H:i');
        $end = $schedule->end_time->format('H:i');

        if ($time < $start || $time > $end) {
            return response()->json([
                'success' => true,
                'available' => false,
                'message' => "Doctor is only available from {$start} to {$end} on {$schedule->day_name}",
                'schedule' => [
                    'start_time' => $start,
                    'end_time' => $end,
                    'day_name' => $schedule->day_name,
                ],
            ]);
        }

        // Check for conflicting appointments
        $conflict = \App\Models\Appointment::where('doctor_id', $validated['doctor_id'])
            ->where('appointment_date', $validated['date'])
            ->where('appointment_time', $time)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => true,
                'available' => false,
                'message' => 'Doctor already has an appointment at this time',
            ]);
        }

        return response()->json([
            'success' => true,
            'available' => true,
            'message' => 'Doctor is available at this time',
            'schedule' => [
                'start_time' => $start,
                'end_time' => $end,
                'day_name' => $schedule->day_name,
            ],
        ]);
    }
}
