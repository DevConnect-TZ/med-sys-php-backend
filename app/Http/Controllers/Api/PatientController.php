<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends Controller
{
    /**
     * Get all patients
     * Required roles: admin, receptionist, doctor, nurse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = Patient::query();

        // Search by name or patient number
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('patient_number', 'like', "%$search%")
                    ->orWhere('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        // Default ordering by created_at descending (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->input('per_page', 15);
        $patients = $query->paginate($perPage);

        return PatientResource::collection($patients);
    }

    /**
     * Get patient by ID
     */
    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'success' => true,
            'patient' => new PatientResource($patient)
        ], 200);
    }

    /**
     * Register new patient
     * Required roles: admin, receptionist
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|array',
            'medical_history' => 'nullable|string',
        ]);

        // Generate patient number
        $lastPatient = Patient::latest('id')->first();
        $patientNumber = 'P' . str_pad(($lastPatient?->id ?? 0) + 1, 6, '0', STR_PAD_LEFT);

        $patient = Patient::create([
            'patient_number' => $patientNumber,
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patient registered successfully',
            'patient' => new PatientResource($patient)
        ], 201);
    }

    /**
     * Update patient information
     * Required roles: admin, receptionist
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email',
            'address' => 'sometimes|string',
            'emergency_contact_name' => 'sometimes|string|max:255',
            'emergency_contact_phone' => 'sometimes|string|max:20',
            'emergency_contact_relationship' => 'sometimes|string|max:100',
            'blood_group' => 'sometimes|string|max:10',
            'allergies' => 'sometimes|array',
            'medical_history' => 'sometimes|string',
        ]);

        $patient->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Patient updated successfully',
            'patient' => new PatientResource($patient)
        ], 200);
    }

    /**
     * Delete patient (soft delete not implemented, actual deletion)
     * Required roles: admin
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $patientName = $patient->full_name;
        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => "Patient '{$patientName}' deleted successfully"
        ], 200);
    }

    /**
     * Get patient visit history
     * Required roles: admin, doctor, nurse
     */
    public function getVisits(Patient $patient): JsonResponse
    {
        $visits = $patient->visits()->with('doctor')->orderBy('visit_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'visits' => $visits->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'visit_date' => $visit->visit_date,
                    'doctor_name' => $visit->doctor->name,
                    'diagnosis' => $visit->diagnosis,
                    'consultation_fee' => $visit->consultation_fee,
                ];
            })
        ], 200);
    }
}
