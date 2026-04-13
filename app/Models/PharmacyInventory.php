<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyInventory extends Model
{
    protected $table = 'pharmacy_inventory';

    protected $fillable = [
        'medication_name',
        'generic_name',
        'dosage',
        'form',
        'manufacturer',
        'quantity',
        'reorder_level',
        'unit_price',
        'expiry_date',
        'batch_number',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Check if item is low stock
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    /**
     * Check if item is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
