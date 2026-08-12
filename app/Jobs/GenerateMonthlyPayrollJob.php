<?php

namespace App\Jobs;

use App\Services\PayrollGenerationService;
use App\Services\PayrollCalculatorService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PayrollGenerationCompleted;
use Carbon\Carbon;

class GenerateMonthlyPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $options;
    protected int $generatedBy;

    /**
     * Create a new job instance.
     */
    public function __construct(array $options = [], int $generatedBy = 1)
    {
        $this->options = $options;
        $this->generatedBy = $generatedBy;

        // Set queue properties
        // $this->onQueue('payroll');
        // $this->timeout = 3600; // 1 hour timeout
    }

    /**
     * Execute the job.
     */
    public function handle(PayrollGenerationService $payrollService, PayrollCalculatorService $calculator): void
    {
        Log::info('Starting monthly payroll generation job', $this->options);

        try {
            // Determine the period
            $period = $this->determinePeriod($calculator);
            $startDate = $period['start_date'];
            $endDate = $period['end_date'];

            Log::info("Generating payroll for period: {$startDate} to {$endDate}");

            // Generate payroll based on options
            $results = $this->generatePayroll($payrollService, $startDate, $endDate);

            // Log results
            $this->logResults($results, $period);

            // Send notification to admin users
            $this->sendNotification($results, $period);

            Log::info('Monthly payroll generation job completed successfully');
        } catch (\Exception $e) {
            Log::error('Monthly payroll generation job failed: ' . $e->getMessage(), [
                'exception' => $e,
                'options' => $this->options
            ]);

            // Send failure notification
            $this->sendFailureNotification($e);

            throw $e;
        }
    }

    /**
     * Determine the payroll period
     */
    private function determinePeriod(PayrollCalculatorService $calculator): array
    {
        if (isset($this->options['month']) && isset($this->options['year'])) {
            return $calculator->getPayrollPeriod($this->options['month'], $this->options['year']);
        }

        // Default to previous month
        $lastMonth = Carbon::now()->subMonth();
        return $calculator->getPayrollPeriod($lastMonth->month, $lastMonth->year);
    }

    /**
     * Generate payroll based on options
     */
    private function generatePayroll(PayrollGenerationService $payrollService, string $startDate, string $endDate): array
    {
        if (isset($this->options['employee_ids'])) {
            // Generate for specific employees
            return $payrollService->generateBulkPayroll($this->options['employee_ids'], $startDate, $endDate, $this->generatedBy);
        }

        if (isset($this->options['department_id'])) {
            // Generate for specific department
            return $payrollService->generatePayrollByDepartment($this->options['department_id'], $startDate, $endDate, $this->generatedBy);
        }

        if (isset($this->options['position_id'])) {
            // Generate for specific position
            return $payrollService->generatePayrollByPosition($this->options['position_id'], $startDate, $endDate, $this->generatedBy);
        }

        // Default: Generate for all active employees
        return $payrollService->generateAllEmployeesPayroll($startDate, $endDate, $this->generatedBy);
    }

    /**
     * Log the results
     */
    private function logResults(array $results, array $period): void
    {
        Log::info('Payroll generation results', [
            'period' => $period['period_name'],
            'total_processed' => $results['total_processed'],
            'successful' => count($results['success']),
            'failed' => count($results['failed']),
            'skipped' => count($results['skipped']),
        ]);

        if (!empty($results['failed'])) {
            Log::warning('Some payroll generations failed', [
                'failed_employees' => $results['failed']
            ]);
        }
    }

    /**
     * Send success notification
     */
    private function sendNotification(array $results, array $period): void
    {
        try {
            // Get admin users
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'Super Admin', 'Admin Reguler']);
            })->get();

            $notificationData = [
                'title' => 'Payroll Generation Completed',
                'message' => "Monthly payroll for {$period['period_name']} has been generated.",
                'period' => $period,
                'results' => [
                    'total_processed' => $results['total_processed'],
                    'successful' => count($results['success']),
                    'failed' => count($results['failed']),
                    'skipped' => count($results['skipped']),
                ],
                'success_details' => $results['success'],
                'failed_details' => $results['failed'],
            ];

            foreach ($adminUsers as $admin) {
                $admin->notify(new PayrollGenerationCompleted($notificationData));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payroll generation notification: ' . $e->getMessage());
        }
    }

    /**
     * Send failure notification
     */
    private function sendFailureNotification(\Exception $exception): void
    {
        try {
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'Super Admin']);
            })->get();

            $notificationData = [
                'title' => 'Payroll Generation Failed',
                'message' => 'Monthly payroll generation has failed.',
                'error' => $exception->getMessage(),
                'options' => $this->options,
            ];

            foreach ($adminUsers as $admin) {
                $admin->notify(new PayrollGenerationCompleted($notificationData));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payroll generation failure notification: ' . $e->getMessage());
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('GenerateMonthlyPayrollJob failed permanently', [
            'exception' => $exception->getMessage(),
            'options' => $this->options,
        ]);

        $this->sendFailureNotification($exception);
    }
}
