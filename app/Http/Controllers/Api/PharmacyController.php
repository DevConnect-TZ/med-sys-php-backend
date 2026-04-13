<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PharmacyInventoryResource;
use App\Http\Resources\PrescriptionResource;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PharmacyInventory;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PharmacyController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Get all prescriptions
     * Required roles: admin, doctor, pharmacist
     */
    public function indexPrescriptions(Request $request): AnonymousResourceCollection
    {
        $query = Prescription::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $prescriptions = $query->orderBy('prescription_date', 'desc')->paginate($perPage);

        return PrescriptionResource::collection($prescriptions);
    }

    /**
     * Get prescription by ID
     */
    public function showPrescription(Prescription $prescription): JsonResponse
    {
        return response()->json([
            'success' => true,
            'prescription' => new PrescriptionResource($prescription)
        ], 200);
    }

    /**
     * Create prescription
     * Required roles: admin, doctor
     */
    public function storePrescription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'visit_id' => 'nullable|exists:visits,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medications' => 'required|json',
            'prescription_date' => 'required|date',
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

        $prescription = Prescription::create([
            'patient_id' => $validated['patient_id'],
            'patient_name' => $patient->full_name,
            'doctor_id' => $validated['doctor_id'],
            'doctor_name' => $doctor->name,
            'visit_id' => $validated['visit_id'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'medications' => $validated['medications'],
            'status' => 'pending',
            'prescription_date' => $validated['prescription_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prescription created successfully',
            'prescription' => new PrescriptionResource($prescription)
        ], 201);
    }

    /**
     * Update prescription (e.g., mark as dispensed)
     * Required roles: admin, doctor, pharmacist
     */
    public function updatePrescription(Request $request, Prescription $prescription): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,dispensed,cancelled',
            'notes' => 'sometimes|string',
        ]);

        $oldStatus = $prescription->status;
        $prescription->update($validated);

        // Send prescription ready email when dispensed
        if ($oldStatus !== 'dispensed' && $prescription->status === 'dispensed') {
            $this->emailService->sendPrescriptionReady($prescription);

            // Auto-complete appointment workflow
            if ($prescription->appointment_id && $prescription->appointment->workflow_status === 'pharmacy_pending') {
                $prescription->appointment->update(['workflow_status' => 'completed', 'status' => 'completed']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $prescription->status === 'dispensed' 
                ? 'Prescription marked as dispensed. Notification sent to patient.' 
                : 'Prescription updated successfully',
            'prescription' => new PrescriptionResource($prescription)
        ], 200);
    }

    /**
     * Get pharmacy inventory
     * Required roles: admin, pharmacist
     */
    public function indexInventory(Request $request): AnonymousResourceCollection
    {
        $query = PharmacyInventory::query();

        // Search by medication name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('medication_name', 'like', "%$search%")
                    ->orWhere('generic_name', 'like', "%$search%");
            });
        }

        // Filter by low stock
        if ($request->input('low_stock') === 'true') {
            $query->whereRaw('quantity <= reorder_level');
        }

        // Filter by expired
        if ($request->input('expired') === 'true') {
            $query->whereDate('expiry_date', '<', now());
        } elseif ($request->boolean('hide_expired', true)) {
            $query->where(function ($q) {
                $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now());
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 50);
        $inventory = $query->orderBy('medication_name')->paginate($perPage);

        return PharmacyInventoryResource::collection($inventory);
    }

    /**
     * Add inventory item
     * Required roles: admin
     */
    public function storeInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'form' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:100',
        ]);

        $item = PharmacyInventory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item added successfully',
            'inventory' => new PharmacyInventoryResource($item)
        ], 201);
    }

    /**
     * Update inventory item
     * Required roles: admin
     */
    public function updateInventory(Request $request, PharmacyInventory $inventory): JsonResponse
    {
        $validated = $request->validate([
            'medication_name' => 'sometimes|string|max:255',
            'generic_name' => 'sometimes|string|max:255',
            'dosage' => 'sometimes|string|max:100',
            'form' => 'sometimes|string|max:100',
            'manufacturer' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'expiry_date' => 'sometimes|date',
            'batch_number' => 'sometimes|string|max:100',
        ]);

        $inventory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory updated successfully',
            'inventory' => new PharmacyInventoryResource($inventory)
        ], 200);
    }
}
