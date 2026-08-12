<?php

namespace App\Mail;

use App\Models\Payment;
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

class PaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment, string $customMessage = '')
    {
        $this->payment = $payment;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $recipientEmail = $this->payment->invoice->customer->email ?? 'no-email@example.com';
        $subject = 'Payment Receipt - ' . ($this->payment->invoice->invoice_number ?? 'Payment Confirmation');

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
            view: 'emails.payment',
            with: [
                'payment' => $this->payment,
                'customMessage' => $this->customMessage,
                'customer' => $this->payment->invoice->customer,
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
            // Load payment with relationships
            $this->payment->load(['invoice.customer']);

            $pdf = Pdf::loadView('pdf.receipt', ['payment' => $this->payment])
                ->setPaper('letter', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'arial',
                ]);

            $fileName = 'Receipt - ecoforpest.pdf';

            $attachments[] = Attachment::fromData(
                fn() => $pdf->output(),
                $fileName
            )->withMime('application/pdf');
        } catch (\Exception $e) {
            Log::error('Error generating payment PDF attachment: ' . $e->getMessage());
        }

        return $attachments;
    }
}
