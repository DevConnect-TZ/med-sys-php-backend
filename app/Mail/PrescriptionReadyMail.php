<?php

namespace App\Mail;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrescriptionReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Prescription $prescription)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Prescription Ready for Pickup - {$this->prescription->patient_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prescription-ready',
            with: [
                'prescription' => $this->prescription,
                'patientName' => $this->prescription->patient_name,
                'doctorName' => $this->prescription->doctor_name,
                'medicationCount' => count($this->prescription->medications ?? []),
                'medications' => $this->prescription->medications ?? [],
            ],
        );
    }
}
