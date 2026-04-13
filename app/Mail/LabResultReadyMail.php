<?php

namespace App\Mail;

use App\Models\LabResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LabResultReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LabResult $labResult)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Lab Result Ready - {$this->labResult->test_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lab-result-ready',
            with: [
                'labResult' => $this->labResult,
                'patientName' => $this->labResult->patient_name,
                'testName' => $this->labResult->test_name,
                'resultDate' => $this->labResult->result_date->format('F d, Y'),
                'results' => $this->labResult->results,
                'resultFileUrl' => $this->labResult->result_file_url,
            ],
        );
    }
}
