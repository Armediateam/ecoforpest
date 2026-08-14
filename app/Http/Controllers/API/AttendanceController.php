<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLocations;
use App\Models\Holiday;
use App\Models\AccessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ShiftService;
use App\Services\ShiftTimeHelper;
use Carbon\Carbon;
use App\Models\Setting;

class AttendanceController extends Controller
{
    public function history(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);
        $shiftService = new ShiftService();
        $shift = $shiftService->getEffectiveShiftForEmployee($employee);

        // Ambil timezone dari Setting
        $timezone = Setting::where('key', 'timezone')->first()?->value ?? 'Asia/Jakarta';
        $month = $request->query('month', Carbon::now($timezone)->month);
        $year = $request->query('year', Carbon::now($timezone)->year);
        $now = Carbon::now($timezone);
        $isCurrentMonth = ($month == $now->month && $year == $now->year);
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $endDate = $isCurrentMonth ? $now->copy()->startOfDay() : Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->endOfMonth();

        $period = $startDate->daysUntil($endDate->copy()->addDay(false));
        $result = [];

        $attendanceSetting = $this->getAttendanceSetting();

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $attendance = Attendance::where('employee_id', $employee->id)->where('date', $dateStr)->first();
            $holiday = Holiday::isHoliday($dateStr);

            $shiftStart = null;
            $shiftEnd = null;
            $clockIn = $attendance?->clock_in ? Carbon::parse($attendance->clock_in, $timezone)->format('H:i') : null;
            $clockOut = $attendance?->clock_out ? Carbon::parse($attendance->clock_out, $timezone)->format('H:i') : null;
            $leaveType = null;
            $leaveReason = null;
            $isWorkday = false;

            // Cari jam masuk shift untuk hari ini
            $dayName = strtolower($date->englishDayOfWeek); // e.g. 'monday'
            if ($shift && is_array($shift->workhour)) {
                foreach ($shift->workhour as $wh) {
                    if (isset($wh['day']) && $wh['day'] === $dayName) {
                        $shiftStart = $wh['start_time'] ?? null;
                        $shiftEnd = $wh['end_time'] ?? null;
                        $isWorkday = true;
                        break;
                    }
                }
            }

            // Skip hari yang bukan workday (tidak ada dalam shift) - termasuk hari libur yang jatuh di hari non-workday
            if (!$isWorkday && !$attendance) {
                continue;
            }

            // Skip hari ini jika belum absen dan bukan workday (termasuk hari libur di non-workday)
            if ($date->isSameDay($now) && !$attendance && !$isWorkday) {
                continue;
            }

            // Skip hari ini jika belum absen dan bukan libur (untuk workday)
            if ($date->isSameDay($now) && !$attendance && !$holiday && $isWorkday) {
                continue;
            }

            // Support shift malam: jika shiftEnd < shiftStart, clock out bisa di hari berikutnya
            if ($shiftStart && $shiftEnd && $shiftEnd < $shiftStart && !$clockOut) {
                $nextDate = Carbon::parse($dateStr)->addDay()->toDateString();
                $nextAttendance = Attendance::where('employee_id', $employee->id)->where('date', $nextDate)->first();
                if ($nextAttendance && $nextAttendance->clock_out) {
                    $clockOut = Carbon::parse($nextAttendance->clock_out, $timezone)->format('H:i');
                }
            }

            // Penentuan status
            if ($holiday && $isWorkday) {
                $clockInStatus = 'Libur';
                $clockOutStatus = 'Libur';
                $leaveType = $holiday->type ?? null;
                $leaveReason = $holiday->name ?? null;
            } elseif ($attendance) {
                if ($shiftStart && $clockIn && $shiftEnd) {
                    $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftStart);
                    // $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftStart, $timezone);
                    $lateLimit = $shiftStartTime->copy()->addMinutes($attendanceSetting['late_clock_in']);
                    $clockInObj = Carbon::parse($attendance->clock_in, $timezone);
                    if ($clockInObj->lte($lateLimit)) {
                        $clockInStatus = 'Hadir';
                    } else {
                        $clockInStatus = 'Terlambat';
                    }
                } else {
                    $clockInStatus = 'Hadir'; // fallback jika tidak ada data shift
                }
                // Penentuan clockOutStatus
                if ($clockOut) {
                    if ($shiftEnd) {
                        $shiftEndTime = Carbon::parse($dateStr . ' ' . $shiftEnd, $timezone);
                        $earlyClockOut = $attendanceSetting['early_clock_out'] ?? 0;
                        // Untuk shift malam (shiftEnd < shiftStart), clock out bisa di hari berikutnya
                        if ($shiftStart && $shiftEnd < $shiftStart) {
                            $shiftEndTime = $shiftEndTime->addDay();
                        }
                        $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                        $clockOutObj = Carbon::parse($attendance->clock_out, $timezone);
                        if ($clockOutObj->lt($earliestClockOut)) {
                            $clockOutStatus = 'Early Clock Out';
                        } else {
                            $clockOutStatus = 'Sudah Clock Out';
                        }
                    } else {
                        $clockOutStatus = 'Sudah Clock Out';
                    }
                } else {
                    $clockOutStatus = 'Belum Clock Out';
                }
            } else {
                // Hanya untuk workday yang tidak ada attendance
                $clockInStatus = 'Tidak Hadir';
                $clockOutStatus = 'Tidak Hadir';
            }

            $result[] = [
                'date' => $dateStr,
                'clock_in_status' => $clockInStatus,
                'clock_out_status' => $clockOutStatus,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'shift_start' => $shiftStart,
                'shift_end' => $shiftEnd,
                'holiday' => $holiday?->name,
                'leave_type' => $leaveType,
                'leave_reason' => $leaveReason,
                'is_workday' => $isWorkday,
                'attendance_data' => $attendance,
            ];
        }

        // Urutkan hasil berdasarkan tanggal terbaru (descending)
        usort($result, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);
        $timezone = Setting::where('key', 'timezone')->first()?->value ?? 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $date = $now->copy();
        $dateStr = $date->toDateString();

        // Cek apakah sudah ada attendance untuk employee_id + date
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $dateStr)
            ->first();

        // Validasi: Tidak bisa clock in dua kali
        if ($attendance && !empty($attendance->clock_in) && empty($attendance->clock_out) && !$request->hasFile('image_clock_out')) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi clock in hari ini. Silakan clock out jika shift sudah selesai.',
            ], 422);
        }
        // Validasi: Tidak bisa clock out dua kali
        if ($attendance && !empty($attendance->clock_out) && $request->hasFile('image_clock_out')) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi clock out hari ini.',
            ], 422);
        }

        // Validasi: Tidak bisa clock out jika belum clock in
        if ($request->hasFile('image_clock_out') && (!$attendance || empty($attendance->clock_in))) {
            return response()->json([
                'message' => 'Anda belum melakukan clock in hari ini. Silakan clock in terlebih dahulu.',
            ], 422);
        }

        // Tentukan rules validasi dinamis
        if ($attendance && empty($attendance->clock_out)) {
            // Sudah clock in, clock out required
            $rules = [
                'notes' => 'nullable|string',
                'image_clock_in' => 'nullable|image',
                'image_clock_out' => 'required|image',
                'coordinate_clock_in' => 'nullable|array',
                'coordinate_clock_in.latitude' => 'nullable|numeric|between:-90,90',
                'coordinate_clock_in.longitude' => 'nullable|numeric|between:-180,180',
                'coordinate_clock_out' => 'required|array',
                'coordinate_clock_out.latitude' => 'required|numeric|between:-90,90',
                'coordinate_clock_out.longitude' => 'required|numeric|between:-180,180',
                'is_leave' => 'nullable|boolean',
                'leave_type' => 'nullable|string',
                'leave_reason' => 'nullable|string',
            ];
        } else {
            // Belum clock in, clock in required
            $rules = [
                'notes' => 'nullable|string',
                'image_clock_in' => 'required|image',
                'image_clock_out' => 'nullable|image',
                'coordinate_clock_in' => 'required|array',
                'coordinate_clock_in.latitude' => 'required|numeric|between:-90,90',
                'coordinate_clock_in.longitude' => 'required|numeric|between:-180,180',
                'coordinate_clock_out' => 'nullable|array',
                'coordinate_clock_out.latitude' => 'nullable|numeric|between:-90,90',
                'coordinate_clock_out.longitude' => 'nullable|numeric|between:-180,180',
                'is_leave' => 'nullable|boolean',
                'leave_type' => 'nullable|string',
                'leave_reason' => 'nullable|string',
            ];
        }

        $validated = $request->validate($rules);
        $shiftService = new ShiftService();
        $shift = $shiftService->getEffectiveShiftForEmployee($employee);
        $holiday = Holiday::isHoliday($dateStr);

        // Determine if it's a workday
        $dayName = strtolower($date->englishDayOfWeek); // e.g. 'monday'
        $isWorkday = false;
        if ($shift && is_array($shift->workhour)) {
            foreach ($shift->workhour as $wh) {
                if (isset($wh['day']) && $wh['day'] === $dayName) {
                    $isWorkday = true;
                    break;
                }
            }
        }

        // Cek access request untuk hari libur atau non-workday
        if ($holiday || !$isWorkday) {
            $accessRequest = AccessRequest::where('employee_id', $employee->id)
                ->where('request_date', $dateStr)
                ->where('status', 'approved')
                ->first();

            if (!$accessRequest) {
                $requestType = $holiday ? 'hari libur' : 'di luar jadwal shift';
                return response()->json([
                    'message' => "Tidak dapat melakukan absensi pada {$requestType}. Silakan ajukan request akses terlebih dahulu.",
                    'holiday' => $holiday?->name,
                    'can_request_access' => true
                ], 422);
            }
        }

        // Handle image uploads
        if ($request->hasFile('image_clock_in')) {
            $path = $request->file('image_clock_in')->store('attendance/clock-in', 'public');
            $validated['image_clock_in'] = $path;
        }
        if ($request->hasFile('image_clock_out')) {
            $path = $request->file('image_clock_out')->store('attendance/clock-out', 'public');
            $validated['image_clock_out'] = $path;
        }

        // Format coordinates
        $validated['coordinate_clock_in'] = [
            'latitude' => $request->input('coordinate_clock_in.latitude'),
            'longitude' => $request->input('coordinate_clock_in.longitude')
        ];
        if ($request->has('coordinate_clock_out')) {
            $validated['coordinate_clock_out'] = [
                'latitude' => $request->input('coordinate_clock_out.latitude'),
                'longitude' => $request->input('coordinate_clock_out.longitude')
            ];
        }

        // Tentukan status otomatis
        $clockInStatus = null;
        $clockOutStatus = null;
        $isLeave = $validated['is_leave'] ?? false;
        if ($holiday) {
            $clockInStatus = 'Libur';
            $clockOutStatus = 'Libur';
            $isLeave = true;
            $validated['leave_type'] = $holiday->type;
            $validated['leave_reason'] = $holiday->name;
        }

        // Ambil waktu server
        $nowTime = $now->format('H:i');

        // Cari jam masuk shift untuk hari ini
        $dayName = strtolower($date->englishDayOfWeek); // e.g. 'monday'
        $shiftStart = null;
        $shiftEnd = null;
        if ($shift && is_array($shift->workhour)) {
            foreach ($shift->workhour as $wh) {
                if ((isset($wh['day']) && $wh['day'] === $dayName)) {
                    $shiftStart = $wh['start_time'] ?? null;
                    $shiftEnd = $wh['end_time'] ?? null;
                    break;
                }
            }
        }

        // Validasi clock in terlalu awal
        $attendanceSetting = $this->getAttendanceSetting();
        if (!$request->hasFile('image_clock_out') && $shiftStart) {
            $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftStart, $timezone);
            $earliestClockIn = $shiftStartTime->copy()->subMinutes($attendanceSetting['early_clock_in']);
            if ($now->lt($earliestClockIn)) {
                return response()->json([
                    'message' => 'Terlalu awal untuk clock in. Clock in hanya diperbolehkan mulai ' . $earliestClockIn->format('H:i') . '.',
                ], 422);
            }
        }

        // Validasi clock out terlalu awal
        if ($request->hasFile('image_clock_out') && $shiftEnd) {
            $earlyClockOut = $attendanceSetting['early_clock_out'] ?? 0;
            if ($earlyClockOut > 0) {
                $shiftEndTime = Carbon::parse($dateStr . ' ' . $shiftEnd, $timezone);
                $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                // Untuk shift malam (shiftEnd < shiftStart), clock out bisa di hari berikutnya
                if ($shiftStart && $shiftEnd < $shiftStart) {
                    $shiftEndTime = $shiftEndTime->addDay();
                    $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                }
                if ($now->lt($earliestClockOut)) {
                    return response()->json([
                        'message' => 'Terlalu awal untuk clock out. Clock out hanya diperbolehkan mulai ' . $earliestClockOut->format('H:i') . '.',
                    ], 422);
                }
            }
        }

        // Cek apakah sudah ada attendance untuk employee_id + date
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $dateStr)
            ->first();

        // Support shift malam: jika clock out, dan shiftEnd < shiftStart, serta jam sekarang < shiftStart, update attendance hari sebelumnya
        if ($attendance && empty($attendance->clock_out)) {
            $isOvernight = $shiftStart && $shiftEnd && $shiftEnd < $shiftStart;
            if ($isOvernight && $nowTime < $shiftStart) {
                // Update attendance hari sebelumnya
                $prevDate = $date->copy()->subDay()->toDateString();
                $prevAttendance = Attendance::where('employee_id', $employee->id)
                    ->where('date', $prevDate)
                    ->first();
                if ($prevAttendance && empty($prevAttendance->clock_out)) {
                    $prevAttendance->clock_out = $nowTime;
                    if (isset($validated['image_clock_out'])) {
                        $prevAttendance->image_clock_out = $validated['image_clock_out'];
                    }
                    if (isset($validated['coordinate_clock_out'])) {
                        $prevAttendance->coordinate_clock_out = $validated['coordinate_clock_out'];
                    }
                    // Calculate workhours for overnight shift
                    if ($prevAttendance->clock_in && $prevAttendance->clock_out) {
                        $clockIn = Carbon::parse($prevAttendance->clock_in, $timezone);
                        $clockOut = Carbon::parse($prevAttendance->clock_out, $timezone);
                        $prevAttendance->workhours = $clockOut->floatDiffInHours($clockIn);
                        // Set clock_out_status
                        if ($shiftEnd) {
                            $shiftEndTime = Carbon::parse($prevDate . ' ' . $shiftEnd, $timezone);
                            $earlyClockOut = $attendanceSetting['early_clock_out'] ?? 0;
                            if ($shiftStart && $shiftEnd < $shiftStart) {
                                $shiftEndTime = $shiftEndTime->addDay();
                            }
                            $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                            if ($clockOut->lt($earliestClockOut)) {
                                $prevAttendance->clock_out_status = 'Early Clock Out';
                            } else {
                                $prevAttendance->clock_out_status = 'Sudah Clock Out';
                            }
                        } else {
                            $prevAttendance->clock_out_status = 'Sudah Clock Out';
                        }
                    }
                    $prevAttendance->save();
                    $this->syncEmployeeLocationFromAttendance($employee, $validated['coordinate_clock_out'] ?? null, 'attendance_clock_out');
                    return response()->json($prevAttendance);
                }
            }
            // Default: update attendance hari ini
            $attendance->clock_out = $nowTime;
            if (isset($validated['image_clock_out'])) {
                $attendance->image_clock_out = $validated['image_clock_out'];
            }
            if (isset($validated['coordinate_clock_out'])) {
                $attendance->coordinate_clock_out = $validated['coordinate_clock_out'];
            }
            // Calculate workhours for normal shift
            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in, $timezone);
                $clockOut = Carbon::parse($attendance->clock_out, $timezone);
                $attendance->workhours = $clockOut->floatDiffInHours($clockIn);
                // Set clock_out_status
                if ($shiftEnd) {
                    $shiftEndTime = Carbon::parse($dateStr . ' ' . $shiftEnd, $timezone);
                    $earlyClockOut = $attendanceSetting['early_clock_out'] ?? 0;
                    if ($shiftStart && $shiftEnd < $shiftStart) {
                        $shiftEndTime = $shiftEndTime->addDay();
                    }
                    $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                    if ($clockOut->lt($earliestClockOut)) {
                        $attendance->clock_out_status = 'Early Clock Out';
                    } else {
                        $attendance->clock_out_status = 'Sudah Clock Out';
                    }
                } else {
                    $attendance->clock_out_status = 'Sudah Clock Out';
                }
            }
            $attendance->save();
            $this->syncEmployeeLocationFromAttendance($employee, $validated['coordinate_clock_out'] ?? null, 'attendance_clock_out');
            return response()->json($attendance);
        } else {
            // Pastikan tidak membuat data baru jika sudah ada attendance hari ini
            if ($attendance) {
                return response()->json([
                    'message' => 'Data absensi hari ini sudah ada.',
                ], 422);
            }
            // clock_in pakai waktu server
            $validated['clock_in'] = $now->format('Y-m-d H:i:s');
            $validated['clock_out'] = null;
            // Cari jam masuk shift untuk status hadir/terlambat
            if ($shiftStart && $shiftEnd) {
                $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftStart, $timezone);
                $lateLimit = $shiftStartTime->copy()->addMinutes($attendanceSetting['late_clock_in']);
                $nowTimeObj = Carbon::parse($now->format('Y-m-d H:i:s'), $timezone);
                if ($nowTimeObj->lte($lateLimit)) {
                    $clockInStatus = 'Hadir';
                } else {
                    $clockInStatus = 'Terlambat';
                }
            } else {
                $clockInStatus = 'Hadir';
            }
            $clockOutStatus = 'Belum Clock Out';
            $validated['clock_in_status'] = $clockInStatus;
            $validated['clock_out_status'] = $clockOutStatus;
            $validated['is_leave'] = $isLeave;
            $validated['employee_id'] = $employee->id;
            $validated['date'] = $dateStr;
            $attendance = Attendance::create($validated);
            $this->syncEmployeeLocationFromAttendance($employee, $validated['coordinate_clock_in'] ?? null, 'attendance_clock_in');
            return response()->json($attendance);
        }
    }

    public function today(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);
        $timezone = Setting::where('key', 'timezone')->first()?->value ?? 'Asia/Jakarta';
        $shiftService = new ShiftService();
        $shift = $shiftService->getEffectiveShiftForEmployee($employee);

        $today = Carbon::now($timezone);
        $date = $today->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $date)->first();
        $holiday = Holiday::isHoliday($date);

        $shiftStart = null;
        $shiftEnd = null;
        $clockIn = $attendance?->clock_in ? Carbon::parse($attendance->clock_in, $timezone)->format('H:i') : null;
        $clockOut = $attendance?->clock_out ? Carbon::parse($attendance->clock_out, $timezone)->format('H:i') : null;

        $clockInStatus = null;
        $clockOutStatus = null;

        // Cari jam masuk shift untuk hari ini
        $dayName = strtolower($today->englishDayOfWeek); // e.g. 'monday'
        $isWorkday = false;
        if ($shift && is_array($shift->workhour)) {
            foreach ($shift->workhour as $wh) {
                if (isset($wh['day']) && $wh['day'] === $dayName) {
                    $shiftStart = $wh['start_time'] ?? null;
                    $shiftEnd = $wh['end_time'] ?? null;
                    $isWorkday = true;
                    break;
                }
            }
        }

        // Support shift malam: jika shiftEnd < shiftStart, clock out bisa di hari berikutnya
        if ($shiftStart && $shiftEnd && $shiftEnd < $shiftStart && !$clockOut) {
            $prevDate = Carbon::parse($date)->subDay()->toDateString();
            $prevAttendance = Attendance::where('employee_id', $employee->id)->where('date', $prevDate)->first();
            if ($prevAttendance && $prevAttendance->clock_out) {
                $clockOut = Carbon::parse($prevAttendance->clock_out, $timezone)->format('H:i');
            }
        }

        $now = Carbon::now($timezone);
        $attendanceSetting = $this->getAttendanceSetting();

        // Cek access request untuk hari libur atau non-workday
        $accessRequest = null;
        $canRequestAccess = false;
        if ($holiday || !$isWorkday) {
            $accessRequest = AccessRequest::where('employee_id', $employee->id)
                ->where('request_date', $date)
                ->first();

            // Jika belum ada request, bisa request
            if (!$accessRequest) {
                $canRequestAccess = true;
            }
        }

        // Penentuan status baru
        if ($holiday && $isWorkday) {
            // Cek apakah ada access request yang approved
            if ($accessRequest && $accessRequest->status === 'approved') {
                // Jika ada access request approved, status seperti workday normal dengan prefix "Akses Disetujui"
                if ($shiftStart) {
                    $shiftStartTime = Carbon::parse($date . ' ' . $shiftStart, $timezone);
                    if ($now->lt($shiftStartTime)) {
                        $clockInStatus = 'Akses Disetujui - Belum Mulai Shift';
                        $clockOutStatus = 'Akses Disetujui - Belum Mulai Shift';
                    } elseif (!$attendance) {
                        $clockInStatus = 'Akses Disetujui - Belum Absen';
                        $clockOutStatus = 'Akses Disetujui - Belum Absen';
                    } else {
                        // Status normal seperti workday dengan prefix
                        if ($shiftStart && $clockIn && $shiftEnd) {
                            $shiftStartTime = Carbon::parse($date . ' ' . $shiftStart, $timezone);
                            $lateLimit = $shiftStartTime->copy()->addMinutes($attendanceSetting['late_clock_in']);
                            $clockInObj = Carbon::parse($clockIn, $timezone);
                            if ($clockInObj->lte($lateLimit)) {
                                $clockInStatus = 'Akses Disetujui - Hadir';
                            } else {
                                $clockInStatus = 'Akses Disetujui - Terlambat';
                            }
                        } else {
                            $clockInStatus = 'Akses Disetujui - Hadir';
                        }

                        if ($clockOut) {
                            if ($shiftEnd) {
                                $shiftEndTime = Carbon::parse($date . ' ' . $shiftEnd, $timezone);
                                $earlyClockOut = $attendanceSetting['early_clock_out'] ?? 0;
                                if ($shiftStart && $shiftEnd < $shiftStart) {
                                    $shiftEndTime = $shiftEndTime->addDay();
                                }
                                $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                                $clockOutObj = Carbon::parse($clockOut, $timezone);
                                if ($clockOutObj->lt($earliestClockOut)) {
                                    $clockOutStatus = 'Akses Disetujui - Early Clock Out';
                                } else {
                                    $clockOutStatus = 'Akses Disetujui - Sudah Clock Out';
                                }
                            } else {
                                $clockOutStatus = 'Akses Disetujui - Sudah Clock Out';
                            }
                        } else {
                            $clockOutStatus = 'Akses Disetujui - Belum Clock Out';
                        }
                    }
                } else {
                    $clockInStatus = 'Akses Disetujui - Belum Absen';
                    $clockOutStatus = 'Akses Disetujui - Belum Absen';
                }
            } else {
                // Status libur normal atau waiting approval/rejected
                if ($accessRequest && $accessRequest->status === 'pending') {
                    $clockInStatus = 'Libur - Menunggu Persetujuan Akses';
                    $clockOutStatus = 'Libur - Menunggu Persetujuan Akses';
                } elseif ($accessRequest && $accessRequest->status === 'rejected') {
                    $clockInStatus = 'Libur - Akses Ditolak';
                    $clockOutStatus = 'Libur - Akses Ditolak';
                } else {
                    $clockInStatus = 'Libur';
                    $clockOutStatus = 'Libur';
                }
            }
        } elseif (!$isWorkday) {
            // Non-workday
            if ($accessRequest && $accessRequest->status === 'approved') {
                // Jika ada access request approved untuk non-workday dengan prefix "Akses Disetujui"
                if (!$attendance) {
                    $clockInStatus = 'Akses Disetujui - Belum Absen';
                    $clockOutStatus = 'Akses Disetujui - Belum Absen';
                } else {
                    $clockInStatus = 'Akses Disetujui - Hadir';
                    if ($clockOut) {
                        $clockOutStatus = 'Akses Disetujui - Sudah Clock Out';
                    } else {
                        $clockOutStatus = 'Akses Disetujui - Belum Clock Out';
                    }
                }
            } else {
                // Status non-workday normal atau waiting approval/rejected
                if ($accessRequest && $accessRequest->status === 'pending') {
                    $clockInStatus = 'Non-Workday - Menunggu Persetujuan Akses';
                    $clockOutStatus = 'Non-Workday - Menunggu Persetujuan Akses';
                } elseif ($accessRequest && $accessRequest->status === 'rejected') {
                    $clockInStatus = 'Non-Workday - Akses Ditolak';
                    $clockOutStatus = 'Non-Workday - Akses Ditolak';
                } else {
                    $clockInStatus = 'Tidak ada Shift';
                    $clockOutStatus = 'Tidak ada Shift';
                }
            }
        } elseif ($shiftStart) {
            // Parse shiftStart ke Carbon pada hari ini
            $shiftStartTime = null;
            if ($shiftStart) {
                $shiftStartTime = Carbon::parse($date . ' ' . $shiftStart, $timezone);
            }
            if ($shiftStartTime && $now->lt($shiftStartTime)) {
                $clockInStatus = 'Belum Mulai Shift';
                $clockOutStatus = 'Belum Mulai Shift';
            } elseif (!$attendance) {
                $clockInStatus = 'Belum Absen';
                $clockOutStatus = 'Belum Absen';
            } else {
                // Status Clock In
                if ($shiftStart && $clockIn && $shiftEnd) {
                    $shiftStartTime = Carbon::parse($date . ' ' . $shiftStart, $timezone);
                    $lateLimit = $shiftStartTime->copy()->addMinutes($attendanceSetting['late_clock_in']);
                    $clockInObj = Carbon::parse($clockIn, $timezone);
                    if ($clockInObj->lte($lateLimit)) {
                        $clockInStatus = 'Hadir';
                    } else {
                        $clockInStatus = 'Terlambat';
                    }
                } else {
                    $clockInStatus = 'Hadir'; // fallback jika tidak ada data shift
                }

                // Status Clock Out
                if ($clockOut) {
                    if ($shiftEnd) {
                        $shiftEndTime = Carbon::parse($date . ' ' . $shiftEnd, $timezone);
                        $earlyClockOut = $attendanceSetting['early_clock_out'] ?? 0;
                        if ($shiftStart && $shiftEnd < $shiftStart) {
                            $shiftEndTime = $shiftEndTime->addDay();
                        }
                        $earliestClockOut = $shiftEndTime->copy()->subMinutes($earlyClockOut);
                        $clockOutObj = Carbon::parse($clockOut, $timezone);
                        if ($clockOutObj->lt($earliestClockOut)) {
                            $clockOutStatus = 'Early Clock Out';
                        } else {
                            $clockOutStatus = 'Sudah Clock Out';
                        }
                    } else {
                        $clockOutStatus = 'Sudah Clock Out';
                    }
                } else {
                    $clockOutStatus = 'Belum Clock Out';
                }
            }
        } else {
            $clockInStatus = 'Tidak ada Shift';
            $clockOutStatus = 'Tidak ada Shift';
        }

        // Tambahan: early_clock_in, late_clock_in, early_clock_out (format H:i)
        $earlyClockIn = null;
        $lateClockIn = null;
        $earlyClockOut = null;
        if ($shiftStart) {
            $shiftStartTime = Carbon::parse($date . ' ' . $shiftStart, $timezone);
            $earlyClockIn = $shiftStartTime->copy()->subMinutes($attendanceSetting['early_clock_in'])->format('H:i');
            $lateClockIn = $shiftStartTime->copy()->addMinutes($attendanceSetting['late_clock_in'])->format('H:i');
        }
        if ($shiftEnd && $shiftStart) {
            $shiftEndTime = Carbon::parse($date . ' ' . $shiftEnd, $timezone);
            if ($shiftEnd < $shiftStart) {
                $shiftEndTime = $shiftEndTime->addDay();
            }
            if (!empty($attendanceSetting['early_clock_out'])) {
                $earlyClockOut = $shiftEndTime->copy()->subMinutes($attendanceSetting['early_clock_out'])->format('H:i');
            } else {
                $earlyClockOut = null;
            }
        }

        return response()->json([
            // show date with timezone
            'date' => $today->format('Y-m-d H:i:s') . ' ' . $today->getTimezone(),
            'clock_in_status' => $clockInStatus,
            'clock_out_status' => $clockOutStatus,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'early_clock_in' => $earlyClockIn,
            'late_clock_in' => $lateClockIn,
            'early_clock_out' => $earlyClockOut,
            'holiday' => $holiday?->name,
            'attendance_data' => $attendance,
            'access_request' => $accessRequest,
            'can_request_access' => $canRequestAccess,
            'is_workday' => $isWorkday
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);
        $timezone = Setting::where('key', 'timezone')->first()?->value ?? 'Asia/Jakarta';
        $shiftService = new ShiftService();
        $shift = $shiftService->getEffectiveShiftForEmployee($employee);

        $month = $request->query('month', Carbon::now($timezone)->month);
        $year = Carbon::now($timezone)->year;
        $now = Carbon::now($timezone);
        $isCurrentMonth = ($month == $now->month && $year == $now->year);
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $endDate = $isCurrentMonth ? $now->copy()->startOfDay() : Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->endOfMonth();

        $period = $startDate->daysUntil($endDate->copy()->addDay(false));
        $summary = [
            'Hadir' => 0,
            'Terlambat' => 0,
            'Tidak Hadir' => 0,
            'Libur' => 0,
        ];

        $attendanceSetting = $this->getAttendanceSetting();

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $attendance = Attendance::where('employee_id', $employee->id)->where('date', $dateStr)->first();
            $holiday = Holiday::isHoliday($dateStr);

            $clockIn = $attendance?->clock_in ? Carbon::parse($attendance->clock_in, $timezone)->format('H:i') : null;
            $isWorkday = false;
            $shiftStart = null;
            $shiftEnd = null;

            $dayName = strtolower($date->englishDayOfWeek);
            if ($shift && is_array($shift->workhour)) {
                foreach ($shift->workhour as $wh) {
                    if (isset($wh['day']) && $wh['day'] === $dayName) {
                        $shiftStart = $wh['start_time'] ?? null;
                        $shiftEnd = $wh['end_time'] ?? null;
                        $isWorkday = true;
                        break;
                    }
                }
            }

            // Skip hari yang bukan workday (tidak ada dalam shift) - termasuk hari libur yang jatuh di hari non-workday
            if (!$isWorkday && !$attendance) {
                continue;
            }

            // Skip hari ini jika belum absen dan bukan workday (termasuk hari libur di non-workday)
            if ($date->isSameDay($now) && !$attendance && !$isWorkday) {
                continue;
            }

            // Skip hari ini jika belum absen dan bukan libur (untuk workday)
            if ($date->isSameDay($now) && !$attendance && !$holiday && $isWorkday) {
                continue;
            }

            if ($holiday && $isWorkday) {
                $clockInStatus = 'Libur';
            } elseif ($attendance) {
                if ($shiftStart && $clockIn && $shiftEnd) {
                    $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftStart);
                    // $shiftStartTime = Carbon::parse($dateStr . ' ' . $shiftStart, $timezone);
                    $lateLimit = $shiftStartTime->copy()->addMinutes($attendanceSetting['late_clock_in']);
                    $clockInObj = Carbon::parse($clockIn, $timezone);
                    if ($clockInObj->lte($lateLimit)) {
                        $clockInStatus = 'Hadir';
                    } else {
                        $clockInStatus = 'Terlambat';
                    }
                } else {
                    $clockInStatus = 'Hadir';
                }
            } else {
                // Hanya untuk workday yang tidak ada attendance
                $clockInStatus = 'Tidak Hadir';
            }

            if (isset($summary[$clockInStatus])) {
                $summary[$clockInStatus]++;
            }
        }

        return response()->json($summary);
    }

    public function request_access(Request $request)
    {
        $user = $request->user();
        $employee = Employee::findOrFail($user->id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $timezone = Setting::where('key', 'timezone')->first()?->value ?? 'Asia/Jakarta';
        $today = Carbon::now($timezone);
        $requestDate = $today->toDateString();
        $shiftService = new ShiftService();
        $shift = $shiftService->getEffectiveShiftForEmployee($employee);
        $holiday = Holiday::isHoliday($requestDate);

        // Check if date is holiday or non-workday
        $dayName = strtolower($today->englishDayOfWeek);
        $isWorkday = false;
        if ($shift && is_array($shift->workhour)) {
            foreach ($shift->workhour as $wh) {
                if (isset($wh['day']) && $wh['day'] === $dayName) {
                    $isWorkday = true;
                    break;
                }
            }
        }

        // Determine request type
        $requestType = null;
        if ($holiday) {
            $requestType = 'holiday';
        } elseif (!$isWorkday) {
            $requestType = 'non_workday';
        } else {
            return response()->json([
                'message' => 'Tidak perlu request akses untuk hari kerja normal.',
            ], 422);
        }

        // Check if request already exists
        $existingRequest = AccessRequest::where('employee_id', $employee->id)
            ->where('request_date', $requestDate)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'Request akses untuk tanggal ini sudah pernah diajukan.',
                'existing_request' => $existingRequest
            ], 422);
        }

        // Create access request using firstOrCreate to handle race conditions
        $accessRequest = AccessRequest::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'request_date' => $requestDate,
            ],
            [
                'request_type' => $requestType,
                'reason' => $validated['reason'],
                'status' => 'pending',
                'requested_at' => now()
            ]
        );

        // Check if this was an existing request (not newly created)
        if (!$accessRequest->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Request akses untuk tanggal ini sudah pernah diajukan.',
                'existing_request' => $accessRequest
            ], 422);
        }

        return response()->json([
            'message' => 'Request akses berhasil diajukan.',
            'access_request' => $accessRequest
        ]);
    }

    /**
     * Ambil setting attendance dari table setting (key: attendance, value: JSON)
     */
    private function getAttendanceSetting()
    {
        $setting = \App\Models\Setting::where('key', 'attendance')->first();
        $default = [
            'early_clock_in' => 120,
            'late_clock_in' => 10,
            'early_clock_out' => 15,
        ];
        if (!$setting || empty($setting->value)) return $default;
        $value = $setting->value;
        if (is_string($value)) {
            $json = json_decode($value, true);
        } else {
            $json = $value;
        }
        $merged = array_merge($default, $json ?: []);
        $merged['early_clock_in'] = (int) $merged['early_clock_in'];
        $merged['late_clock_in'] = (int) $merged['late_clock_in'];
        $merged['early_clock_out'] = (int) $merged['early_clock_out'];
        return $merged;
    }

    private function syncEmployeeLocationFromAttendance(Employee $employee, ?array $coordinates, string $source): void
    {
        if (
            empty($coordinates['latitude']) ||
            empty($coordinates['longitude'])
        ) {
            return;
        }

        EmployeeLocations::renewLocation([
            'employee_id' => $employee->id,
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'info' => [
                'source' => $source,
            ],
        ]);
    }
}
