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
            'visit_number' => $this->visit_number,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient_name,
            'doctor_id' => $this->doctor_id,
            'doctor_name' => $this->doctor_name,
            'appointment_id' => $this->appointment_id,
            'visit_date' => $this->visit_date,
            'visit_time' => $this->visit_time,
            'chief_complaint' => $this->chief_complaint,
            'diagnosis' => $this->diagnosis,
            'medical_notes' => $this->medical_notes,
            'vital_signs' => $this->vital_signs,
            'consultation_fee' => $this->consultation_fee,
            'status' => $this->status,
            'workflow_status' => $this->workflow_status,
            'lab_orders' => $this->whenLoaded('labOrders', function () {
                return $this->labOrders->map(function ($labOrder) {
                    return [
                        'id' => $labOrder->id,
                        'test_name' => $labOrder->test_name,
                        'test_type' => $labOrder->test_type,
                        'status' => $labOrder->status,
                        'cost' => $labOrder->cost,
                        'lab_result' => $labOrder->labResult ? [
                            'results' => $labOrder->labResult->results,
                        ] : null,
                    ];
                });
            }),
            'prescriptions' => $this->whenLoaded('prescriptions', function () {
                return $this->prescriptions->map(function ($prescription) {
                    return [
                        'id' => $prescription->id,
                        'status' => $prescription->status,
                        'medications' => $prescription->medications,
                    ];
                });
            }),
            'invoices' => $this->whenLoaded('invoices', function () {
                return $this->invoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_date' => $invoice->invoice_date,
                        'total' => $invoice->total,
                        'status' => $invoice->status,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
