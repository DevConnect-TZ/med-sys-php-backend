<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'invoice_number' => $this->invoice_number,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient_name,
            'visit_id' => $this->visit_id,
            'appointment_id' => $this->appointment_id,
            'invoice_date' => $this->invoice_date,
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'amount_paid' => $this->amount_paid,
            'payment_date' => $this->payment_date,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
