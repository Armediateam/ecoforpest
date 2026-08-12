<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PayrollGenerationService
{
    private PayrollCalculatorService $calculator;

    public function __construct(PayrollCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Generate payroll for a single employee
     */
    public function generateSinglePayroll(Employee $employee, string $startDate, string $endDate, int $generatedBy): ?Payroll
    {
        try {
            // Check if payroll already exists for this period
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('period_start_date', $startDate)
                ->where('period_end_date', $endDate)
                ->whereNull('deleted_at')
                ->first();

            if ($existingPayroll) {
                // Log::info("Payroll already exists for employee {$employee->name} for period {$startDate} to {$endDate}");
                // return $existingPayroll;
                // delete the existing payroll to regenerate
                // Log::info("Payroll already exists for employee {$employee->name} for period {$startDate} to {$endDate}");
                $existingPayroll->delete();
            }

            // Calculate payroll data
            $payrollData = $this->calculator->calculatePayrollData($employee, $startDate, $endDate);

            // Create payroll record
            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'period_start_date' => $startDate,
                'period_end_date' => $endDate,
                'work_days' => $payrollData['work_days'],
                'leave_days' => $payrollData['leave_days'],
                'permission_days' => $payrollData['permission_days'],
                'absent_days' => $payrollData['absent_days'],
                'overtime_hours' => $payrollData['overtime_hours'],
                'employee_income' => $payrollData['employee_income'],
                'employee_expense' => $payrollData['employee_expense'],
                'final_salary' => round($payrollData['final_salary']),
                'generated_by' => $generatedBy,
                'generated_at' => now(),
            ]);

            Log::info("Payroll generated successfully for employee {$employee->name}");
            return $payroll;
        } catch (Exception $e) {
            Log::error("Failed to generate payroll for employee {$employee->name}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate payroll for multiple employees
     */
    public function generateBulkPayroll(array $employeeIds, string $startDate, string $endDate, int $generatedBy): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
            'total_processed' => 0,
        ];

        $employees = Employee::whereIn('id', $employeeIds)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();

        foreach ($employees as $employee) {
            $results['total_processed']++;

            try {
                $payroll = $this->generateSinglePayroll($employee, $startDate, $endDate, $generatedBy);

                if ($payroll) {
                    if ($payroll->wasRecentlyCreated) {
                        $results['success'][] = [
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->name,
                            'payroll_id' => $payroll->id,
                            'final_salary' => round($payroll->final_salary),
                        ];
                    } else {
                        $results['skipped'][] = [
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->name,
                            'reason' => 'Payroll already exists for this period',
                        ];
                    }
                } else {
                    $results['failed'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->name,
                        'reason' => 'Failed to calculate or create payroll',
                    ];
                }
            } catch (Exception $e) {
                $results['failed'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'reason' => $e->getMessage(),
                ];
                Log::error("Bulk payroll generation failed for employee {$employee->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Generate payroll for all active employees
     */
    public function generateAllEmployeesPayroll(string $startDate, string $endDate, int $generatedBy): array
    {
        $activeEmployees = $this->calculator->getActiveEmployees();
        $employeeIds = $activeEmployees->pluck('id')->toArray();

        return $this->generateBulkPayroll($employeeIds, $startDate, $endDate, $generatedBy);
    }

    /**
     * Generate payroll for employees by department
     */
    public function generatePayrollByDepartment(int $departmentId, string $startDate, string $endDate, int $generatedBy): array
    {
        $employees = Employee::whereHas('position.department', function ($query) use ($departmentId) {
            $query->where('id', $departmentId);
        })
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();

        return $this->generateBulkPayroll($employeeIds, $startDate, $endDate, $generatedBy);
    }

    /**
     * Generate payroll for employees by position
     */
    public function generatePayrollByPosition(int $positionId, string $startDate, string $endDate, int $generatedBy): array
    {
        $employees = Employee::where('position_id', $positionId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();

        return $this->generateBulkPayroll($employeeIds, $startDate, $endDate, $generatedBy);
    }

    /**
     * Validate payroll data before generation
     */
    public function validatePayrollGeneration(array $employeeIds, string $startDate, string $endDate): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Validate date format
        try {
            Carbon::parse($startDate);
            Carbon::parse($endDate);
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Invalid date format';
        }

        // Check if end date is after start date
        if (Carbon::parse($endDate)->lt(Carbon::parse($startDate))) {
            $validation['valid'] = false;
            $validation['errors'][] = 'End date must be after start date';
        }

        // Check if employees exist and are active
        $activeEmployees = Employee::whereIn('id', $employeeIds)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        if ($activeEmployees === 0) {
            $validation['valid'] = false;
            $validation['errors'][] = 'No active employees found';
        }

        // Check for existing payrolls
        $existingPayrolls = Payroll::whereIn('employee_id', $employeeIds)
            ->where('period_start_date', $startDate)
            ->where('period_end_date', $endDate)
            ->whereNull('deleted_at')
            ->count();

        if ($existingPayrolls > 0) {
            $validation['warnings'][] = "Found {$existingPayrolls} existing payroll(s) for this period. They will be skipped.";
        }

        return $validation;
    }

    /**
     * Get payroll generation summary for a period
     */
    public function getPayrollSummary(string $startDate, string $endDate): array
    {
        $payrolls = Payroll::whereBetween('period_start_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->with('employee')
            ->get();

        $totalEmployees = $payrolls->count();
        $totalSalary = $payrolls->sum('final_salary');
        $averageSalary = $totalEmployees > 0 ? $totalSalary / $totalEmployees : 0;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period_name' => Carbon::parse($startDate)->format('F Y'),
            ],
            'summary' => [
                'total_employees' => $totalEmployees,
                'total_salary' => $totalSalary,
                'average_salary' => round($averageSalary),
                'total_work_days' => $payrolls->sum('work_days'),
                'total_overtime_hours' => $payrolls->sum('overtime_hours'),
            ],
            'breakdown' => [
                'total_income' => $payrolls->sum(function ($payroll) {
                    return $payroll->employee_income['total_penghasilan'] ?? 0;
                }),
                'total_deductions' => $payrolls->sum(function ($payroll) {
                    return $payroll->employee_expense['total_potongan'] ?? 0;
                }),
            ],
        ];
    }
}
