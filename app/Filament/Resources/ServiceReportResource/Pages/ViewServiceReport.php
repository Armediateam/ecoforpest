<?php

namespace App\Filament\Resources\ServiceReportResource\Pages;

use App\Filament\Resources\ServiceReportResource;
use Filament\Actions;
use App\Models\ServiceReport;
use Filament\Resources\Pages\ViewRecord;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use Filament\Infolists\Infolist;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewServiceReport extends ViewRecord
{
    protected static string $resource = ServiceReportResource::class;

    protected static string $view = 'filament.resources.service-report-resource.pages.view-service-report-tabs';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning'),
            Actions\Action::make('download_pdf_for_customer')
                ->label('Download PDF for Customer')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function ($record) {
                    $report = ServiceReport::findOrFail($record->id);
                    $workOrder = $report->workOrder()
                        ->with([
                            'customer',
                            'assigned',
                            'service',
                            'surveys.surveyForm',
                            'progress' => function ($query) {
                                $query->latest();
                            }
                        ])
                        ->first();

                    // Get all progress photos
                    $progressPhotos = $workOrder->progress
                        ->flatMap(fn($p) => $p->photo_url ?? [])
                        ->filter()
                        ->values()
                        ->all();

                    // Get the latest identification survey
                    $identificationSurvey = $workOrder->surveys()
                        ->whereHas('surveyForm', function ($q) {
                            $q->where('type', 'identification');
                        })
                        ->with('surveyForm')
                        ->latest()
                        ->first();

                    // Get evaluation survey if exists
                    $evaluationSurvey = $workOrder->surveys()
                        ->whereHas('surveyForm', function ($q) {
                            $q->where('type', 'evaluation');
                        })
                        ->with('surveyForm')
                        ->latest()
                        ->first();

                    $pdf = Pdf::loadView('pdf.service-report-customer', [
                        'report' => $report,
                        'workOrder' => $workOrder,
                        'progressPhotos' => $progressPhotos,
                        'identificationSurvey' => $identificationSurvey,
                        'evaluationSurvey' => $evaluationSurvey,
                        'date' => now()->format('d F Y'),
                        'time' => now()->format('H:i'),
                        'reportNumber' => sprintf(
                            'SR-%s-%s',
                            $workOrder->id,
                            now()->format('Ymd')
                        ),
                    ]);

                    $pdf->setPaper('a4');
                    $pdf->setOptions([
                        'isRemoteEnabled' => true,
                        'isHtml5ParserEnabled' => true,
                        'isFontSubsettingEnabled' => true,
                        'margin_top' => 20,
                        'margin_right' => 15,
                        'margin_bottom' => 20,
                        'margin_left' => 15,
                    ]);

                    return response()->streamDownload(
                        function () use ($pdf) {
                            echo $pdf->stream();
                        },
                        sprintf(
                            'service-report-customer-%s-%s.pdf',
                            $record->work_order_id,
                            now()->format('Ymd-His')
                        )
                    );
                }),
            // ...existing code...
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function ($record) {
                    $report = ServiceReport::where('work_order_id', $record->work_order_id)->firstOrFail();
                    $workOrder = $report->workOrder()->with(['customer', 'assigned'])->first();
                    // Ambil survey identifikasi (beserta jawaban)
                    $identificationSurvey = $workOrder->surveys()
                        ->whereHas('surveyForm', function ($q) {
                            $q->where('type', 'identification');
                        })
                        ->with('surveyForm')
                        ->latest()
                        ->first();
                    $surveyFields = $identificationSurvey?->surveyForm?->fields ?? [];
                    $surveyAnswers = $identificationSurvey?->answers ?? [];
                    $pdf = Pdf::loadView('pdf.service-report', [
                        'report' => $report,
                        'workOrder' => $workOrder,
                        'surveyFields' => $surveyFields,
                        'surveyAnswers' => $surveyAnswers,
                    ]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, 'service-report-' . $record->work_order_id . '.pdf');
                }),
            // Actions\Action::make('resend_email')
            //     ->label('Resend Email')
            //     ->icon('heroicon-o-envelope')
            //     ->color('primary')
            //     ->requiresConfirmation()
            //     ->modalDescription('Are you sure you want to resend the service report email to the client?')
            //     ->action(function () {
            //         // Add email sending logic here
            //         $this->record->update(['email_sent' => true]);
            //     })
            //     ->successNotificationTitle('Email sent successfully')
            //     ->visible(fn(): bool => !$this->record->email_sent),
        ];
    }

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forServiceReport()->toArray();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get the infolist from the resource class
        // dd($this->record->workOrder->getSurveyWithAnswersAttribute());
        $resource = static::getResource();
        return $resource::infolist($infolist);
    }
}
