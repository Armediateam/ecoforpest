<?php

namespace App\Mail;

use App\Models\Proposal;
use App\Models\ProposalCustomer;
use App\Helpers\SettingsHelper;
use App\Traits\GridBuilderConverter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Helpers\PhoneHelper;

class ProposalMail extends Mailable
{
    use Queueable, SerializesModels, GridBuilderConverter;

    public $proposal;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($proposal, string $customMessage = '')
    {
        $this->proposal = $proposal;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $recipientEmail = $this->proposal->email ?? 'no-email@example.com';
        $subject = 'Proposal: ' . ($this->proposal->subject ?? 'Your Proposal');

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
        // Load relationships if not already loaded
        $this->loadProposalRelationships();

        $contactInfo = SettingsHelper::getEmailContactInfo();

        return new Content(
            view: 'emails.proposal',
            with: [
                'proposal' => $this->proposal,
                'customMessage' => $this->customMessage,
                'customer' => ($this->proposal->related == 'customer') ? $this->proposal->customer : $this->proposal->lead,
                'company' => $contactInfo['company_name'],
                'contactInfo' => $contactInfo,
            ],
        );
    }

    /**
     * Load proposal relationships
     */
    private function loadProposalRelationships()
    {
        try {
            // Load basic relationships first
            if ($this->proposal instanceof ProposalCustomer) {
                $this->proposal->load(['proposalTemplate', 'country', 'paymentTerm', 'customer', 'lead']);

                // Try to load proposalOrder separately
                if (!$this->proposal->relationLoaded('proposalOrder')) {
                    $this->proposal->load('proposalOrder');
                }

                // Load proposalItems if proposalOrder exists
                if ($this->proposal->proposalOrder) {
                    try {
                        $this->proposal->proposalOrder->load('proposalItems');
                    } catch (\Exception $e) {
                        Log::warning('Could not load proposalItems for ProposalCustomer: ' . $e->getMessage());
                    }
                }
            } else {
                $this->proposal->load(['proposalTemplate', 'country', 'paymentTerm', 'customer', 'lead']);

                // Try to load proposalOrder separately
                if (!$this->proposal->relationLoaded('proposalOrder')) {
                    $this->proposal->load('proposalOrder');
                }

                // Load proposalItems if proposalOrder exists
                if ($this->proposal->proposalOrder) {
                    try {
                        $this->proposal->proposalOrder->load('proposalItems');
                    } catch (\Exception $e) {
                        Log::warning('Could not load proposalItems for Proposal: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error loading proposal relationships: ' . $e->getMessage());
        }
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        try {
            // Load proposal with relationships
            $this->loadProposalRelationships();

            // Prepare data for PDF generation
            $customer = ($this->proposal->related == 'customer') ? $this->proposal->customer : $this->proposal->lead;

            // Process template content with dynamic placeholders
            $templateContent = $this->processTemplateContent($this->proposal, $customer);

            $data = [
                'proposal' => $this->proposal,
                'customer' => $customer,
                'company' => config('app.name'),
                'date' => \Carbon\Carbon::parse($this->proposal->date ?? now())->format('d F Y'),
                'content' => $templateContent,
                'hasTemplate' => !empty($this->proposal->proposalTemplate?->content),
            ];

            // Choose the appropriate view based on whether template exists
            $view = $data['hasTemplate'] ? 'proposals.template-preview' : 'proposals.preview';

            $pdf = PDF::loadView($view, $data)
                ->setPaper('a4')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'arial',
                ]);

            $proposalNumber = $this->proposal->id ?? 'draft';
            $fileName = "Proposal-{$proposalNumber}.pdf";

            $attachments[] = Attachment::fromData(
                fn() => $pdf->output(),
                $fileName
            )->withMime('application/pdf');
        } catch (\Exception $e) {
            Log::error('Error generating proposal PDF attachment: ' . $e->getMessage());
        }

        return $attachments;
    }

    /**
     * Process template content and replace placeholders with actual data
     */
    private function processTemplateContent($proposal, $customer)
    {
        $content = $proposal->proposalTemplate?->content ?? '';

        if (empty($content)) {
            return '';
        }

        // Convert TiptapEditor JSON content to HTML
        $htmlContent = tiptap_converter()->asHTML($content);

        $order = null;
        if ($proposal->proposalOrder) {
            if ($proposal->proposalOrder instanceof \Illuminate\Database\Eloquent\Collection) {
                $order = $proposal->proposalOrder->first();
            } else {
                $order = $proposal->proposalOrder;
            }
        }

        // Calculate totals
        $subtotal = $order?->subtotal ?? 0;
        $discountFixed = $order?->discount_fixed ?? 0;
        $discountPercent = $order?->discount_percent ?? 0;
        $adjustment = $order?->adjustment ?? 0;
        $total = $order?->total ?? 0;

        // Prepare replacement data
        $replacements = [
            '{customer}' => $customer?->name ?? 'N/A',
            '{customer_name}' => $customer?->name ?? 'N/A',
            '{customer_email}' => $customer?->email ?? $proposal->email ?? 'N/A',
            '{customer_phone}' => PhoneHelper::clean($customer?->phone) ?? PhoneHelper::clean($proposal->phone) ?? 'N/A',
            '{customer_address}' => $customer?->address ?? $proposal->address ?? 'N/A',
            '{company}' => config('app.name', 'Ecoforpest'),
            '{company_name}' => config('app.name', 'Ecoforpest'),
            '{date}' => \Carbon\Carbon::parse($proposal->date ?? now())->format('d F Y'),
            '{proposal_date}' => \Carbon\Carbon::parse($proposal->date ?? now())->format('d F Y'),
            '{valid_until}' => $proposal->open_till ? \Carbon\Carbon::parse($proposal->open_till)->format('d F Y') : 'N/A',
            '{proposal_number}' => $proposal->id ?? 'Draft',
            '{proposal_subject}' => $proposal->subject ?? 'N/A',
            '{subtotal}' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            '{discount_fixed}' => 'Rp ' . number_format($discountFixed, 0, ',', '.'),
            '{discount_percent}' => $discountPercent . '%',
            '{adjustment}' => 'Rp ' . number_format($adjustment, 0, ',', '.'),
            '{total}' => 'Rp ' . number_format($total, 0, ',', '.'),
            '{payment_term}' => $proposal->paymentTerm?->name ?? 'N/A',
            '{contract_start}' => $proposal->contract_start_date ? \Carbon\Carbon::parse($proposal->contract_start_date)->format('d F Y') : 'N/A',
            '{contract_end}' => $proposal->contract_end_date ? \Carbon\Carbon::parse($proposal->contract_end_date)->format('d F Y') : 'N/A',
            '{warranty_term}' => $proposal->warranty_term ?? 'N/A',
            '{warranty_type}' => $proposal->warranty_type ?? 'N/A',
            '{to}' => $proposal->to ?? 'N/A',
            '{email}' => $proposal->email ?? 'N/A',
            '{phone}' => $proposal->phone ?? 'N/A',
            '{address}' => $proposal->address ?? 'N/A',
            '{city}' => $proposal->city ?? 'N/A',
            '{state}' => $proposal->state ?? 'N/A',
            '{zip_code}' => $proposal->zip_code ?? 'N/A',
            '{country}' => $proposal->country?->name ?? 'N/A',
        ];

        // Replace placeholders in content
        $processedContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $htmlContent
        );

        // Convert Filament TipTap grid builder to PDF-compatible table layout
        $processedContent = $this->convertGridBuilderToTable($processedContent);

        return $processedContent;
    }
}
