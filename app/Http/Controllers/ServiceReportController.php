<?php

namespace App\Http\Controllers;

use App\Models\ServiceReport;
use App\Models\WorkOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ServiceReportController extends Controller
{
    public function previewPdf(WorkOrder $workOrder)
    {
        $report = ServiceReport::where('work_order_id', $workOrder->id)->first();
        if (! $report) {
            $report = new ServiceReport([
                'work_order_id' => $workOrder->id,
                'title' => 'Service Report #' . $workOrder->id,
                'content' => 'Belum ada laporan service.',
            ]);
        }
        $workOrder->load(['customer', 'assigned']);

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

        return $pdf->stream('service-report-' . $workOrder->id . '.pdf');
    }

    public function previewPdfCustomer($id)
    {
        $report = ServiceReport::where('id', $id)->first();
        if (!$report) {
            return response()->json(['message' => 'Service Report not found.'], Response::HTTP_NOT_FOUND);
        }
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

        return $pdf->stream('service-report-customer-' . $workOrder->id . '.pdf');

        // $pdf->setPaper('a4');
        // $pdf->setOptions([
        //     'isRemoteEnabled' => true,
        //     'isHtml5ParserEnabled' => true,
        //     'isFontSubsettingEnabled' => true,
        //     'margin_top' => 20,
        //     'margin_right' => 15,
        //     'margin_bottom' => 20,
        //     'margin_left' => 15,
        // ]);

        // return $pdf->stream('service-report-customer-' . $workOrder->id . '.pdf');
    }
}
