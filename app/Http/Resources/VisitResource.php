<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
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
            'appointment_id' => $this->appointment_id,
            'visit_date' => $this->visit_date,
            'chief_complaint' => $this->chief_complaint,
            'diagnosis' => $this->diagnosis,
            'medical_notes' => $this->medical_notes,
            'vital_signs' => $this->vital_signs,
            'consultation_fee' => $this->consultation_fee,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
