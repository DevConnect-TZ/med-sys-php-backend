<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Confirmed - Invoice #{$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-paid',
            with: [
                'invoice' => $this->invoice,
                'patientName' => $this->invoice->patient_name,
                'invoiceNumber' => $this->invoice->invoice_number,
                'paymentDate' => $this->invoice->payment_date->format('F d, Y'),
                'paymentMethod' => $this->invoice->payment_method,
                'amountPaid' => $this->invoice->amount_paid,
                'total' => $this->invoice->total,
            ],
        );
    }
}
