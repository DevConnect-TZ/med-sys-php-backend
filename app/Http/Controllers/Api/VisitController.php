<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PharmacyInventory;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    /**
     * Get all visits with optional filters
     * Required roles: admin, doctor, nurse
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Visit::query();

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
                    $query->whereIn('workflow_status', ['awaiting_payment', 'pharmacy_awaiting_payment']);
                    break;
                case 'lab_technician':
                    $query->whereIn('workflow_status', ['paid', 'lab_pending']);
                    break;
                case 'pharmacist':
                    $query->where('workflow_status', 'pharmacy_pending');
                    break;
                case 'receptionist':
                    $query->whereIn('workflow_status', ['scheduled']);
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
        $visit->load(['labOrders.labResult', 'prescriptions', 'invoices']);

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
            'visit_time' => 'nullable|date_format:H:i',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medical_notes' => 'nullable|string',
            'vital_signs' => 'nullable|array',
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
            'visit_time' => $validated['visit_time'] ?? null,
            'chief_complaint' => $validated['chief_complaint'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'medical_notes' => $validated['medical_notes'] ?? null,
            'vital_signs' => $validated['vital_signs'] ?? null,
            'consultation_fee' => $validated['consultation_fee'] ?? 0.00,
            'status' => 'scheduled',
            'workflow_status' => 'scheduled',
            'visit_number' => 'VST-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
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
            'vital_signs' => 'sometimes|array',
            'consultation_fee' => 'sometimes|numeric|min:0',
        ]);

        $visit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Visit updated successfully',
            'visit' => new VisitResource($visit)
        ], 200);
    }

    /**
     * Doctor reviews visit, adds/updatess visit details + lab orders, forwards to cashier
     */
    public function doctorReview(Request $request, Visit $visit): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('doctor') || $visit->doctor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($visit->workflow_status, ['scheduled'])) {
            return response()->json(['success' => false, 'message' => 'Visit cannot be reviewed at this stage'], 422);
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

        DB::transaction(function () use ($visit, $validated) {
            $visit->update([
                'chief_complaint' => $validated['chief_complaint'] ?? $visit->chief_complaint,
                'diagnosis' => $validated['diagnosis'] ?? $visit->diagnosis,
                'medical_notes' => $validated['medical_notes'] ?? $visit->medical_notes,
                'vital_signs' => $validated['vital_signs'] ?? $visit->vital_signs,
                'consultation_fee' => $validated['consultation_fee'] ?? $visit->consultation_fee,
                'workflow_status' => 'awaiting_payment',
            ]);

            if (!empty($validated['lab_tests'])) {
                foreach ($validated['lab_tests'] as $test) {
                    LabOrder::create([
                        'patient_id' => $visit->patient_id,
                        'patient_name' => $visit->patient_name,
                        'doctor_id' => $visit->doctor_id,
                        'doctor_name' => $visit->doctor_name,
                        'visit_id' => $visit->id,
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

            // Auto-generate invoice for cashier
            $items = [];
            $subtotal = 0;

            $consultationFee = $validated['consultation_fee'] ?? $visit->consultation_fee ?? 0;
            if ($consultationFee > 0) {
                $items[] = [
                    'description' => 'Consultation Fee',
                    'quantity' => 1,
                    'unit_price' => $consultationFee,
                    'total' => $consultationFee,
                ];
                $subtotal += $consultationFee;
            }

            if (!empty($validated['lab_tests'])) {
                foreach ($validated['lab_tests'] as $test) {
                    $cost = $test['cost'] ?? 0;
                    if ($cost > 0) {
                        $items[] = [
                            'description' => 'Lab Test: ' . $test['test_name'],
                            'quantity' => 1,
                            'unit_price' => $cost,
                            'total' => $cost,
                        ];
                        $subtotal += $cost;
                    }
                }
            }

            if (!empty($items)) {
                $lastInvoice = Invoice::latest('id')->first();
                $invoiceNumber = 'INV' . str_pad(($lastInvoice?->id ?? 0) + 1, 6, '0', STR_PAD_LEFT);

                Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'patient_id' => $visit->patient_id,
                    'patient_name' => $visit->patient_name,
                    'visit_id' => $visit->id,
                    'invoice_date' => now()->toDateString(),
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'tax' => 0.00,
                    'discount' => 0.00,
                    'total' => $subtotal,
                    'status' => 'pending',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Visit reviewed and forwarded to cashier',
            'visit' => new VisitResource($visit->fresh())
        ], 200);
    }

    /**
     * Cashier marks visit as paid
     */
    public function markPaid(Visit $visit): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('cashier')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($visit->workflow_status !== 'awaiting_payment') {
            return response()->json(['success' => false, 'message' => 'Visit is not awaiting payment'], 422);
        }

        $pendingLabs = $visit->labOrders()->where('status', 'pending')->count();
        $visit->update(['workflow_status' => $pendingLabs > 0 ? 'lab_pending' : 'lab_completed']);

        // Also mark any linked invoice as paid
        $invoice = $visit->invoices()->where('status', 'pending')->latest()->first();
        if ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'payment_method' => 'cash',
                'amount_paid' => $invoice->total,
                'payment_date' => now()->toDateString(),
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed. Visit forwarded to lab.',
            'visit' => new VisitResource($visit)
        ], 200);
    }

    /**
     * Doctor prescribes medicines after lab results
     */
    public function prescribe(Request $request, Visit $visit): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('doctor') || $visit->doctor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($visit->workflow_status !== 'lab_completed') {
            return response()->json(['success' => false, 'message' => 'Lab results are not ready for prescription'], 422);
        }

        $validated = $request->validate([
            'medications' => 'required|array',
            'medications.*.name' => 'required|string',
            'medications.*.dosage' => 'required|string',
            'medications.*.frequency' => 'required|string',
            'medications.*.duration' => 'required|string',
            'medications.*.quantity' => 'required|numeric',
            'medications.*.price' => 'nullable|numeric|min:0',
            'medications.*.instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Prescription::create([
            'patient_id' => $visit->patient_id,
            'patient_name' => $visit->patient_name,
            'doctor_id' => $visit->doctor_id,
            'doctor_name' => $visit->doctor_name,
            'visit_id' => $visit->id,
            'medications' => $validated['medications'],
            'status' => 'pending',
            'prescription_date' => now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $invoiceItems = collect($validated['medications'])
            ->map(function ($medication) {
                $quantity = (float) ($medication['quantity'] ?? 0);
                $unitPrice = (float) ($medication['price'] ?? 0);

                return [
                    'description' => 'Medicine: ' . $medication['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                ];
            })
            ->values()
            ->all();

        $invoiceSubtotal = collect($invoiceItems)->sum('total');
        $pendingInvoice = $visit->invoices()->where('status', 'pending')->latest()->first();

        if ($pendingInvoice) {
            $pendingInvoice->update([
                'items' => json_encode($invoiceItems),
                'subtotal' => $invoiceSubtotal,
                'tax' => 0,
                'discount' => 0,
                'total' => $invoiceSubtotal,
                'invoice_date' => now()->toDateString(),
            ]);
        } else {
            $lastInvoice = Invoice::latest('id')->first();
            $invoiceNumber = 'INV' . str_pad(($lastInvoice?->id ?? 0) + 1, 6, '0', STR_PAD_LEFT);

            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'patient_id' => $visit->patient_id,
                'patient_name' => $visit->patient_name,
                'visit_id' => $visit->id,
                'appointment_id' => $visit->appointment_id,
                'invoice_date' => now()->toDateString(),
                'items' => json_encode($invoiceItems),
                'subtotal' => $invoiceSubtotal,
                'tax' => 0,
                'discount' => 0,
                'total' => $invoiceSubtotal,
                'status' => 'pending',
            ]);
        }

        $visit->update(['workflow_status' => 'pharmacy_awaiting_payment']);

        return response()->json([
            'success' => true,
            'message' => 'Prescription created and forwarded to cashier with invoice for payment',
            'visit' => new VisitResource($visit)
        ], 200);
    }

    /**
     * Cashier confirms prescription payment, forwards to pharmacy
     */
    public function confirmPharmacyPayment(Visit $visit): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('cashier')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($visit->workflow_status !== 'pharmacy_awaiting_payment') {
            return response()->json(['success' => false, 'message' => 'Visit is not awaiting prescription payment'], 422);
        }

        $prescription = $visit->prescriptions()->where('status', 'pending')->latest()->first();
        if (!$prescription) {
            return response()->json(['success' => false, 'message' => 'No pending prescription found for this visit'], 422);
        }

        $shortages = $this->getMedicationShortages($prescription->medications ?? []);
        if (!empty($shortages)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient inventory for: ' . implode('; ', $shortages),
            ], 422);
        }

        $visit->update(['workflow_status' => 'pharmacy_pending']);

        // Also mark any linked invoice as paid
        $invoice = $visit->invoices()->where('status', 'pending')->latest()->first();
        if ($invoice) {
            $invoice->update([
                'status' => 'paid',
                'payment_method' => 'cash',
                'amount_paid' => $invoice->total,
                'payment_date' => now()->toDateString(),
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prescription payment confirmed. Forwarded to pharmacy.',
            'visit' => new VisitResource($visit)
        ], 200);
    }

    /**
     * Pharmacist dispenses prescription, completes visit
     */
    public function dispense(Visit $visit): JsonResponse
    {
        $user = auth()->user();
        if (!$user->hasRole('pharmacist')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($visit->workflow_status !== 'pharmacy_pending') {
            return response()->json(['success' => false, 'message' => 'Visit is not pending pharmacy dispense'], 422);
        }

        $prescription = $visit->prescriptions()->where('status', 'pending')->latest()->first();
        if (!$prescription) {
            return response()->json(['success' => false, 'message' => 'No pending prescription found for this visit'], 422);
        }

        try {
            DB::transaction(function () use ($visit, $prescription) {
                $this->consumeMedicationInventory($prescription->medications ?? []);
                $prescription->update(['status' => 'dispensed']);
                $visit->update(['workflow_status' => 'completed', 'status' => 'completed']);
            });
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->errors()['medications'][0] ?? 'Insufficient inventory to dispense prescription',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Medicines dispensed. Visit completed.',
            'visit' => new VisitResource($visit)
        ], 200);
    }

    private function getMedicationShortages(array $medications): array
    {
        $requirements = [];

        foreach ($medications as $medication) {
            $name = trim((string) ($medication['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            $requirements[$key] = [
                'name' => $name,
                'required' => ($requirements[$key]['required'] ?? 0) + (float) ($medication['quantity'] ?? 0),
            ];
        }

        $shortages = [];

        foreach ($requirements as $requirement) {
            $available = PharmacyInventory::query()
                ->where(function ($query) use ($requirement) {
                    $query->whereRaw('LOWER(medication_name) = ?', [mb_strtolower($requirement['name'])])
                        ->orWhereRaw('LOWER(generic_name) = ?', [mb_strtolower($requirement['name'])]);
                })
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhereDate('expiry_date', '>=', now()->toDateString());
                })
                ->sum('quantity');

            if ($available < $requirement['required']) {
                $shortages[] = sprintf(
                    '%s (required %s, available %s)',
                    $requirement['name'],
                    rtrim(rtrim(number_format($requirement['required'], 2, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format((float) $available, 2, '.', ''), '0'), '.')
                );
            }
        }

        return $shortages;
    }

    private function consumeMedicationInventory(array $medications): void
    {
        $requirements = [];

        foreach ($medications as $medication) {
            $name = trim((string) ($medication['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            $requirements[$key] = [
                'name' => $name,
                'required' => ($requirements[$key]['required'] ?? 0) + (float) ($medication['quantity'] ?? 0),
            ];
        }

        $shortages = $this->getMedicationShortages($medications);
        if (!empty($shortages)) {
            throw ValidationException::withMessages([
                'medications' => ['Insufficient inventory for: ' . implode('; ', $shortages)],
            ]);
        }

        foreach ($requirements as $requirement) {
            $remaining = $requirement['required'];

            $inventoryItems = PharmacyInventory::query()
                ->where(function ($query) use ($requirement) {
                    $query->whereRaw('LOWER(medication_name) = ?', [mb_strtolower($requirement['name'])])
                        ->orWhereRaw('LOWER(generic_name) = ?', [mb_strtolower($requirement['name'])]);
                })
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhereDate('expiry_date', '>=', now()->toDateString());
                })
                ->where('quantity', '>', 0)
                ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($inventoryItems as $inventoryItem) {
                if ($remaining <= 0) {
                    break;
                }

                $deduction = min((float) $inventoryItem->quantity, $remaining);
                $inventoryItem->update([
                    'quantity' => (float) $inventoryItem->quantity - $deduction,
                ]);

                $remaining -= $deduction;
            }
        }
    }
}
