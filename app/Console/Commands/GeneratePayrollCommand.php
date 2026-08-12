<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMonthlyPayrollJob;
use App\Services\PayrollCalculatorService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GeneratePayrollCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payroll:generate 
                          {--month= : The month to generate payroll for (1-12)}
                          {--year= : The year to generate payroll for}
                          {--department= : Generate for specific department ID}
                          {--position= : Generate for specific position ID}
                          {--employees= : Comma-separated list of employee IDs}
                          {--force : Force generation even if payroll already exists}
                          {--sync : Run synchronously instead of queuing}
                          {--dry-run : Show what would be generated without actually creating records}
                          {--queue : Run as background job}';

    /**
     * The console command description.
     */
    protected $description = 'Generate monthly payroll for employees based on attendance and overtime data';

    /**
     * Execute the console command.
     */
    public function handle(PayrollCalculatorService $calculator): int
    {
        $this->info('🚀 Starting Payroll Generation...');

        try {
            // Parse options
            $options = $this->parseOptions();

            // Validate options
            if (!$this->validateOptions($options)) {
                return self::FAILURE;
            }

            // Show confirmation
            if (!$options['dry_run'] && !$this->confirmGeneration($options)) {
                $this->info('Payroll generation cancelled.');
                return self::SUCCESS;
            }

            // Handle dry-run
            if ($options['dry_run']) {
                return $this->runDryRun($options, $calculator);
            }

            // Generate payroll
            if ($this->option('sync')) {
                $this->runSynchronously($options);
            } elseif ($this->option('queue')) {
                $this->runAsynchronously($options);
            } else {
                $this->runSynchronously($options);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate payroll: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Parse command options
     */
    private function parseOptions(): array
    {
        $options = [];

        // Parse month and year
        $month = $this->option('month');
        $year = $this->option('year');

        if (!$month || !$year) {
            // Default to previous month
            $lastMonth = Carbon::now()->subMonth();
            $month = $lastMonth->month;
            $year = $lastMonth->year;
        }

        $options['month'] = (int) $month;
        $options['year'] = (int) $year;

        // Parse target filters
        if ($this->option('department')) {
            $options['department_id'] = (int) $this->option('department');
        }

        if ($this->option('position')) {
            $options['position_id'] = (int) $this->option('position');
        }

        if ($this->option('employees')) {
            $employeeIds = explode(',', $this->option('employees'));
            $options['employee_ids'] = array_map('intval', array_filter($employeeIds));
        }

        $options['force'] = $this->option('force');
        $options['dry_run'] = $this->option('dry-run');
        $options['queue'] = $this->option('queue');

        return $options;
    }

    /**
     * Validate options
     */
    private function validateOptions(array $options): bool
    {
        // Validate month
        if ($options['month'] < 1 || $options['month'] > 12) {
            $this->error('Invalid month. Must be between 1 and 12.');
            return false;
        }

        // Validate year
        if ($options['year'] < 2020 || $options['year'] > 2030) {
            $this->error('Invalid year. Must be between 2020 and 2030.');
            return false;
        }

        // Check for conflicting options
        $filters = ['department_id', 'position_id', 'employee_ids'];
        $activeFilters = array_filter($filters, fn($filter) => isset($options[$filter]));

        if (count($activeFilters) > 1) {
            $this->error('Cannot specify multiple target filters (department, position, employees) at once.');
            return false;
        }

        return true;
    }

    /**
     * Show confirmation dialog
     */
    private function confirmGeneration(array $options): bool
    {
        $period = Carbon::create($options['year'], $options['month'], 1)->format('F Y');

        $this->info("📅 Period: {$period}");

        if (isset($options['department_id'])) {
            $this->info("🏢 Target: Department ID {$options['department_id']}");
        } elseif (isset($options['position_id'])) {
            $this->info("👤 Target: Position ID {$options['position_id']}");
        } elseif (isset($options['employee_ids'])) {
            $count = count($options['employee_ids']);
            $this->info("👥 Target: {$count} specific employees");
        } else {
            $this->info("🌐 Target: All active employees");
        }

        if ($options['force']) {
            $this->warn("⚠️  Force mode enabled - existing payrolls will be overwritten");
        }

        return $this->confirm('Do you want to proceed with payroll generation?');
    }

    /**
     * Run payroll generation synchronously
     */
    private function runSynchronously(array $options): void
    {
        $this->info('Running payroll generation synchronously...');

        // Dispatch job synchronously
        $job = new GenerateMonthlyPayrollJob($options, auth()->id() ?? 1);
        $job->handle(
            app(\App\Services\PayrollGenerationService::class),
            app(\App\Services\PayrollCalculatorService::class)
        );

        $this->info('✅ Payroll generation completed successfully!');
    }

    /**
     * Run payroll generation asynchronously
     */
    private function runAsynchronously(array $options): void
    {
        $this->info('Queueing payroll generation job...');

        // Dispatch job to queue
        GenerateMonthlyPayrollJob::dispatch($options, auth()->id() ?? 1);

        $this->info('✅ Payroll generation job queued successfully!');
        $this->info('💡 You can monitor the job progress in the admin panel or logs.');
    }

    /**
     * Run dry-run to show what would be generated
     */
    private function runDryRun(array $options, PayrollCalculatorService $calculator): int
    {
        $this->info('🔍 DRY RUN - No records will be created');
        $this->info('================================================');

        $period = Carbon::create($options['year'], $options['month'], 1);
        $startDate = $period->copy()->startOfMonth()->toDateString();
        $endDate = $period->copy()->endOfMonth()->toDateString();

        $this->info("📅 Period: {$period->format('F Y')} ({$startDate} to {$endDate})");

        // Get employees based on filters
        $employees = $this->getTargetEmployees($options);

        if ($employees->isEmpty()) {
            $this->warn('❌ No employees found matching the criteria.');
            return self::SUCCESS;
        }

        $this->info("👥 Found {$employees->count()} employees to process:");
        $this->newLine();

        $totalSalary = 0;
        $processedCount = 0;

        foreach ($employees as $employee) {
            try {
                // Calculate payroll data
                $payrollData = $calculator->calculatePayrollData($employee, $startDate, $endDate);

                // Check if payroll already exists
                $existingPayroll = \App\Models\Payroll::where('employee_id', $employee->id)
                    ->where('period_start_date', $startDate)
                    ->where('period_end_date', $endDate)
                    ->exists();

                $status = $existingPayroll ? '⚠️  ALREADY EXISTS' : '✅ WOULD CREATE';
                
                $this->line("🧑‍💼 {$employee->name} ({$employee->id})");
                $this->line("   Status: {$status}");
                $this->line("   Work Days: {$payrollData['work_days']} | Leave: {$payrollData['leave_days']} | Absent: {$payrollData['absent_days']}");
                $this->line("   Overtime Hours: {$payrollData['overtime_hours']}");
                $this->line("   Final Salary: Rp " . number_format($payrollData['final_salary'], 0, ',', '.'));
                $this->newLine();

                $totalSalary += $payrollData['final_salary'];
                $processedCount++;

            } catch (\Exception $e) {
                $this->error("❌ Error calculating for {$employee->name}: {$e->getMessage()}");
            }
        }

        $this->info('================================================');
        $this->info("📊 SUMMARY:");
        $this->info("   Total Employees: {$employees->count()}");
        $this->info("   Successfully Calculated: {$processedCount}");
        $this->info("   Total Salary Cost: Rp " . number_format($totalSalary, 0, ',', '.'));
        $this->info('================================================');
        $this->info('💡 Use without --dry-run to actually create the payroll records.');

        return self::SUCCESS;
    }

    /**
     * Get target employees based on filters
     */
    private function getTargetEmployees(array $options): \Illuminate\Database\Eloquent\Collection
    {
        $query = \App\Models\Employee::where('status', 'active');

        if (isset($options['department_id'])) {
            $query->where('department_id', $options['department_id']);
        } elseif (isset($options['position_id'])) {
            $query->where('position_id', $options['position_id']);
        } elseif (isset($options['employee_ids'])) {
            $query->whereIn('id', $options['employee_ids']);
        }

        return $query->with(['department', 'position'])->get();
    }
}
