<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    /**
     * List admissions and referrals
     */
    public function index(Request $request): JsonResponse
    {
        $query = Admission::with(['patient:id,first_name,last_name,patient_id,patient_number', 'doctor:id,name']);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        $perPage = $request->input('per_page', 15);
        $admissions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $admissions->map(function ($admission) {
                return [
                    'id' => $admission->id,
                    'patient_id' => $admission->patient_id,
                    'patient_name' => $admission->patient?->full_name,
                    'patient_identifier' => $admission->patient?->patient_id ?: $admission->patient?->patient_number,
                    'doctor_id' => $admission->doctor_id,
                    'doctor_name' => $admission->doctor?->name,
                    'visit_id' => $admission->visit_id,
                    'appointment_id' => $admission->appointment_id,
                    'type' => $admission->type,
                    'status' => $admission->status,
                    'location' => $admission->location,
                    'notes' => $admission->notes,
                    'discharged_at' => $admission->discharged_at,
                    'created_at' => $admission->created_at,
                    'updated_at' => $admission->updated_at,
                ];
            }),
            'meta' => [
                'current_page' => $admissions->currentPage(),
                'last_page' => $admissions->lastPage(),
                'per_page' => $admissions->perPage(),
                'total' => $admissions->total(),
            ],
        ]);
    }

    /**
     * Create admission or referral
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'visit_id' => 'nullable|exists:visits,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'bed_id' => 'nullable|exists:beds,id',
            'type' => 'required|in:admission,referral',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $doctor = User::findOrFail($validated['doctor_id']);
        if (!$doctor->hasRole('doctor')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a doctor',
            ], 422);
        }

        $patient = Patient::findOrFail($validated['patient_id']);

        // Validate bed availability for admissions
        if (!empty($validated['bed_id']) && $validated['type'] === 'admission') {
            $bed = Bed::findOrFail($validated['bed_id']);
            if ($bed->is_occupied) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected bed is already occupied',
                ], 422);
            }
            $bed->update(['is_occupied' => true]);
        }

        $admission = Admission::create([
            ...$validated,
            'status' => 'active',
        ]);

        // Optionally update visit/appointment status
        if (!empty($validated['visit_id'])) {
            Visit::where('id', $validated['visit_id'])->update(['status' => $validated['type'] === 'admission' ? 'admitted' : 'referred']);
        }
        if (!empty($validated['appointment_id'])) {
            Appointment::where('id', $validated['appointment_id'])->update(['status' => $validated['type'] === 'admission' ? 'admitted' : 'referred']);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['type'] === 'admission' ? 'Patient admitted successfully' : 'Patient referred successfully',
            'data' => [
                'id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'patient_name' => $patient->full_name,
                'doctor_id' => $admission->doctor_id,
                'bed_id' => $admission->bed_id,
                'bed_name' => $admission->bed ? ($admission->bed->ward?->name . ' - Bed ' . $admission->bed->bed_number) : null,
                'type' => $admission->type,
                'status' => $admission->status,
                'location' => $admission->location,
                'notes' => $admission->notes,
                'created_at' => $admission->created_at,
            ],
        ], 201);
    }

    /**
     * Discharge a patient (for admissions)
     */
    public function discharge(Admission $admission): JsonResponse
    {
        if ($admission->type !== 'admission') {
            return response()->json([
                'success' => false,
                'message' => 'Only admissions can be discharged',
            ], 422);
        }

        if ($admission->status === 'discharged') {
            return response()->json([
                'success' => false,
                'message' => 'Patient is already discharged',
            ], 422);
        }

        if ($admission->bed_id) {
            Bed::where('id', $admission->bed_id)->update(['is_occupied' => false]);
        }

        $admission->update([
            'status' => 'discharged',
            'discharged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patient discharged successfully',
            'data' => [
                'id' => $admission->id,
                'status' => $admission->status,
                'discharged_at' => $admission->discharged_at,
            ],
        ]);
    }

    /**
     * Complete a referral
     */
    public function completeReferral(Admission $admission): JsonResponse
    {
        if ($admission->type !== 'referral') {
            return response()->json([
                'success' => false,
                'message' => 'Only referrals can be completed',
            ], 422);
        }

        if ($admission->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Referral is already completed',
            ], 422);
        }

        $admission->update([
            'status' => 'completed',
            'discharged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Referral completed successfully',
            'data' => [
                'id' => $admission->id,
                'status' => $admission->status,
                'discharged_at' => $admission->discharged_at,
            ],
        ]);
    }

    /**
     * Show single admission
     */
    public function show(Admission $admission): JsonResponse
    {
        $admission->load(['patient', 'doctor', 'visit', 'appointment']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admission->id,
                'patient_id' => $admission->patient_id,
                'patient_name' => $admission->patient?->full_name,
                'patient_identifier' => $admission->patient?->patient_id ?: $admission->patient?->patient_number,
                'doctor_id' => $admission->doctor_id,
                'doctor_name' => $admission->doctor?->name,
                'visit_id' => $admission->visit_id,
                'appointment_id' => $admission->appointment_id,
                'type' => $admission->type,
                'status' => $admission->status,
                'location' => $admission->location,
                'notes' => $admission->notes,
                'discharged_at' => $admission->discharged_at,
                'created_at' => $admission->created_at,
                'updated_at' => $admission->updated_at,
            ],
        ]);
    }
}
