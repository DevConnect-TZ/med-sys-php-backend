<?php

namespace App\Services;

use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentScheduledMail;
use App\Mail\InvoiceCreatedMail;
use App\Mail\InvoicePaidMail;
use App\Mail\LabResultReadyMail;
use App\Mail\PrescriptionReadyMail;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabResult;
use App\Models\Prescription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send appointment scheduled email
     */
    public function sendAppointmentScheduled(Appointment $appointment): bool
    {
        try {
            if (!$appointment->patient->email) {
                Log::warning("Patient email not found for appointment {$appointment->id}");
                return false;
            }

            Mail::to($appointment->patient->email)
                ->queue(new AppointmentScheduledMail($appointment));

            Log::info("Appointment scheduled email queued for patient {$appointment->patient_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send appointment scheduled email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send appointment cancelled email
     */
    public function sendAppointmentCancelled(Appointment $appointment): bool
    {
        try {
            if (!$appointment->patient->email) {
                Log::warning("Patient email not found for appointment {$appointment->id}");
                return false;
            }

            Mail::to($appointment->patient->email)
                ->queue(new AppointmentCancelledMail($appointment));

            Log::info("Appointment cancelled email queued for patient {$appointment->patient_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send appointment cancelled email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send prescription ready email
     */
    public function sendPrescriptionReady(Prescription $prescription): bool
    {
        try {
            if (!$prescription->patient->email) {
                Log::warning("Patient email not found for prescription {$prescription->id}");
                return false;
            }

            Mail::to($prescription->patient->email)
                ->queue(new PrescriptionReadyMail($prescription));

            Log::info("Prescription ready email queued for patient {$prescription->patient_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send prescription ready email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send invoice created email
     */
    public function sendInvoiceCreated(Invoice $invoice): bool
    {
        try {
            if (!$invoice->patient->email) {
                Log::warning("Patient email not found for invoice {$invoice->id}");
                return false;
            }

            Mail::to($invoice->patient->email)
                ->queue(new InvoiceCreatedMail($invoice));

            Log::info("Invoice created email queued for patient {$invoice->patient_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send invoice created email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send invoice paid email
     */
    public function sendInvoicePaid(Invoice $invoice): bool
    {
        try {
            if (!$invoice->patient->email) {
                Log::warning("Patient email not found for invoice {$invoice->id}");
                return false;
            }

            Mail::to($invoice->patient->email)
                ->queue(new InvoicePaidMail($invoice));

            Log::info("Invoice paid email queued for patient {$invoice->patient_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send invoice paid email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send lab result ready email
     */
    public function sendLabResultReady(LabResult $labResult): bool
    {
        try {
            if (!$labResult->patient->email) {
                Log::warning("Patient email not found for lab result {$labResult->id}");
                return false;
            }

            Mail::to($labResult->patient->email)
                ->queue(new LabResultReadyMail($labResult));

            Log::info("Lab result ready email queued for patient {$labResult->patient_id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send lab result ready email: {$e->getMessage()}");
            return false;
        }
    }
}
