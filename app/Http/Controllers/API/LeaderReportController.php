<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaderReport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaderReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $reports = LeaderReport::with(['leader', 'teknisi'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'periode_laporan' => 'required|date',
            'tanggal' => 'required|date',
            'leader_id' => 'required|exists:employees,id',
            'teknisi_id' => 'required|exists:employees,id',

            'kehadiran_tepat_waktu' => 'required|integer|min:0|max:10',
            'tidak_terlambat'       => 'required|integer|min:0|max:5',
            'izin_dengan_bukti'     => 'required|integer|min:0|max:5',

            'jumlah_lokasi'         => 'required|integer|min:0|max:15',
            'kecepatan_treatment'   => 'required|integer|min:0|max:10',
            'update_laporan'        => 'required|integer|min:0|max:5',

            'penggunaan_apd'        => 'required|integer|min:0|max:10',
            'foto_dokumentasi'      => 'required|integer|min:0|max:10',
            'rating_kepuasan'       => 'required|integer|min:0|max:10',

            'laporan_sesuai_sop'    => 'required|integer|min:0|max:5',
            'penggunaan_aplikasi'   => 'required|integer|min:0|max:5',

            'tidak_kehilangan_alat' => 'required|integer|min:0|max:5',
            'laporan_bahan_kimia'   => 'required|integer|min:0|max:5',

            'komentar_penilai'          => 'nullable',
            'rekomendasi_sanksi'        => 'nullable',
            'catatan_sanksi'            => 'nullable',
            'is_approved'               => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->all();
        if (is_string($data['rekomendasi_sanksi'])) {
            $data['rekomendasi_sanksi'] = json_decode($data['rekomendasi_sanksi'], true);
        }

        $report = LeaderReport::create(array_merge(
            $data,
            ['is_approved' => $request->input('is_approved', false)]
        ));

        return response()->json([
            'status' => 'success',
            'message' => 'Leader report created successfully',
            'data' => $report
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $report = LeaderReport::with(['leader', 'teknisi'])->find($id);

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leader report not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $report = LeaderReport::find($id);

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leader report not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'periode_laporan' => 'sometimes|required|date',
            'tanggal' => 'sometimes|required|date',
            'leader_id' => 'sometimes|required|exists:employees,id',
            'teknisi_id' => 'sometimes|required|exists:employees,id',
            
            'kehadiran_tepat_waktu' => 'sometimes|required|numeric|min:0|max:10',
            'tidak_terlambat' => 'sometimes|required|numeric|min:0|max:5',
            'izin_dengan_bukti' => 'sometimes|required|numeric|min:0|max:5',

            'jumlah_lokasi' => 'sometimes|required|numeric|min:0|max:15',
            'kecepatan_treatment' => 'sometimes|required|numeric|min:0|max:10',
            'update_laporan' => 'sometimes|required|numeric|min:0|max:5',

            'penggunaan_apd' => 'sometimes|required|numeric|min:0|max:10',
            'foto_dokumentasi' => 'sometimes|required|numeric|min:0|max:10',
            'rating_kepuasan' => 'sometimes|required|numeric|min:0|max:10',

            'laporan_sesuai_sop' => 'sometimes|required|numeric|min:0|max:5',
            'penggunaan_aplikasi' => 'sometimes|required|numeric|min:0|max:5',

            'tidak_kehilangan_alat' => 'sometimes|required|numeric|min:0|max:5',
            'laporan_bahan_kimia' => 'sometimes|required|numeric|min:0|max:5',

            'komentar_penilai' => 'nullable|string',
            'rekomendasi_sanksi' => 'nullable|string',
            'catatan_sanksi' => 'nullable|string',
            'keterlambatan_detail' => 'nullable|string',
            'peralatan_tidak_lengkap_detail' => 'nullable|string',
            'catatan_tambahan' => 'nullable|string',
            'is_approved' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->all();

        if (is_string($data['rekomendasi_sanksi'])) {
            $data['rekomendasi_sanksi'] = json_decode($data['rekomendasi_sanksi'], true);
        }

        $report->update($data);
        if ($request->has('is_approved')) {
            $report->is_approved = $request->input('is_approved');
            $report->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Leader report updated successfully',
            'data' => $report
        ]);
    }
    /**
     * Approve a leader report
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $report = LeaderReport::find($id);
        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leader report not found'
            ], 404);
        }

        // Only allow approval if not already approved
        if ($report->is_approved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Report is already approved'
            ], 422);
        }

        $report->is_approved = true;
        $report->approved_by = auth()->id();
        $report->approved_at = now();
        $report->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Leader report approved successfully',
            'data' => $report->load(['approvedBy'])
        ]);
    }

    /**
     * Revoke approval from a leader report
     */
    public function revokeApproval(Request $request, $id): JsonResponse
    {
        $report = LeaderReport::find($id);
        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leader report not found'
            ], 404);
        }

        if (!$report->is_approved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Report is not approved'
            ], 422);
        }

        $report->is_approved = false;
        $report->approved_by = null;
        $report->approved_at = null;
        $report->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Leader report approval revoked successfully',
            'data' => $report
        ]);
    }

    /**
     * Reject a leader report
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $report = LeaderReport::find($id);
        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leader report not found'
            ], 404);
        }

        // Cannot reject if already rejected
        if ($report->is_rejected) {
            return response()->json([
                'status' => 'error',
                'message' => 'Report is already rejected'
            ], 422);
        }

        // If report was approved, remove approval
        if ($report->is_approved) {
            $report->is_approved = false;
            $report->approved_by = null;
            $report->approved_at = null;
        }

        $report->is_rejected = true;
        $report->rejection_reason = $request->rejection_reason;
        $report->rejected_by = auth()->id();
        $report->rejected_at = now();
        $report->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Leader report rejected successfully',
            'data' => $report->load(['rejectedBy'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $report = LeaderReport::find($id);

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leader report not found'
            ], 404);
        }

        $report->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Leader report deleted successfully'
        ]);
    }

    /**
     * Get all options needed for the form
     */
    public function options(): JsonResponse
    {
        $employees = \App\Models\Employee::select('id', 'name')
            ->orderBy('name')
            ->get();

        $leaders = $employees->where('position.name', 'Leader');
        $teknisi = $employees->where('position.name', 'Teknisi');

        return response()->json([
            'status' => 'success',
            'data' => [
                'leaders' => $leaders->values(),
                'teknisi' => $teknisi->values(),
                'penilaian_skor' => [
                    ['id' => 1, 'label' => '1'],
                    ['id' => 2, 'label' => '2'],
                    ['id' => 3, 'label' => '3'],
                    ['id' => 4, 'label' => '4'],
                    ['id' => 5, 'label' => '5'],
                ],
                'boolean_options' => [
                    ['id' => true, 'label' => 'Ya'],
                    ['id' => false, 'label' => 'Tidak'],
                ],
            ]
        ]);
    }
    public function getEmployees(Request $request): JsonResponse
    {
        $query = Employee::query()
            ->with('position'); // Eager load position relationship

        if ($request->has('type')) {
            if ($request->input('type') === 'leader') {
                $query->whereHas('position', function ($q) {
                    $q->where('is_leader', true);
                });
            } elseif ($request->input('type') === 'teknisi') {
                $query->whereHas('position', function ($q) {
                    $q->where('is_leader', false);
                });
            }
        }

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'ilike', '%' . $searchTerm . '%');
        }

        $employees = $query->select('id', 'name', 'position_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Employees retrieved successfully',
            'data' => $employees
        ]);
    }
}
