<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Get all leave requests for authenticated employee
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $query = Leave::with(['employee', 'approvedBy'])
            ->where('employee_id', $employee->id);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year if provided
        if ($request->has('year')) {
            $query->whereYear('start_date', $request->year);
        }

        // Filter by month if provided
        if ($request->has('month')) {
            $query->whereMonth('start_date', $request->month);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $leaves
        ]);
    }

    /**
     * Store a new leave request
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        // Validasi tanggal tidak boleh yang sudah lewat
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $today = Carbon::today();

        if ($startDate->lt($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai cuti tidak boleh tanggal yang sudah lewat.',
                'errors' => [
                    'start_date' => ['Tanggal mulai cuti tidak boleh tanggal yang sudah lewat.']
                ]
            ], 422);
        }

        // Validasi tidak boleh ada data pada tanggal yang sama
        $existingLeave = Leave::where('employee_id', $employee->id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingLeave) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada pengajuan cuti pada tanggal yang sama atau bertabrakan.',
                'errors' => [
                    'start_date' => ['Sudah ada pengajuan cuti pada tanggal yang sama atau bertabrakan.']
                ]
            ], 422);
        }

        // Create leave request
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type' => null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
            'request_date' => Carbon::today(),
            'approved_by' => null,
        ]);

        $leave->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dibuat.',
            'data' => $leave
        ], 201);
    }

    /**
     * Show specific leave request
     */
    public function show(Request $request, Leave $leave)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if leave belongs to authenticated employee
        if ($leave->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat data ini.'
            ], 403);
        }

        $leave->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => $leave
        ]);
    }

    /**
     * Update leave request (only if status is pending)
     */
    public function update(Request $request, Leave $leave)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if leave belongs to authenticated employee
        if ($leave->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah data ini.'
            ], 403);
        }

        // Check if leave can be updated (only pending status)
        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan cuti hanya dapat diubah jika status masih pending.'
            ], 422);
        }

        $validated = $request->validate([
            'leave_type' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date|after_or_equal:today',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'reason' => 'sometimes|required|string|max:500',
        ]);

        // Validasi tanggal tidak boleh yang sudah lewat
        if (isset($validated['start_date'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $today = Carbon::today();

            if ($startDate->lt($today)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal mulai cuti tidak boleh tanggal yang sudah lewat.',
                    'errors' => [
                        'start_date' => ['Tanggal mulai cuti tidak boleh tanggal yang sudah lewat.']
                    ]
                ], 422);
            }
        }

        // Validasi tidak boleh ada data pada tanggal yang sama (kecuali record yang sedang di-update)
        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $checkStartDate = $validated['start_date'] ?? $leave->start_date;
            $checkEndDate = $validated['end_date'] ?? $leave->end_date;

            $existingLeave = Leave::where('employee_id', $employee->id)
                ->where('id', '!=', $leave->id) // Exclude current record
                ->where(function ($query) use ($checkStartDate, $checkEndDate) {
                    $query->whereBetween('start_date', [$checkStartDate, $checkEndDate])
                        ->orWhereBetween('end_date', [$checkStartDate, $checkEndDate])
                        ->orWhere(function ($q) use ($checkStartDate, $checkEndDate) {
                            $q->where('start_date', '<=', $checkStartDate)
                                ->where('end_date', '>=', $checkEndDate);
                        });
                })
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingLeave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah ada pengajuan cuti pada tanggal yang sama atau bertabrakan.',
                    'errors' => [
                        'start_date' => ['Sudah ada pengajuan cuti pada tanggal yang sama atau bertabrakan.']
                    ]
                ], 422);
            }
        }

        $leave->update($validated);
        $leave->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil diperbarui.',
            'data' => $leave
        ]);
    }

    /**
     * Cancel leave request (only if status is pending)
     */
    public function destroy(Request $request, Leave $leave)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if leave belongs to authenticated employee
        if ($leave->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus data ini.'
            ], 403);
        }

        // Check if leave can be cancelled (only pending status)
        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan cuti hanya dapat dibatalkan jika status masih pending.'
            ], 422);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dibatalkan.'
        ]);
    }
}
