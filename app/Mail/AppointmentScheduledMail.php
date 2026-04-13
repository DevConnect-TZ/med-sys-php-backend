<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Appointment Confirmation - {$this->appointment->appointment_date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-scheduled',
            with: [
                'appointment' => $this->appointment,
                'patientName' => $this->appointment->patient_name,
                'doctorName' => $this->appointment->doctor_name,
                'appointmentDate' => $this->appointment->appointment_date->format('F d, Y'),
                'appointmentTime' => $this->appointment->appointment_time,
                'reason' => $this->appointment->reason,
            ],
        );
    }
}
