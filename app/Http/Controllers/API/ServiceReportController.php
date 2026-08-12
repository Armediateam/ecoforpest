<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\ServiceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceReportController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceReport::query();
        if ($request->work_order_id) {
            $query->where('work_order_id', $request->work_order_id);
        }
        if ($request->customer_name) {
            $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
        }
        $reports = $query->orderByDesc('created_at')->paginate(15);
        return response()->json($reports);
    }

    /**
     * GET /api/work-orders/{workOrder}/service-report
     * Ambil service report berdasarkan work order id
     */
    public function show($workOrderId)
    {
        $report = ServiceReport::where('work_order_id', $workOrderId)->first();
        if (!$report) {
            return response()->json(['message' => 'Service report tidak ditemukan'], 404);
        }
        return response()->json($report);
    }

    public function store(Request $request, $workOrderId)
    {
        $workOrder = WorkOrder::with(['assigned', 'customer'])->findOrFail($workOrderId);
        $validated = $request->validate([
            'technician_signature' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
            'client_signature' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
        ]);

        if (ServiceReport::where('work_order_id', $workOrderId)->exists()) {
            return response()->json(['message' => 'Service report untuk work order ini sudah ada.'], 422);
        }

        $validated['customer_name'] = $request->filled('customer_name')
            ? $request->input('customer_name')
            : ($workOrder->customer?->name ?? ($workOrder->lead?->name ?? 'No Customer Name'));
        $validated['work_order_id'] = $workOrderId;
        $validated['created_by'] = Auth::id();
        $validated['signature_token'] = Str::random(32);
        $validated['work_order_number'] = $workOrder->id;
        $validated['technician_name'] = $workOrder->assigned?->name ?? $request->user()->name ?? 'No Technician Name';
        $validated['close_order'] = $workOrder->status === WorkOrder::STATUS_CLOSED ? now()->toDateTimeString() : null;

        // Nama client diambil dari customer_name
        $validated['client_signature_name'] = $validated['customer_name'] ?? $workOrder->customer?->name ?? ($workOrder->lead?->name ?? 'No Client Name');
        // Nama teknisi diambil dari assigned
        $validated['technician_signature_name'] = $workOrder->assigned?->name ?? $request->user()->name ?? 'No Technician Name';

        if ($request->hasFile('client_signature')) {
            $file = $request->file('client_signature');
            $validated['client_signature'] = $file->store('service-report-signatures', 'public');
            // client_signature_name sudah otomatis nama client
            $validated['client_approve'] = true;
        }
        if ($request->hasFile('technician_signature')) {
            $file = $request->file('technician_signature');
            $validated['technician_signature'] = $file->store('service-report-signatures', 'public');
            // technician_signature_name sudah otomatis nama teknisi
            $validated['technician_approve'] = true;
        }
        $report = ServiceReport::create($validated);
        return response()->json($report, 201);
    }

    /**
     * DELETE /api/work-orders/{workOrder}/service-report
     * Hapus service report berdasarkan work order id
     */
    public function destroy($workOrderId)
    {
        $report = ServiceReport::where('work_order_id', $workOrderId)->first();
        if (!$report) {
            return response()->json(['message' => 'Service report tidak ditemukan'], 404);
        }
        $report->delete();
        return response()->json(['message' => 'Service report berhasil dihapus']);
    }

    /**
     * Public: Tampilkan halaman service report berdasarkan token
     */
    public function publicShow($token)
    {
        $report = ServiceReport::where('signature_token', $token)->firstOrFail();
        return view('public-service-report', compact('report'));
    }

    /**
     * Public: Proses upload tanda tangan customer
     */
    public function publicSign(Request $request, $token)
    {
        $report = ServiceReport::where('signature_token', $token)->firstOrFail();
        $validated = $request->validate([
            'client_signature' => 'required|string', // base64 string dari canvas
            'client_signature_name' => 'required|string',
        ]);
        // Jika signature berupa data:image/png;base64
        if (str_starts_with($validated['client_signature'], 'data:image')) {
            $data = $validated['client_signature'];
            $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $data = str_replace(' ', '+', $data);
            $filename = 'service-report-signatures/client-signature-' . uniqid() . '.png';
            Storage::disk('public')->put($filename, base64_decode($data));
            $report->client_signature = $filename;
        }
        $report->client_signature_name = $validated['client_signature_name'];
        $report->client_approve = true;
        $report->save();
        return redirect()->back()->with('success', 'Tanda tangan berhasil diupload!');
    }

    /**
     * Public: Proses upload tanda tangan teknisi
     */
    public function publicSignTechnician(Request $request, $token)
    {
        $report = ServiceReport::where('signature_token', $token)->firstOrFail();
        $validated = $request->validate([
            'technician_signature' => 'required|string', // base64 string dari canvas
            'technician_signature_name' => 'required|string',
        ]);
        // Jika signature berupa data:image/png;base64
        if (str_starts_with($validated['technician_signature'], 'data:image')) {
            $data = $validated['technician_signature'];
            $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $data = str_replace(' ', '+', $data);
            $filename = 'service-report-signatures/technician-signature-' . uniqid() . '.png';
            Storage::disk('public')->put($filename, base64_decode($data));
            $report->technician_signature = $filename;
        }
        $report->technician_signature_name = $validated['technician_signature_name'];
        $report->technician_approve = true;
        $report->save();
        return redirect()->back()->with('success', 'Tanda tangan teknisi berhasil diupload!');
    }

    /**
     * Export/Preview PDF Service Report
     */
    public function exportPdf($workOrderId)
    {
        $report = ServiceReport::where('work_order_id', $workOrderId)->firstOrFail();
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
        $pdf = \PDF::loadView('pdf.service-report', [
            'report' => $report,
            'workOrder' => $workOrder,
            'surveyFields' => $surveyFields,
            'surveyAnswers' => $surveyAnswers,
        ]);
        return $pdf->stream('service-report-' . $workOrderId . '.pdf');
    }

    /**
     * Public: Preview PDF Service Report by token
     */
    public function publicPdf($token)
    {
        $report = ServiceReport::where('signature_token', $token)->firstOrFail();
        $workOrder = $report->workOrder()->with(['customer', 'assigned'])->first();
        $identificationSurvey = $workOrder->surveys()
            ->whereHas('surveyForm', function ($q) {
                $q->where('type', 'identification');
            })
            ->with('surveyForm')
            ->latest()
            ->first();
        $surveyFields = $identificationSurvey?->surveyForm?->fields ?? [];
        $surveyAnswers = $identificationSurvey?->answers ?? [];
        $pdf = \PDF::loadView('pdf.service-report', [
            'report' => $report,
            'workOrder' => $workOrder,
            'surveyFields' => $surveyFields,
            'surveyAnswers' => $surveyAnswers,
        ]);
        return $pdf->stream('service-report-' . $report->id . '-token.pdf');
    }
}
