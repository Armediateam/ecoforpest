<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Traits\GridBuilderConverter;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PhoneHelper;

class ContractPreviewController extends Controller
{
    use GridBuilderConverter;
    
    public function __invoke(Contract $contract)
    {
        $contract->load(['contractTemplate', 'customer', 'contractType']);

        $customer = $contract->customer;

        // Process template content with dynamic placeholders
        $templateContent = $this->processTemplateContent($contract, $customer);

        $data = [
            'contract' => $contract,
            'customer' => $customer,
            'company' => config('app.name'),
            'date' => Carbon::parse($contract->start_date)->format('d F Y'),
            'content' => $templateContent,
            'hasTemplate' => !empty($contract->contractTemplate?->content),
        ];

        // Choose the appropriate view based on whether template exists
        $view = $data['hasTemplate'] ? 'contracts.template-preview' : 'contracts.preview';

        return PDF::loadView($view, $data)
            ->setPaper('a4')
            ->stream("contract-{$contract->id}.pdf");
    }

    /**
     * Process template content and replace placeholders with actual data
     */
    private function processTemplateContent($contract, $customer)
    {
        $content = $contract->contractTemplate?->content ?? '';

        if (empty($content)) {
            return '';
        }

        // First convert tiptap JSON to HTML
        $htmlContent = tiptap_converter()->asHTML($content);

        // Prepare replacement data
        $replacements = [
            '{customer}' => $customer?->name ?? 'N/A',
            '{customer_name}' => $customer?->name ?? 'N/A',
            '{customer_email}' => $customer?->email ?? 'N/A',
            '{customer_phone}' => PhoneHelper::clean($customer?->phone) ?? 'N/A',
            '{customer_address}' => $customer?->address ?? 'N/A',
            '{customer_company}' => $customer?->company ?? 'N/A',
            '{company}' => config('app.name', 'Ecoforpest'),
            '{company_name}' => config('app.name', 'Ecoforpest'),
            '{date}' => Carbon::parse($contract->start_date)->format('d F Y'),
            '{contract_date}' => Carbon::parse($contract->start_date)->format('d F Y'),
            '{start_date}' => Carbon::parse($contract->start_date)->format('d F Y'),
            '{end_date}' => Carbon::parse($contract->end_date)->format('d F Y'),
            '{contract_number}' => $contract->id ?? 'Draft',
            '{contract_subject}' => $contract->subject ?? 'N/A',
            '{contract_value}' => 'Rp ' . number_format($contract->contract_value ?? 0, 0, ',', '.'),
            '{total_workmanship}' => number_format($contract->total_workmanship ?? 0, 0, ',', '.'),
            '{contract_type}' => $contract->contractType?->name ?? 'N/A',
            '{payment_terms}' => $contract->contractType?->payment_terms ?? 'N/A',
            '{description}' => $contract->description ?? 'N/A',
        ];

        // Replace placeholders in HTML content
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
