<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResultResource extends JsonResource
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
            'lab_order_id' => $this->lab_order_id,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient_name,
            'test_name' => $this->test_name,
            'results' => $this->results,
            'result_file_url' => $this->result_file_url,
            'technician_id' => $this->technician_id,
            'technician_name' => $this->technician_name,
            'result_date' => $this->result_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
