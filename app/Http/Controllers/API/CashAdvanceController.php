<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CashAdvance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CashAdvanceController extends Controller
{
    public function index(): JsonResponse
    {
        $schedules = CashAdvance::where('employee_id', auth()->id())
            ->with(['user'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ]);
    }

    public function show($id): JsonResponse
    {
        $cashAdvance = CashAdvance::with(['user'])->where([
            'id' => $id,
            'employee_id' => auth()->id()
        ])->first();

        if (!$cashAdvance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cash Advance not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cashAdvance
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        $validated['date'] = Carbon::parse($validated['date']);
        $validated['status'] = 'pending';
        $validated['category'] = 'reimbursement';
        $validated['employee_id'] = auth()->id();

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('cash_advances', $filename, 'public');
            $validated['attachment'] = $path;
        }

        $cashAdvance = CashAdvance::create($validated);

        if (!$cashAdvance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Cash Advance'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cashAdvance
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cashAdvance = CashAdvance::where([
            'id' => $id,
            'employee_id' => auth()->id()
        ])->first();

        if (!$cashAdvance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cash Advance not found'
            ], 404);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        $validated['date'] = Carbon::parse($validated['date']);
        $validated['status'] = 'pending';
        $validated['category'] = 'reimbursement';

        // Handle file upload and delete old file if exists
        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($cashAdvance->attachment && Storage::disk('public')->exists($cashAdvance->attachment)) {
                Storage::disk('public')->delete($cashAdvance->attachment);
            }

            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('cash_advances', $filename, 'public');
            $validated['attachment'] = $path;
        }

        $cashAdvance->fill($validated);

        if (!$cashAdvance->save()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Cash Advance'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cashAdvance
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $cashAdvance = CashAdvance::where([
            'id' => $id,
            'employee_id' => auth()->id()
        ])->first();

        if (!$cashAdvance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cash Advance not found'
            ], 404);
        }

        // Delete attachment file if exists
        if ($cashAdvance->attachment && Storage::disk('public')->exists($cashAdvance->attachment)) {
            Storage::disk('public')->delete($cashAdvance->attachment);
        }

        if (!$cashAdvance->delete()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Cash Advance'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cash Advance deleted successfully'
        ]);
    }
}
