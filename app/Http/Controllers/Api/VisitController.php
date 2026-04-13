<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VisitController extends Controller
{
    /**
     * Get all visits with optional filters
     * Required roles: admin, doctor, nurse
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Visit::query();

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->input('doctor_id'));
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('visit_date', $request->input('date'));
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $visits = $query->orderBy('visit_date', 'desc')->paginate($perPage);

        return VisitResource::collection($visits);
    }

    /**
     * Get visit by ID
     */
    public function show(Visit $visit): JsonResponse
    {
        return response()->json([
            'success' => true,
            'visit' => new VisitResource($visit)
        ], 200);
    }

    /**
     * Create visit record (EMR)
     * Required roles: admin, doctor
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'visit_date' => 'required|date',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medical_notes' => 'nullable|string',
            'vital_signs' => 'nullable|json',
            'consultation_fee' => 'nullable|numeric|min:0',
        ]);

        // Get patient and doctor names
        $patient = Patient::findOrFail($validated['patient_id']);
        $doctor = User::findOrFail($validated['doctor_id']);

        // Check if doctor is actually a doctor
        if (!$doctor->hasRole('doctor')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a doctor'
            ], 422);
        }

        $visit = Visit::create([
            'patient_id' => $validated['patient_id'],
            'patient_name' => $patient->full_name,
            'doctor_id' => $validated['doctor_id'],
            'doctor_name' => $doctor->name,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'visit_date' => $validated['visit_date'],
            'chief_complaint' => $validated['chief_complaint'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'medical_notes' => $validated['medical_notes'] ?? null,
            'vital_signs' => $validated['vital_signs'] ?? null,
            'consultation_fee' => $validated['consultation_fee'] ?? 0.00,
            'status' => 'completed',
        ]);

        // Update appointment workflow status only if it's still scheduled
        if (($validated['appointment_id'] ?? false) && $visit->appointment->workflow_status === 'scheduled') {
            $visit->appointment->update(['workflow_status' => 'awaiting_payment']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Visit record created successfully',
            'visit' => new VisitResource($visit)
        ], 201);
    }

    /**
     * Update visit record
     * Required roles: admin, doctor
     */
    public function update(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'chief_complaint' => 'sometimes|string',
            'diagnosis' => 'sometimes|string',
            'medical_notes' => 'sometimes|string',
            'vital_signs' => 'sometimes|json',
            'consultation_fee' => 'sometimes|numeric|min:0',
        ]);

        $visit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Visit updated successfully',
            'visit' => new VisitResource($visit)
        ], 200);
    }
}
