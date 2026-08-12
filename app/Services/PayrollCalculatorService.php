<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollCalculatorService
{
    /**
     * Helper function to safely convert value to numeric
     */
    private function safeNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove common currency symbols and separators
            $cleaned = preg_replace('/[^\d.,\-]/', '', $value);
            // Replace comma with dot for decimal separator
            $cleaned = str_replace(',', '.', $cleaned);
            // Handle multiple dots (keep only the last one as decimal separator)
            $parts = explode('.', $cleaned);
            if (count($parts) > 2) {
                $cleaned = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
            }
            return is_numeric($cleaned) ? (float) $cleaned : 0;
        }

        return 0;
    }

    /**
     * Calculate payroll data for an employee for a specific period
     */
    public function calculatePayrollData(Employee $employee, string $startDate, string $endDate): array
    {
        $workDays = $this->calculateWorkDays($employee->id, $startDate, $endDate);
        $leaveDays = $this->calculateLeaveDays($employee->id, $startDate, $endDate);
        $permissionDays = $this->calculatePermissionDays($employee->id, $startDate, $endDate);
        $absentDays = $this->calculateAbsentDays($employee->id, $startDate, $endDate);
        $overtimeHours = $this->calculateOvertimeHours($employee->id, $startDate, $endDate);
        $totalWorkHours = $this->calculateTotalWorkHours($employee->id, $startDate, $endDate);
        $averageWorkHours = $this->calculateAverageWorkHours($employee->id, $startDate, $endDate);

        // INCOME: Selalu ambil penuh dari employee, overtime tetap dihitung
        $employeeIncome = $this->calculateEmployeeIncomeFull($employee, $overtimeHours);

        // DEDUCTION: Tambahkan potongan absen jika ada
        $employeeExpense = $this->calculateEmployeeExpenseWithAbsent($employee,  $employeeIncome, $workDays, $absentDays, $startDate, $endDate);
        $finalSalary = $employeeIncome['total_penghasilan'] - $employeeExpense['total_potongan'];

        return [
            'work_days' => $workDays,
            'leave_days' => $leaveDays,
            'permission_days' => $permissionDays,
            'absent_days' => $absentDays,
            'overtime_hours' => $overtimeHours,
            'total_work_hours' => $totalWorkHours,
            'average_work_hours' => $averageWorkHours,
            'employee_income' => $employeeIncome,
            'employee_expense' => $employeeExpense,
            'final_salary' => $finalSalary,
        ];
    }
    /**
     * Ambil income penuh dari employee, overtime tetap dihitung
     */
    private function calculateEmployeeIncomeFull(Employee $employee, int $overtimeHours): array
    {
        $baseIncome = $employee->employee_income ?? [];
        $parsedIncome = $this->parseIncomeExpenseData($baseIncome);

        if (empty($parsedIncome)) {
            $parsedIncome = [
                'gaji_pokok' => 0,
                'uang_makan' => 0,
                'uang_transport' => 0,
                'pulsa' => 0,
                'masa_kerja' => 0,
                'overtime' => 50000, // Default overtime rate per hour
            ];
        }

        // Ensure all values are numeric
        foreach ($parsedIncome as $key => $value) {
            $parsedIncome[$key] = $this->safeNumeric($value);
        }

        // TODO: Perhitungan overtime tapi belom fix, dia belom kasih tau rumusnya
        // ASUMSI: overtime dihitung berdasarkan jam lembur * rate lembur
        $overtimeRate = $parsedIncome['overtime'] ?? 0;
        $overtimePay = $overtimeHours * $overtimeRate;

        // Kalkulasi total penghasilan sebelum potongan
        $totalPenghasilan = $overtimePay;
        $result = [];
        foreach ($parsedIncome as $key => $value) {
            if ($key !== 'overtime') {
                $totalPenghasilan += $value;
                $result[$key] = round($value);
            }
        }
        $result['overtime'] = round($overtimePay);
        $result['total_penghasilan'] = round($totalPenghasilan);

        return $result;
    }

    /**
     * Tambahkan deduction absen jika ada
     */
    private function calculateEmployeeExpenseWithAbsent(Employee $employee, array $income, int $workDays, int $absentDays, string $startDate, string $endDate): array
    {
        $expenses = $this->calculateEmployeeExpense($employee, $income['total_penghasilan']);
        // Jika ada absen, tambahkan potongan absen
        if ($absentDays > 0) {
            // Hitung total hari dalam periode
            $totalDaysInPeriod = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            // Basis potongan: gaji_pokok + uang_makan + uang_transport
            $baseForDeduction = ($income['gaji_pokok'] ?? 0) + ($income['uang_makan'] ?? 0) + ($income['uang_transport'] ?? 0);
            // Nominal potongan absen: (baseForDeduction / totalDaysInPeriod) * absentDays
            // $potonganAbsen = round(($baseForDeduction / $totalDaysInPeriod) * $absentDays);
            $potonganAbsen = ($baseForDeduction / ($workDays + $absentDays)) * ($absentDays);
            $expenses['potongan_absen'] = round($potonganAbsen);
            $expenses['total_potongan'] = round(($expenses['total_potongan'] ?? 0) + $potonganAbsen);
        }
        return $expenses;
    }

    /**
     * Calculate work days from attendance data
     */
    private function calculateWorkDays(int $employeeId, string $startDate, string $endDate): int
    {
        return Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('clock_in_status', ['Hadir', 'Terlambat']) // Gunakan clock_in_status
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Calculate leave days from attendance data
     */
    private function calculateLeaveDays(int $employeeId, string $startDate, string $endDate): int
    {
        return Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_leave', true)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Calculate permission days from attendance data
     */
    private function calculatePermissionDays(int $employeeId, string $startDate, string $endDate): int
    {
        return Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('leave_type', 'permission')
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Calculate absent days from attendance records and shift schedules
     */
    private function calculateAbsentDays(int $employeeId, string $startDate, string $endDate): int
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return 0;
        }

        // Get the effective shift for this employee
        $effectiveShift = $employee->getEffectiveShift();

        if (!$effectiveShift || !isset($effectiveShift->workhour)) {
            // If no shift defined, fall back to simple absent count
            return Attendance::where('employee_id', $employeeId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('clock_in_status', 'Tidak Hadir')
                ->where('is_leave', false)
                ->whereNull('deleted_at')
                ->count();
        }

        $absentCount = 0;
        $shiftWorkdays = $effectiveShift->workhour; // Array of workdays from shift

        // Parse start and end dates
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Iterate through each date in the period
        $current = $start->copy();
        $today = Carbon::now()->toDateString();
        while ($current->lte($end) && $current->toDateString() <= $today) {
            $dayName = strtolower($current->format('l')); // monday, tuesday, etc.

            // Check if this day is a scheduled workday according to shift
            $isScheduledWorkday = collect($shiftWorkdays)->contains(function ($workday) use ($dayName) {
                return isset($workday['day']) && strtolower($workday['day']) === $dayName;
            });

            if ($isScheduledWorkday) {
                // Check if employee has attendance record for this date
                $attendance = Attendance::where('employee_id', $employeeId)
                    ->whereDate('date', $current)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$attendance) {
                    // No attendance record = absent
                    $absentCount++;
                } elseif ($attendance->clock_in_status === 'Tidak Hadir' && !$attendance->is_leave) {
                    // Explicitly marked as absent (not on leave)
                    $absentCount++;
                }
                // Note: 'Cuti' (leave) is not counted as absent since it's approved
            }

            $current->addDay();
        }

        return $absentCount;
    }

    /**
     * Calculate approved overtime hours - menggunakan duration_hour
     */
    private function calculateOvertimeHours(int $employeeId, string $startDate, string $endDate): int
    {
        return Overtime::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'approved') // lowercase sesuai kemungkinan data
            ->whereNull('deleted_at')
            ->sum('duration_hour') ?? 0;
    }

    /**
     * Calculate total work hours from attendance workhours column
     */
    private function calculateTotalWorkHours(int $employeeId, string $startDate, string $endDate): float
    {
        return Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('clock_in_status', ['Hadir', 'Terlambat'])
            ->whereNull('deleted_at')
            ->sum('workhours') ?? 0.0;
    }

    /**
     * Calculate average daily work hours
     */
    private function calculateAverageWorkHours(int $employeeId, string $startDate, string $endDate): float
    {
        $totalHours = $this->calculateTotalWorkHours($employeeId, $startDate, $endDate);
        $workDays = $this->calculateWorkDays($employeeId, $startDate, $endDate);

        return $workDays > 0 ? round($totalHours / $workDays, 2) : 0.0;
    }

    /**
     * Calculate employee income including overtime
     */
    private function calculateEmployeeIncome(Employee $employee, int $workDays, int $overtimeHours): array
    {
        // Get base income from employee's income data (JSONB field)
        $baseIncome = $employee->employee_income ?? [];

        // Convert repeater format to key-value format if needed
        $parsedIncome = $this->parseIncomeExpenseData($baseIncome);

        // If no income data in employee record, use default values
        if (empty($parsedIncome)) {
            $parsedIncome = [
                'gaji_pokok' => 0,
                'uang_makan' => 0,
                'uang_transport' => 0,
                'pulsa' => 0,
                'masa_kerja' => 0,
                'overtime' => 50000, // Default overtime rate per hour
            ];
        }

        // Extract income components with default values and ensure numeric
        $gajiPokok = $this->safeNumeric($parsedIncome['gaji_pokok'] ?? 0);
        $uangMakan = $this->safeNumeric($parsedIncome['uang_makan'] ?? 0);
        $uangTransport = $this->safeNumeric($parsedIncome['uang_transport'] ?? 0);
        $pulsa = $this->safeNumeric($parsedIncome['pulsa'] ?? 0);
        $masaKerja = $this->safeNumeric($parsedIncome['masa_kerja'] ?? 0);
        $overtimeRate = $this->safeNumeric($parsedIncome['overtime'] ?? 50000);

        // Calculate pro-rata based on work days (assuming 22 working days per month)
        $workingDaysInMonth = 22;
        $proRata = $workingDaysInMonth > 0 ? min($workDays / $workingDaysInMonth, 1) : 0;

        // Apply pro-rata calculation to monthly components
        $gajiPokokAdjusted = $gajiPokok * $proRata;
        $uangMakanAdjusted = $uangMakan * $proRata;
        $uangTransportAdjusted = $uangTransport * $proRata;
        $pulsaAdjusted = $pulsa * $proRata;
        $masaKerjaAdjusted = $masaKerja * $proRata;

        // Calculate overtime pay (not pro-rated, based on actual hours)
        $overtimePay = $overtimeHours * $overtimeRate;

        $totalPenghasilan = $gajiPokokAdjusted + $uangMakanAdjusted + $uangTransportAdjusted +
            $pulsaAdjusted + $masaKerjaAdjusted + $overtimePay;

        return [
            'gaji_pokok' => round($gajiPokokAdjusted),
            'uang_makan' => round($uangMakanAdjusted),
            'uang_transport' => round($uangTransportAdjusted),
            'pulsa' => round($pulsaAdjusted),
            'masa_kerja' => round($masaKerjaAdjusted),
            'overtime' => round($overtimePay),
            'total_penghasilan' => round($totalPenghasilan),
        ];
    }

    /**
     * Calculate employee expenses/deductions
     */
    private function calculateEmployeeExpense(Employee $employee, int $totalIncome): array
    {
        // Get base expense from employee's expense data (JSONB field)
        $baseExpense = $employee->employee_expense ?? [];

        // Convert repeater format to key-value format if needed
        $parsedExpense = $this->parseIncomeExpenseData($baseExpense);

        // If no expense data in employee record, calculate default BPJS
        if (empty($parsedExpense)) {
            // Default BPJS rates (example rates - adjust according to actual rates)
            $bpjsKetenagakerjaanRate = 0; // 2%
            // $bpjsKetenagakerjaanRate = 0.02; // 2%
            // $bpjsKesehatanRate = 0.01; // 1%
            $bpjsKesehatanRate = 0; // 1%

            $bpjsKetenagakerjaan = $totalIncome * $bpjsKetenagakerjaanRate;
            $bpjsKesehatan = $totalIncome * $bpjsKesehatanRate;

            return [
                'bpjs_ketenagakerjaan' => round($bpjsKetenagakerjaan),
                'bpjs_kesehatan' => round($bpjsKesehatan),
                'total_potongan' => round($bpjsKetenagakerjaan + $bpjsKesehatan),
            ];
        }

        // Use expense data from employee record
        $totalPotongan = 0;
        $expenses = [];

        foreach ($parsedExpense as $key => $value) {
            if ($key !== 'total_potongan') {
                $numericValue = $this->safeNumeric($value);
                if ($numericValue > 0) {
                    $expenses[$key] = round($numericValue);
                    $totalPotongan += $numericValue;
                }
            }
        }

        // Add any percentage-based deductions if specified
        if (isset($parsedExpense['bpjs_ketenagakerjaan_rate'])) {
            $rate = $this->safeNumeric($parsedExpense['bpjs_ketenagakerjaan_rate']);
            $bpjsKetenagakerjaan = $totalIncome * ($rate / 100);
            $expenses['bpjs_ketenagakerjaan'] = round($bpjsKetenagakerjaan);
            $totalPotongan += $bpjsKetenagakerjaan;
        }

        if (isset($parsedExpense['bpjs_kesehatan_rate'])) {
            $rate = $this->safeNumeric($parsedExpense['bpjs_kesehatan_rate']);
            $bpjsKesehatan = $totalIncome * ($rate / 100);
            $expenses['bpjs_kesehatan'] = round($bpjsKesehatan);
            $totalPotongan += $bpjsKesehatan;
        }

        $expenses['total_potongan'] = round($totalPotongan);

        return $expenses;
    }

    /**
     * Parse income/expense data from either repeater format or key-value format
     */
    private function parseIncomeExpenseData(array $data): array
    {
        $parsed = [];

        // Check if data is in repeater format (array of objects)
        if (isset($data[0]) && is_array($data[0]) && isset($data[0]['name'], $data[0]['nominal'])) {
            // Convert repeater format to key-value
            foreach ($data as $item) {
                if (isset($item['name'], $item['nominal'])) {
                    $key = strtolower(str_replace(' ', '_', $item['name']));
                    $parsed[$key] = $this->safeNumeric($item['nominal']);
                }
            }
        } else {
            $parsed = [];

            foreach ($data as $item) {
                if (is_array($item) && isset($item['name'], $item['nominal'])) {
                    // Convert name to lowercase snake_case key
                    $key = strtolower(str_replace([' ', '-'], '_', trim($item['name'])));
                    // Convert nominal to numeric value
                    $parsed[$key] = $this->safeNumeric($item['nominal']);
                }
            }

            return $parsed;
        }

        return $parsed;
    }

    /**
     * Get all active employees for payroll generation
     */
    public function getActiveEmployees(): Collection
    {
        return Employee::where('status', 'active')
            ->whereNull('deleted_at')
            ->with(['position', 'shift'])
            ->get();
    }

    /**
     * Calculate payroll period (start and end date of current month)
     */
    public function getCurrentPayrollPeriod(): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'period_name' => $now->format('F Y')
        ];
    }

    /**
     * Calculate payroll period for specific month and year
     */
    public function getPayrollPeriod(int $month, int $year): array
    {
        $date = Carbon::create($year, $month, 1);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'period_name' => $date->format('F Y')
        ];
    }
}
