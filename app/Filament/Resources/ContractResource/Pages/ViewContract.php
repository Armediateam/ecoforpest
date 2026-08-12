<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\GridBuilderConverter;
use App\Helpers\PhoneHelper;

class ViewContract extends ViewRecord
{
    use GridBuilderConverter;

    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Download PDF Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function ($record) {
                    $customer = $record->customer;

                    // Process template content with dynamic placeholders
                    $templateContent = $this->processTemplateContent($record, $customer);

                    $data = [
                        'contract' => $record,
                        'customer' => $customer,
                        'company' => config('app.name'),
                        'date' => \Carbon\Carbon::parse($record->date)->format('d F Y'),
                        'content' => $templateContent,
                        'hasTemplate' => !empty($record->contractTemplate?->content),
                    ];

                    // Choose the appropriate view based on whether template exists
                    $view = $data['hasTemplate'] ? 'contracts.template-preview' : 'contracts.preview';

                    $pdf = Pdf::loadView($view, $data)
                        ->setPaper('a4')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'arial',
                        ]);
                    $contractNumber = $record->id ?? 'draft';
                    $fileName = "Contract-{$contractNumber}.pdf";

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, $fileName);
                }),
            Actions\EditAction::make(),
        ];
    }

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
            '{company}' => config('app.name', 'Ecoforpest'),
            '{company_name}' => config('app.name', 'Ecoforpest'),
            '{date}' => \Carbon\Carbon::parse($contract->created_at)->format('d F Y'),
            '{contract_number}' => $contract->id ?? 'Draft',
            '{contract_subject}' => $contract->subject ?? 'N/A',
            '{start_date}' => $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d F Y') : 'N/A',
            '{end_date}' => $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d F Y') : 'N/A',
            '{contract_value}' => $contract->contract_value ? 'Rp ' . number_format($contract->contract_value, 2) : 'Rp 0.00',
            '{total_workmanship}' => $contract->total_workmanship ? number_format($contract->total_workmanship, 2) : '0.00',
            '{contract_type}' => $contract->contractType?->name ?? 'N/A',
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
