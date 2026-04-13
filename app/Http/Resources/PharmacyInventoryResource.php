<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medication_name' => $this->medication_name,
            'generic_name' => $this->generic_name,
            'dosage' => $this->dosage,
            'form' => $this->form,
            'manufacturer' => $this->manufacturer,
            'quantity' => $this->quantity,
            'reorder_level' => $this->reorder_level,
            'unit_price' => $this->unit_price,
            'expiry_date' => $this->expiry_date,
            'batch_number' => $this->batch_number,
            'is_low_stock' => $this->isLowStock(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
