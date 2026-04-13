<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabOrderResource;
use App\Http\Resources\LabResultResource;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Get all lab orders
     * Required roles: admin, doctor, lab_technician
     */
    public function indexOrders(Request $request): AnonymousResourceCollection
    {
        $query = LabOrder::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->input('appointment_id'));
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->orderBy('order_date', 'desc')->paginate($perPage);

        return LabOrderResource::collection($orders);
    }

    /**
     * Get lab order by ID
     */
    public function showOrder(LabOrder $labOrder): JsonResponse
    {
        return response()->json([
            'success' => true,
            'lab_order' => new LabOrderResource($labOrder)
        ], 200);
    }

    /**
     * Create lab order
     * Required roles: admin, doctor
     */
    public function storeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'visit_id' => 'nullable|exists:visits,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'test_name' => 'required|string|max:255',
            'test_type' => 'nullable|string|max:100',
            'priority' => 'sometimes|in:normal,urgent',
            'notes' => 'nullable|string',
            'order_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        $doctor = User::findOrFail($validated['doctor_id']);

        if (!$doctor->hasRole('doctor')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a doctor'
            ], 422);
        }

        $labOrder = LabOrder::create([
            'patient_id' => $validated['patient_id'],
            'patient_name' => $patient->full_name,
            'doctor_id' => $validated['doctor_id'],
            'doctor_name' => $doctor->name,
            'visit_id' => $validated['visit_id'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'test_name' => $validated['test_name'],
            'test_type' => $validated['test_type'] ?? null,
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            'order_date' => $validated['order_date'],
            'cost' => $validated['cost'] ?? 0.00,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lab order created successfully',
            'lab_order' => new LabOrderResource($labOrder)
        ], 201);
    }

    /**
     * Update lab order
     * Required roles: admin, doctor, lab_technician
     */
    public function updateOrder(Request $request, LabOrder $labOrder): JsonResponse
    {
        $validated = $request->validate([
            'test_name' => 'sometimes|string|max:255',
            'test_type' => 'sometimes|string|max:100',
            'priority' => 'sometimes|in:normal,urgent',
            'status' => 'sometimes|in:pending,completed,cancelled',
            'notes' => 'sometimes|string',
            'cost' => 'sometimes|numeric|min:0',
        ]);

        $labOrder->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lab order updated successfully',
            'lab_order' => new LabOrderResource($labOrder)
        ], 200);
    }

    /**
     * Upload lab result
     * Required roles: admin, lab_technician
     */
    public function storeResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lab_order_id' => 'required|exists:lab_orders,id',
            'results' => 'nullable|string',
            'result_file_url' => 'nullable|string|url',
            'result_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $labOrder = LabOrder::findOrFail($validated['lab_order_id']);
        $technician = auth()->user();

        $labResult = LabResult::create([
            'lab_order_id' => $validated['lab_order_id'],
            'patient_id' => $labOrder->patient_id,
            'patient_name' => $labOrder->patient_name,
            'test_name' => $labOrder->test_name,
            'results' => $validated['results'] ?? null,
            'result_file_url' => $validated['result_file_url'] ?? null,
            'technician_id' => $technician->id,
            'technician_name' => $technician->name,
            'result_date' => $validated['result_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update lab order status
        $labOrder->update(['status' => 'completed']);

        // Auto-advance appointment workflow if all lab orders are completed
        if ($labOrder->appointment_id) {
            $pendingCount = LabOrder::where('appointment_id', $labOrder->appointment_id)
                ->where('status', '!=', 'completed')
                ->count();
            if ($pendingCount === 0) {
                $labOrder->appointment->update(['workflow_status' => 'lab_completed']);
            }
        }

        // Send lab result ready email
        $this->emailService->sendLabResultReady($labResult);

        return response()->json([
            'success' => true,
            'message' => 'Lab result uploaded successfully. Result notification sent to patient.',
            'lab_result' => new LabResultResource($labResult)
        ], 201);
    }

    /**
     * Get lab results for an order
     * Required roles: admin, doctor, lab_technician
     */
    public function showResult(LabOrder $labOrder): JsonResponse
    {
        $result = $labOrder->labResult;

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No results found for this lab order'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'lab_result' => new LabResultResource($result)
        ], 200);
    }
}
