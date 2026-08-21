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
     * Income statistics: daily/weekly/monthly totals plus cumulative series
     * Required roles: admin, cashier
     */
    public function incomeStats(): JsonResponse
    {
        $today = now()->startOfDay();

        $paidQuery = fn () => Invoice::where('status', 'paid');

        $dailyIncome = (clone $paidQuery())->whereDate('paid_at', $today->toDateString())
            ->orWhere(function ($q) use ($today) {
                $q->where('status', 'paid')
                    ->whereDate('payment_date', $today->toDateString());
            })
            ->get();

        $stats = [
            'daily_income' => (float) $dailyIncome->sum('amount_paid'),
            'daily_payments' => $dailyIncome->count(),
        ];

        // Weekly (last 7 days including today)
        $weekStart = $today->copy()->subDays(6);
        $stats['weekly_income'] = (float) Invoice::where('status', 'paid')
            ->where(function ($q) use ($weekStart) {
                $q->whereDate('paid_at', '>=', $weekStart)
                    ->orWhere(function ($sq) use ($weekStart) {
                        $sq->whereNull('paid_at')
                            ->whereDate('payment_date', '>=', $weekStart);
                    });
            })
            ->where(function ($q) use ($today) {
                $q->whereDate('paid_at', '<=', $today)
                    ->orWhere(function ($sq) use ($today) {
                        $sq->whereNull('paid_at')
                            ->whereDate('payment_date', '<=', $today);
                    });
            })
            ->sum('amount_paid');

        // Monthly (current calendar month)
        $stats['monthly_income'] = (float) Invoice::where('status', 'paid')
            ->where(function ($q) {
                $q->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->orWhere(function ($sq) {
                        $sq->whereNull('paid_at')
                            ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()]);
                    });
            })
            ->sum('amount_paid');

        // Totals
        $stats['completed_payments'] = Invoice::where('status', 'paid')->count();
        $stats['pending_invoices'] = Invoice::where('status', 'pending')->count();
        $stats['pending_amount'] = (float) Invoice::where('status', 'pending')->sum('total');
        $stats['total_income'] = (float) Invoice::where('status', 'paid')->sum('amount_paid');

        // Daily income series for the last 30 days (cumulative)
        $seriesStart = $today->copy()->subDays(29);
        $rows = Invoice::where('status', 'paid')
            ->where(function ($q) use ($seriesStart) {
                $q->whereDate('paid_at', '>=', $seriesStart)
                    ->orWhere(function ($sq) use ($seriesStart) {
                        $sq->whereNull('paid_at')
                            ->whereDate('payment_date', '>=', $seriesStart);
                    });
            })
            ->get();

        $byDay = [];
        foreach ($rows as $invoice) {
            $day = $invoice->paid_at?->toDateString() ?? ($invoice->payment_date?->toDateString() ?? null);
            if ($day) {
                $byDay[$day] = ($byDay[$day] ?? 0) + (float) $invoice->amount_paid;
            }
        }

        $dailySeries = [];
        $cumulative = 0.0;
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $income = $byDay[$date] ?? 0.0;
            $cumulative += $income;
            $dailySeries[] = [
                'date' => $date,
                'income' => round($income, 2),
                'cumulative' => round($cumulative, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'series' => $dailySeries,
            ],
        ]);
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
            'items' => $this->decodeItems($validated['items']),
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

        if (array_key_exists('items', $validated)) {
            $validated['items'] = $this->decodeItems($validated['items']);
        }

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
        if ($invoice->appointment_id) {
            if ($invoice->appointment->workflow_status === 'awaiting_payment') {
                $invoice->appointment->update(['workflow_status' => 'paid']);
            } elseif ($invoice->appointment->workflow_status === 'pharmacy_awaiting_payment') {
                $invoice->appointment->update(['workflow_status' => 'pharmacy_pending']);
            }
        }

        // Auto-advance visit workflow if linked
        if ($invoice->visit_id) {
            if ($invoice->visit->workflow_status === 'awaiting_payment') {
                $pendingLabs = $invoice->visit->labOrders()->where('status', 'pending')->count();
                $invoice->visit->update(['workflow_status' => $pendingLabs > 0 ? 'lab_pending' : 'lab_completed']);
            } elseif ($invoice->visit->workflow_status === 'pharmacy_awaiting_payment') {
                $invoice->visit->update(['workflow_status' => 'pharmacy_pending']);
            }
        }

        // Send payment confirmation email
        $this->emailService->sendInvoicePaid($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid successfully. Payment confirmation sent to patient.',
            'invoice' => new InvoiceResource($invoice)
        ], 200);
    }

    /**
     * Decode a JSON string of items into an array (prevents double encoding)
     */
    private function decodeItems(mixed $items): mixed
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $items;
    }
}
