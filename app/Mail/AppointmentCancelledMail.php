<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Appointment Cancelled - {$this->appointment->appointment_date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-cancelled',
            with: [
                'appointment' => $this->appointment,
                'patientName' => $this->appointment->patient_name,
                'doctorName' => $this->appointment->doctor_name,
                'appointmentDate' => $this->appointment->appointment_date->format('F d, Y'),
                'appointmentTime' => $this->appointment->appointment_time,
            ],
        );
    }
}
