<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient_name,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor_name,
            'visit_id' => $this->visit_id,
            'test_name' => $this->test_name,
            'test_type' => $this->test_type,
            'priority' => $this->priority,
            'status' => $this->status,
            'notes' => $this->notes,
            'order_date' => $this->order_date,
            'cost' => $this->cost,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
