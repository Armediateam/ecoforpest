<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Employee;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    /**
     * Get all overtime requests for authenticated employee
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $query = Overtime::with(['employee', 'approvedBy'])
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

        $overtimes = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $overtimes
        ]);
    }

    /**
     * Store a new overtime request
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $validated = $request->validate([
            // 'date' => 'required|date|after_or_equal:today',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:500',
        ]);

        // Additional custom validation for time logic
        $startTime = Carbon::createFromFormat('H:i', $validated['start_time']);
        $endTime = Carbon::createFromFormat('H:i', $validated['end_time']);

        // Handle cross-midnight scenario: if end_time < start_time, it means next day
        if ($endTime->format('H:i') < $startTime->format('H:i')) {
            $endTime->addDay();
        }

        if ($endTime->lte($startTime)) {
            return response()->json([
                'success' => false,
                'message' => 'Waktu selesai harus lebih besar dari waktu mulai.',
                'errors' => [
                    'end_time' => ['Waktu selesai harus lebih besar dari waktu mulai.']
                ]
            ], 422);
        }

        // Validasi tanggal tidak boleh yang sudah lewat
        // $overtimeDate = Carbon::parse($validated['date']);
        // $today = Carbon::today();

        // if ($overtimeDate->lt($today)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Tanggal lembur tidak boleh tanggal yang sudah lewat.',
        //         'errors' => [
        //             'date' => ['Tanggal lembur tidak boleh tanggal yang sudah lewat.']
        //         ]
        //     ], 422);
        // }

        // Validasi tidak boleh ada data pada tanggal yang sama
        // $existingOvertime = Overtime::where('employee_id', $employee->id)
        //     ->where('date', $validated['date'])
        //     ->whereIn('status', ['pending', 'approved'])
        //     ->first();

        // if ($existingOvertime) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Sudah ada pengajuan lembur pada tanggal yang sama.',
        //         'errors' => [
        //             'date' => ['Sudah ada pengajuan lembur pada tanggal yang sama.']
        //         ]
        //     ], 422);
        // }

        // Validasi lembur harus diluar jam kerja shift
        $shiftValidation = $this->validateOvertimeAgainstShift($employee, $validated['date'], $validated['start_time'], $validated['end_time']);
        if ($shiftValidation) {
            return response()->json($shiftValidation, 422);
        }

        // Calculate duration in hours - create fresh objects for accurate calculation
        $startTimeCalc = Carbon::createFromFormat('H:i', $validated['start_time']);
        $endTimeCalc = Carbon::createFromFormat('H:i', $validated['end_time']);

        // Handle cross-midnight scenario for duration calculation
        if ($endTimeCalc->format('H:i') < $startTimeCalc->format('H:i')) {
            $endTimeCalc->addDay();
        }

        // Calculate duration and round to integer (database expects integer)
        $durationHours = (int) round(abs($endTimeCalc->diffInRealHours($startTimeCalc)));

        // Create overtime request
        $overtime = Overtime::create([
            'employee_id' => $employee->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration_hour' => $durationHours,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'request_date' => Carbon::today(),
            'approved_by' => null,
        ]);

        $overtime->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibuat.',
            'data' => $overtime
        ], 201);
    }

    /**
     * Show specific overtime request
     */
    public function show(Request $request, Overtime $overtime)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if overtime belongs to authenticated employee
        if ($overtime->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat data ini.'
            ], 403);
        }

        $overtime->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => $overtime
        ]);
    }

    /**
     * Update overtime request (only if status is pending)
     */
    public function update(Request $request, Overtime $overtime)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if overtime belongs to authenticated employee
        if ($overtime->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah data ini.'
            ], 403);
        }

        // Check if overtime can be updated (only pending status)
        if ($overtime->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan lembur hanya dapat diubah jika status masih pending.'
            ], 422);
        }

        $validated = $request->validate([
            'date' => 'sometimes|required|date|after_or_equal:today',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i',
            'reason' => 'sometimes|required|string|max:500',
        ]);

        // Additional custom validation for time logic if both times are provided
        if (isset($validated['start_time']) && isset($validated['end_time'])) {
            $startTime = Carbon::createFromFormat('H:i', $validated['start_time']);
            $endTime = Carbon::createFromFormat('H:i', $validated['end_time']);

            // Handle cross-midnight scenario: if end_time < start_time, it means next day
            if ($endTime->format('H:i') < $startTime->format('H:i')) {
                $endTime->addDay();
            }

            if ($endTime->lte($startTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waktu selesai harus lebih besar dari waktu mulai.',
                    'errors' => [
                        'end_time' => ['Waktu selesai harus lebih besar dari waktu mulai.']
                    ]
                ], 422);
            }
        } elseif (isset($validated['start_time']) || isset($validated['end_time'])) {
            // If only one time is provided, validate against existing data
            $checkStartTime = $validated['start_time'] ?? $overtime->start_time;
            $checkEndTime = $validated['end_time'] ?? $overtime->end_time;

            $startTime = Carbon::createFromFormat('H:i', $checkStartTime);
            $endTime = Carbon::createFromFormat('H:i', $checkEndTime);

            // Handle cross-midnight scenario: if end_time < start_time, it means next day
            if ($endTime->format('H:i') < $startTime->format('H:i')) {
                $endTime->addDay();
            }

            if ($endTime->lte($startTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waktu selesai harus lebih besar dari waktu mulai.',
                    'errors' => [
                        'end_time' => ['Waktu selesai harus lebih besar dari waktu mulai.']
                    ]
                ], 422);
            }
        }

        // Validasi tanggal tidak boleh yang sudah lewat
        if (isset($validated['date'])) {
            $overtimeDate = Carbon::parse($validated['date']);
            $today = Carbon::today();

            if ($overtimeDate->lt($today)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal lembur tidak boleh tanggal yang sudah lewat.',
                    'errors' => [
                        'date' => ['Tanggal lembur tidak boleh tanggal yang sudah lewat.']
                    ]
                ], 422);
            }

            // Validasi tidak boleh ada data pada tanggal yang sama (kecuali record yang sedang di-update)
            $existingOvertime = Overtime::where('employee_id', $employee->id)
                ->where('date', $validated['date'])
                ->where('id', '!=', $overtime->id) // Exclude current record
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingOvertime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah ada pengajuan lembur pada tanggal yang sama.',
                    'errors' => [
                        'date' => ['Sudah ada pengajuan lembur pada tanggal yang sama.']
                    ]
                ], 422);
            }
        }

        // Validasi lembur harus diluar jam kerja shift (jika ada perubahan waktu atau tanggal)
        if (isset($validated['date']) || isset($validated['start_time']) || isset($validated['end_time'])) {
            // Gunakan data yang di-update atau data existing
            $checkDate = $validated['date'] ?? $overtime->date;
            $checkStartTime = $validated['start_time'] ?? $overtime->start_time;
            $checkEndTime = $validated['end_time'] ?? $overtime->end_time;

            $shiftValidation = $this->validateOvertimeAgainstShift($employee, $checkDate, $checkStartTime, $checkEndTime);
            if ($shiftValidation) {
                return response()->json($shiftValidation, 422);
            }
        }

        // Recalculate duration if times are updated
        if (isset($validated['start_time']) || isset($validated['end_time'])) {
            $startTime = Carbon::createFromFormat('H:i', $validated['start_time'] ?? $overtime->start_time);
            $endTime = Carbon::createFromFormat('H:i', $validated['end_time'] ?? $overtime->end_time);

            // Handle cross-midnight scenario: if end_time < start_time, it means next day
            if ($endTime->format('H:i') < $startTime->format('H:i')) {
                $endTime->addDay();
            }

            // Calculate duration and round to integer (database expects integer)
            $validated['duration_hour'] = (int) round(abs($endTime->diffInRealHours($startTime)));
        }

        $overtime->update($validated);
        $overtime->load(['employee', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil diperbarui.',
            'data' => $overtime
        ]);
    }

    /**
     * Cancel overtime request (only if status is pending)
     */
    public function destroy(Request $request, Overtime $overtime)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        // Check if overtime belongs to authenticated employee
        if ($overtime->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus data ini.'
            ], 403);
        }

        // Check if overtime can be cancelled (only pending status)
        if ($overtime->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan lembur hanya dapat dibatalkan jika status masih pending.'
            ], 422);
        }

        $overtime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibatalkan.'
        ]);
    }

    /**
     * Validate that overtime is outside work shift hours
     */
    private function validateOvertimeAgainstShift(Employee $employee, string $date, string $startTime, string $endTime): ?array
    {
        $shiftService = new ShiftService();
        $effectiveShift = $shiftService->getEffectiveShiftForEmployee($employee);

        if (!$effectiveShift || !is_array($effectiveShift->workhour)) {
            return null; // No shift defined, no validation needed
        }

        $overtimeDate = Carbon::parse($date);
        $dayName = strtolower($overtimeDate->englishDayOfWeek);

        // Cari jam kerja untuk hari tersebut
        $workday = null;
        foreach ($effectiveShift->workhour as $wh) {
            if (isset($wh['day']) && $wh['day'] === $dayName) {
                $workday = $wh;
                break;
            }
        }

        // Jika tidak ada jadwal kerja di hari tersebut, lembur diizinkan
        if (!$workday || !isset($workday['start_time']) || !isset($workday['end_time'])) {
            return null;
        }

        $shiftStart = $workday['start_time'];
        $shiftEnd = $workday['end_time'];

        $overtimeStart = Carbon::createFromFormat('H:i', $startTime);
        $overtimeEnd = Carbon::createFromFormat('H:i', $endTime);
        $shiftStartTime = Carbon::createFromFormat('H:i', $shiftStart);
        $shiftEndTime = Carbon::createFromFormat('H:i', $shiftEnd);

        // Handle shift malam (jika shift end < shift start)
        if ($shiftEnd < $shiftStart) {
            // Shift malam: misal 18:00 - 04:00
            $shiftEndTime->addDay();

            // Handle jika overtime melewati tengah malam
            if ($overtimeEnd->format('H:i') < $overtimeStart->format('H:i')) {
                $overtimeEnd->addDay();
            }

            // Untuk shift malam, cek apakah lembur bertabrakan dengan jam kerja
            $isConflicting = false;

            // Cek konflik dengan jam kerja hari ini (18:00 - 23:59)
            if (($overtimeStart->format('H:i') >= $shiftStart && $overtimeStart->format('H:i') <= '23:59') ||
                ($overtimeEnd->format('H:i') >= $shiftStart && $overtimeEnd->format('H:i') <= '23:59' && !$overtimeEnd->gt(Carbon::createFromFormat('H:i', '23:59')))
            ) {
                $isConflicting = true;
            }

            // Cek konflik dengan jam kerja hari berikutnya (00:00 - 04:00)
            if (($overtimeStart->format('H:i') >= '00:00' && $overtimeStart->format('H:i') <= $shiftEnd) ||
                ($overtimeEnd->format('H:i') >= '00:00' && $overtimeEnd->format('H:i') <= $shiftEnd && $overtimeEnd->lt($shiftEndTime))
            ) {
                $isConflicting = true;
            }

            if ($isConflicting) {
                return [
                    'success' => false,
                    'message' => "Waktu lembur tidak boleh bertabrakan dengan jam kerja shift ({$shiftStart} - {$shiftEnd}).",
                    'errors' => [
                        'start_time' => ["Waktu lembur tidak boleh bertabrakan dengan jam kerja shift ({$shiftStart} - {$shiftEnd})."],
                        'end_time' => ["Waktu lembur tidak boleh bertabrakan dengan jam kerja shift ({$shiftStart} - {$shiftEnd})."]
                    ]
                ];
            }
        } else {
            // Shift normal: misal 08:00 - 17:00

            // Handle jika overtime melewati tengah malam
            if ($overtimeEnd->format('H:i') < $overtimeStart->format('H:i')) {
                $overtimeEnd->addDay();
            }

            // Cek apakah ada tumpang tindih dengan jam kerja
            $isConflicting = false;

            // Cek jika overtime start atau end berada dalam jam kerja
            if (($overtimeStart->format('H:i') >= $shiftStart && $overtimeStart->format('H:i') < $shiftEnd) ||
                ($overtimeEnd->format('H:i') > $shiftStart && $overtimeEnd->format('H:i') <= $shiftEnd && !$overtimeEnd->gt(Carbon::createFromFormat('H:i', $shiftEnd)))
            ) {
                $isConflicting = true;
            }

            // Cek jika overtime mencakup seluruh jam kerja
            if ($overtimeStart->format('H:i') <= $shiftStart && $overtimeEnd->format('H:i') >= $shiftEnd && !$overtimeEnd->gt(Carbon::createFromFormat('H:i', $shiftEnd))) {
                $isConflicting = true;
            }

            if ($isConflicting) {
                return [
                    'success' => false,
                    'message' => "Waktu lembur tidak boleh bertabrakan dengan jam kerja shift ({$shiftStart} - {$shiftEnd}).",
                    'errors' => [
                        'start_time' => ["Waktu lembur tidak boleh bertabrakan dengan jam kerja shift ({$shiftStart} - {$shiftEnd})."],
                        'end_time' => ["Waktu lembur tidak boleh bertabrakan dengan jam kerja shift ({$shiftStart} - {$shiftEnd})."]
                    ]
                ];
            }
        }

        return null; // No conflict found
    }
}
