<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Helpers\SettingsHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, string $customMessage = '')
    {
        $this->invoice = $invoice;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $recipientEmail = $this->invoice->customer->email ?? 'no-email@example.com';
        $subject = 'Invoice: ' . ($this->invoice->invoice_number ?? 'Your Invoice');

        return new Envelope(
            to: $recipientEmail,
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $contactInfo = SettingsHelper::getEmailContactInfo();

        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'customMessage' => $this->customMessage,
                'customer' => $this->invoice->customer,
                'lead' => $this->invoice->lead,
                'company' => $contactInfo['company_name'],
                'contactInfo' => $contactInfo,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        try {
            // Load invoice with relationships
            $this->invoice->load(['customer', 'invoiceItem']);

            $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $this->invoice])
                ->setPaper('letter', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'arial',
                ]);

            $safeInvoiceNumber = str_replace('/', '-', $this->invoice->invoice_number);
            $fileName = $safeInvoiceNumber . ' - ecoforpest.pdf';

            $attachments[] = Attachment::fromData(
                fn() => $pdf->output(),
                $fileName
            )->withMime('application/pdf');
        } catch (\Exception $e) {
            Log::error('Error generating invoice PDF attachment: ' . $e->getMessage());
        }

        return $attachments;
    }
}
