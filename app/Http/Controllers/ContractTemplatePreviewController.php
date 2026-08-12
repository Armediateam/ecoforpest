<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use App\Traits\GridBuilderConverter;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractTemplatePreviewController extends Controller
{
    use GridBuilderConverter;

    public function preview(ContractTemplate $contractTemplate)
    {
        $content = $this->processTemplateContent($contractTemplate);

        $data = [
            'template' => $contractTemplate,
            'content' => $content,
            'date' => Carbon::now()->format('d F Y'),
        ];

        return PDF::loadView('contract-templates.preview', $data)
            ->setPaper('a4')
            ->stream("contract-template-{$contractTemplate->id}.pdf");
    }

    public function previewHtml(ContractTemplate $contractTemplate)
    {
        $content = $this->processTemplateContent($contractTemplate);

        $data = [
            'template' => $contractTemplate,
            'content' => $content,
            'date' => Carbon::now()->format('d F Y'),
        ];

        return view('contract-templates.preview', $data);
    }

    private function processTemplateContent(ContractTemplate $template)
    {
        $content = $template->content ?? '';

        if (empty($content)) {
            return '';
        }

        // Convert tiptap JSON to HTML
        $htmlContent = tiptap_converter()->asHTML($content);

        // Sample replacement data for preview
        $replacements = [
            '{customer}' => 'ABC Corporation',
            '{customer_name}' => 'ABC Corporation',
            '{customer_email}' => 'contact@abccorp.com',
            '{customer_phone}' => '+62 21 1234 5678',
            '{customer_address}' => 'Jl. Sudirman No. 123, Jakarta',
            '{customer_company}' => 'ABC Corporation',
            '{company}' => config('app.name', 'Ecoforpest'),
            '{company_name}' => config('app.name', 'Ecoforpest'),
            '{date}' => Carbon::now()->format('d F Y'),
            '{contract_date}' => Carbon::now()->format('d F Y'),
            '{start_date}' => Carbon::now()->format('d F Y'),
            '{end_date}' => Carbon::now()->addYear()->format('d F Y'),
            '{contract_number}' => 'CON-2025-001',
            '{contract_subject}' => 'Sample Contract Subject',
            '{contract_value}' => 'Rp 50,000,000',
            '{total_workmanship}' => '12',
            '{contract_type}' => 'Service Contract',
            '{payment_terms}' => 'Net 30 Days',
            '{description}' => 'This is a sample contract description for preview purposes.',
        ];

        // Replace placeholders in content
        $processedContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $htmlContent
        );

        // Convert grid builder to table for PDF compatibility
        $processedContent = $this->convertGridBuilderToTable($processedContent);

        return $processedContent;
    }
}
