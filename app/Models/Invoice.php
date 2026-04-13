<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'patient_id',
        'patient_name',
        'visit_id',
        'appointment_id',
        'invoice_date',
        'items',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'payment_method',
        'amount_paid',
        'payment_date',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'items' => 'json',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
