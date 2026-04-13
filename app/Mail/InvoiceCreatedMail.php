<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number} - AFYA Medical Center",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-created',
            with: [
                'invoice' => $this->invoice,
                'patientName' => $this->invoice->patient_name,
                'invoiceNumber' => $this->invoice->invoice_number,
                'invoiceDate' => $this->invoice->invoice_date->format('F d, Y'),
                'items' => $this->invoice->items ?? [],
                'subtotal' => $this->invoice->subtotal,
                'tax' => $this->invoice->tax,
                'discount' => $this->invoice->discount,
                'total' => $this->invoice->total,
            ],
        );
    }
}
