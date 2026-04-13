<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Patient;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Get all invoices
     * Required roles: admin, receptionist, cashier
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Invoice::query();

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
        $invoices = $query->orderBy('invoice_date', 'desc')->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get invoice by ID
     */
    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'success' => true,
            'invoice' => new InvoiceResource($invoice)
        ], 200);
    }

    /**
     * Create invoice
     * Required roles: admin, receptionist
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'invoice_date' => 'required|date',
            'items' => 'required|json',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Generate invoice number
        $lastInvoice = Invoice::latest('id')->first();
        $invoiceNumber = 'INV' . str_pad(($lastInvoice?->id ?? 0) + 1, 6, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'patient_id' => $validated['patient_id'],
            'patient_name' => $patient->full_name,
            'visit_id' => $validated['visit_id'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'items' => $validated['items'],
            'subtotal' => $validated['subtotal'],
            'tax' => $validated['tax'] ?? 0.00,
            'discount' => $validated['discount'] ?? 0.00,
            'total' => $validated['total'],
            'status' => 'pending',
        ]);

        // Send invoice created email
        $this->emailService->sendInvoiceCreated($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully. Invoice notification sent to patient.',
            'invoice' => new InvoiceResource($invoice),
            'invoice_number' => $invoiceNumber
        ], 201);
    }

    /**
     * Update invoice
     * Required roles: admin, receptionist, cashier
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'sometimes|json',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
        ]);

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'invoice' => new InvoiceResource($invoice)
        ], 200);
    }

    /**
     * Mark invoice as paid
     * Required roles: admin, cashier
     */
    public function markAsPaid(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already paid'
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,insurance,other',
            'amount_paid' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        $invoice->update([
            'status' => 'paid',
            'payment_method' => $validated['payment_method'],
            'amount_paid' => $validated['amount_paid'],
            'payment_date' => $validated['payment_date'],
            'paid_at' => now(),
        ]);

        // Auto-advance appointment workflow if linked
        if ($invoice->appointment_id && $invoice->appointment->workflow_status === 'awaiting_payment') {
            $invoice->appointment->update(['workflow_status' => 'paid']);
        }

        // Send payment confirmation email
        $this->emailService->sendInvoicePaid($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid successfully. Payment confirmation sent to patient.',
            'invoice' => new InvoiceResource($invoice)
        ], 200);
    }
}
