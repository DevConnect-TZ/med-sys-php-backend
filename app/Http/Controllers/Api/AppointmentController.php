<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Get all appointments with optional filters and role-based queue
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Appointment::query();

        // Role-based queue filtering
        if ($request->boolean('my_queue')) {
            $user = auth()->user();
            $role = $user->role;

            switch ($role) {
                case 'doctor':
                    $query->where(function ($q) use ($user) {
                        $q->where('workflow_status', 'scheduled')
                          ->where('doctor_id', $user->id)
                          ->orWhere('workflow_status', 'lab_completed')
                          ->where('doctor_id', $user->id);
                    });
                    break;
                case 'cashier':
                    $query->where('workflow_status', 'awaiting_payment');
                    break;
                case 'lab_technician':
                    $query->where('workflow_status', 'paid');
                    break;
                case 'pharmacist':
                    $query->where('workflow_status', 'pharmacy_pending');
                    break;
                case 'receptionist':
                    // Receptionists see scheduled + cancelled (their domain)
                    $query->whereIn('workflow_status', ['scheduled', 'cancelled']);
                    break;
                case 'admin':
                    // Admin sees all
                    break;
                default:
                    $query->where('workflow_status', 'scheduled');
            }
        }

        if ($request->has('workflow_status')) {
            $query->where('workflow_status', $request->input('workflow_status'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        if ($request->has('date')) {
            $query->whereDate('appointment_date', $request->input('date'));
        }

        $perPage = $request->input('per_page', 15);
        $appointments = $query->orderBy('appointment_date', 'asc')->paginate($perPage);

        return AppointmentResource::collection($appointments);
    }

    /**
     * Get appointment by ID with related workflow data
     */
    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load(['visit', 'labOrders.labResult', 'prescriptions', 'invoices']);

        return response()->json([
            'success' => true,
            'appointment' => new AppointmentResource($appointment)
        ], 200);
    }

    /**
     * Create new appointment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        $doctor = User::findOrFail($validated['doctor_id']);

        if (!$doctor->hasRole('doctor')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a doctor'
            ], 422);
        }

        // Check doctor schedule availability
        $availability = $this->checkDoctorAvailability(
            $validated['doctor_id'],
            $validated['appointment_date'],
            $validated['appointment_time']
        );

        if (!$availability['available']) {
            return response()->json([
                'success' => false,
                'message' => $availability['message']
            ], 422);
        }

        $appointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'patient_name' => $patient->full_name,
            'doctor_id' => $validated['doctor_id'],
            'doctor_name' => $doctor->name,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'scheduled',
            'workflow_status' => 'scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->emailService->sendAppointmentScheduled($appointment);

        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully. Confirmation email sent to patient.',
            'appointment' => new AppointmentResource($appointment)
        ], 201);
    }

    /**
     * Update appointment basics (date/time/reason/notes)
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => 'sometimes|date|after_or_equal:today',
            'appointment_time' => 'sometimes|date_format:H:i',
            'reason' => 'sometimes|string',
            'notes' => 'sometimes|string',
        ]);

        // Check doctor schedule availability if date or time changed
        if (isset($validated['appointment_date']) || isset($validated['appointment_time'])) {
            $date = $validated['appointment_date'] ?? $appointment->appointment_date->format('Y-m-d');
            $time = $validated['appointment_time'] ?? $appointment->appointment_time->format('H:i');

            $availability = $this->checkDoctorAvailability(
                $appointment->doctor_id,
                $date,
                $time,
                $appointment->id
            );

            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $availability['message']
                ], 422);
            }
        }

        $appointment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully',
            'appointment' => new AppointmentResource($appointment)
        ], 200);
    }

    /**
     * Cancel appointment
     */
    public function cancel(Appointment $appointment): JsonResponse
    {
        if ($appointment->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Appointment is already cancelled'
            ], 422);
        }

        $appointment->update(['status' => 'cancelled', 'workflow_status' => 'cancelled']);

        $this->emailService->sendAppointmentCancelled($appointment);

        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully.'
        ], 200);
    }

    /**
     * Doctor reviews appointment, creates visit + lab orders, forwards to cashier
     */
    public function doctorReview(Request $request, Appointment $appointment): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('doctor') || $appointment->doctor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($appointment->workflow_status, ['scheduled'])) {
            return response()->json(['success' => false, 'message' => 'Appointment cannot be reviewed at this stage'], 422);
        }

        $validated = $request->validate([
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medical_notes' => 'nullable|string',
            'vital_signs' => 'nullable|array',
            'consultation_fee' => 'nullable|numeric|min:0',
            'lab_tests' => 'nullable|array',
            'lab_tests.*.test_name' => 'required_with:lab_tests|string',
            'lab_tests.*.test_type' => 'nullable|string',
            'lab_tests.*.cost' => 'nullable|numeric|min:0',
            'lab_tests.*.priority' => 'nullable|in:normal,urgent',
            'lab_tests.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($appointment, $validated, $user) {
            // Create visit
            $visit = Visit::create([
                'patient_id' => $appointment->patient_id,
                'patient_name' => $appointment->patient_name,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor_name,
                'appointment_id' => $appointment->id,
                'visit_date' => now()->toDateString(),
                'chief_complaint' => $validated['chief_complaint'] ?? null,
                'diagnosis' => $validated['diagnosis'] ?? null,
                'medical_notes' => $validated['medical_notes'] ?? null,
                'vital_signs' => $validated['vital_signs'] ?? null,
                'consultation_fee' => $validated['consultation_fee'] ?? 0.00,
                'status' => 'completed',
            ]);

            // Create lab orders
            if (!empty($validated['lab_tests'])) {
                foreach ($validated['lab_tests'] as $test) {
                    LabOrder::create([
                        'patient_id' => $appointment->patient_id,
                        'patient_name' => $appointment->patient_name,
                        'doctor_id' => $appointment->doctor_id,
                        'doctor_name' => $appointment->doctor_name,
                        'visit_id' => $visit->id,
                        'appointment_id' => $appointment->id,
                        'test_name' => $test['test_name'],
                        'test_type' => $test['test_type'] ?? null,
                        'priority' => $test['priority'] ?? 'normal',
                        'notes' => $test['notes'] ?? null,
                        'order_date' => now()->toDateString(),
                        'cost' => $test['cost'] ?? 0.00,
                        'status' => 'pending',
                    ]);
                }
            }

            $appointment->update(['workflow_status' => 'awaiting_payment']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Appointment reviewed and forwarded to cashier',
            'appointment' => new AppointmentResource($appointment->fresh())
        ], 200);
    }

    /**
     * Cashier marks appointment as paid (after invoice payment)
     */
    public function markPaid(Appointment $appointment): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('cashier')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($appointment->workflow_status !== 'awaiting_payment') {
            return response()->json(['success' => false, 'message' => 'Appointment is not awaiting payment'], 422);
        }

        $appointment->update(['workflow_status' => 'paid']);

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed. Appointment forwarded to lab.',
            'appointment' => new AppointmentResource($appointment)
        ], 200);
    }

    /**
     * Doctor prescribes medicines after lab results, forwards to pharmacy
     */
    public function prescribe(Request $request, Appointment $appointment): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('doctor') || $appointment->doctor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($appointment->workflow_status !== 'lab_completed') {
            return response()->json(['success' => false, 'message' => 'Lab results are not ready for prescription'], 422);
        }

        $validated = $request->validate([
            'medications' => 'required|array',
            'medications.*.name' => 'required|string',
            'medications.*.dosage' => 'required|string',
            'medications.*.frequency' => 'required|string',
            'medications.*.duration' => 'required|string',
            'medications.*.quantity' => 'required|numeric',
            'medications.*.instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $visit = $appointment->visit;
        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'No visit record found for this appointment'], 422);
        }

        Prescription::create([
            'patient_id' => $appointment->patient_id,
            'patient_name' => $appointment->patient_name,
            'doctor_id' => $appointment->doctor_id,
            'doctor_name' => $appointment->doctor_name,
            'visit_id' => $visit->id,
            'appointment_id' => $appointment->id,
            'medications' => $validated['medications'],
            'status' => 'pending',
            'prescription_date' => now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $appointment->update(['workflow_status' => 'pharmacy_pending']);

        return response()->json([
            'success' => true,
            'message' => 'Prescription created and forwarded to pharmacy',
            'appointment' => new AppointmentResource($appointment)
        ], 200);
    }

    /**
     * Pharmacist dispenses prescription, completes appointment
     */
    public function dispense(Appointment $appointment): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('pharmacist')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($appointment->workflow_status !== 'pharmacy_pending') {
            return response()->json(['success' => false, 'message' => 'Appointment is not pending pharmacy dispense'], 422);
        }

        $prescription = $appointment->prescriptions()->where('status', 'pending')->latest()->first();
        if ($prescription) {
            $prescription->update(['status' => 'dispensed']);
        }

        $appointment->update(['workflow_status' => 'completed', 'status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'Medicines dispensed. Appointment completed.',
            'appointment' => new AppointmentResource($appointment)
        ], 200);
    }

    /**
     * Check if a doctor is available at the given date and time
     */
    private function checkDoctorAvailability(int $doctorId, string $date, string $time, ?int $excludeAppointmentId = null): array
    {
        $dayOfWeek = (int) \Carbon\Carbon::parse($date)->format('w');

        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return [
                'available' => false,
                'message' => 'Doctor does not have a schedule for this day',
            ];
        }

        $start = $schedule->start_time->format('H:i');
        $end = $schedule->end_time->format('H:i');

        if ($time < $start || $time > $end) {
            return [
                'available' => false,
                'message' => "Doctor is only available from {$start} to {$end} on {$schedule->day_name}",
            ];
        }

        $conflictQuery = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', ['scheduled', 'confirmed']);

        if ($excludeAppointmentId) {
            $conflictQuery->where('id', '!=', $excludeAppointmentId);
        }

        if ($conflictQuery->exists()) {
            return [
                'available' => false,
                'message' => 'Doctor already has an appointment at this time',
            ];
        }

        return [
            'available' => true,
            'message' => 'Doctor is available',
        ];
    }
}
