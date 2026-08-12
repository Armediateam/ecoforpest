<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permit;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PermitController extends Controller
{
    /**
     * Get all permit requests for authenticated employee
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $query = Permit::with(['employee', 'approvedBy'])
            ->where('employee_id', $employee->id);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year if provided
        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }

        // Filter by month if provided
        if ($request->has('month')) {
            $query->whereMonth('date', $request->month);
        }

        $permits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $permits
        ]);
    }

    /**
     * Store a new permit request
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string|max:500',
        ]);

        // Validasi tanggal tidak boleh yang sudah lewat
        $permitDate = Carbon::parse($validated['date']);
        $today = Carbon::today();

        if ($permitDate->lt($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal izin tidak boleh tanggal yang sudah lewat.',
                'errors' => [
                    'date' => ['Tanggal izin tidak boleh tanggal yang sudah lewat.']
                ]
            ], 422);
        }

        // Validasi tidak boleh ada data pada tanggal yang sama
        $existingPermit = Permit::where('employee_id', $employee->id)
            ->where('date', $validated['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingPermit) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada pengajuan izin pada tanggal yang sama.',
                'errors' => [
                    'date' => ['Sudah ada pengajuan izin pada tanggal yang sama.']
                ]
            ], 422);
        }

        // Create permit request
        $permit = Permit::create([
            'employee_id' => $employee->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'],
            'status' => 'pending',
            'request_date' => Carbon::today(),
            'approved_by' => null,
        ]);

        $permit->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dibuat.',
            'data' => $permit
        ], 201);
    }

    /**
     * Show specific permit request
     */
    public function show(Request $request, Permit $permit)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if permit belongs to authenticated employee
        if ($permit->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat data ini.'
            ], 403);
        }

        $permit->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => $permit
        ]);
    }

    /**
     * Update permit request (only if status is pending)
     */
    public function update(Request $request, Permit $permit)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if permit belongs to authenticated employee
        if ($permit->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah data ini.'
            ], 403);
        }

        // Check if permit can be updated (only pending status)
        if ($permit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan izin hanya dapat diubah jika status masih pending.'
            ], 422);
        }

        $validated = $request->validate([
            'date' => 'sometimes|required|date|after_or_equal:today',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'reason' => 'sometimes|required|string|max:500',
        ]);

        // Validasi tanggal tidak boleh yang sudah lewat
        if (isset($validated['date'])) {
            $permitDate = Carbon::parse($validated['date']);
            $today = Carbon::today();

            if ($permitDate->lt($today)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal izin tidak boleh tanggal yang sudah lewat.',
                    'errors' => [
                        'date' => ['Tanggal izin tidak boleh tanggal yang sudah lewat.']
                    ]
                ], 422);
            }

            // Validasi tidak boleh ada data pada tanggal yang sama (kecuali record yang sedang di-update)
            $existingPermit = Permit::where('employee_id', $employee->id)
                ->where('date', $validated['date'])
                ->where('id', '!=', $permit->id) // Exclude current record
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingPermit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah ada pengajuan izin pada tanggal yang sama.',
                    'errors' => [
                        'date' => ['Sudah ada pengajuan izin pada tanggal yang sama.']
                    ]
                ], 422);
            }
        }

        $permit->update($validated);
        $permit->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil diperbarui.',
            'data' => $permit
        ]);
    }

    /**
     * Cancel permit request (only if status is pending)
     */
    public function destroy(Request $request, Permit $permit)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if permit belongs to authenticated employee
        if ($permit->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus data ini.'
            ], 403);
        }

        // Check if permit can be cancelled (only pending status)
        if ($permit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan izin hanya dapat dibatalkan jika status masih pending.'
            ], 422);
        }

        $permit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dibatalkan.'
        ]);
    }
}
