<?php

namespace App\Jobs;

use App\Services\PayrollGenerationService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Notifications\PayrollGenerationCompleted;

class GenerateBulkPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $employeeIds;
    protected string $startDate;
    protected string $endDate;
    protected int $generatedBy;
    protected ?int $notifyUserId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $employeeIds,
        string $startDate,
        string $endDate,
        int $generatedBy,
        ?int $notifyUserId = null
    ) {
        $this->employeeIds = $employeeIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->generatedBy = $generatedBy;
        $this->notifyUserId = $notifyUserId;

        // Set queue properties
        $this->onQueue('payroll');
        $this->timeout = 1800; // 30 minutes timeout
    }

    /**
     * Execute the job.
     */
    public function handle(PayrollGenerationService $payrollService): void
    {
        Log::info('Starting bulk payroll generation job', [
            'employee_count' => count($this->employeeIds),
            'period' => "{$this->startDate} to {$this->endDate}",
            'generated_by' => $this->generatedBy
        ]);

        try {
            // Generate payroll for specified employees
            $results = $payrollService->generateBulkPayroll(
                $this->employeeIds,
                $this->startDate,
                $this->endDate,
                $this->generatedBy
            );

            // Log results
            $this->logResults($results);

            // Send notification
            $this->sendNotification($results);

            Log::info('Bulk payroll generation job completed successfully', [
                'successful' => count($results['success']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped'])
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk payroll generation job failed: ' . $e->getMessage(), [
                'exception' => $e,
                'employee_ids' => $this->employeeIds
            ]);

            $this->sendFailureNotification($e);

            throw $e;
        }
    }

    /**
     * Log the results
     */
    private function logResults(array $results): void
    {
        Log::info('Bulk payroll generation results', [
            'period' => "{$this->startDate} to {$this->endDate}",
            'total_processed' => $results['total_processed'],
            'successful' => count($results['success']),
            'failed' => count($results['failed']),
            'skipped' => count($results['skipped']),
        ]);

        if (!empty($results['failed'])) {
            Log::warning('Some bulk payroll generations failed', [
                'failed_employees' => $results['failed']
            ]);
        }
    }

    /**
     * Send success notification
     */
    private function sendNotification(array $results): void
    {
        try {
            // Determine who to notify
            $users = collect();

            if ($this->notifyUserId) {
                $user = User::find($this->notifyUserId);
                if ($user) {
                    $users->push($user);
                }
            }

            // Also notify admin users
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'Super Admin', 'Admin Reguler']);
            })->get();

            $users = $users->merge($adminUsers)->unique('id');

            $notificationData = [
                'title' => 'Bulk Payroll Generation Completed',
                'message' => "Bulk payroll generation has been completed for period {$this->startDate} to {$this->endDate}.",
                'period' => [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'period_name' => \Carbon\Carbon::parse($this->startDate)->format('F Y')
                ],
                'results' => [
                    'total_processed' => $results['total_processed'],
                    'successful' => count($results['success']),
                    'failed' => count($results['failed']),
                    'skipped' => count($results['skipped']),
                ],
                'success_details' => $results['success'],
                'failed_details' => $results['failed'],
            ];

            foreach ($users as $user) {
                $user->notify(new PayrollGenerationCompleted($notificationData));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send bulk payroll generation notification: ' . $e->getMessage());
        }
    }

    /**
     * Send failure notification
     */
    private function sendFailureNotification(\Exception $exception): void
    {
        try {
            $users = collect();

            if ($this->notifyUserId) {
                $user = User::find($this->notifyUserId);
                if ($user) {
                    $users->push($user);
                }
            }

            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Admin', 'Super Admin']);
            })->get();

            $users = $users->merge($adminUsers)->unique('id');

            $notificationData = [
                'title' => 'Bulk Payroll Generation Failed',
                'message' => 'Bulk payroll generation has failed.',
                'error' => $exception->getMessage(),
                'period' => [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ],
                'employee_count' => count($this->employeeIds),
            ];

            foreach ($users as $user) {
                $user->notify(new PayrollGenerationCompleted($notificationData));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send bulk payroll generation failure notification: ' . $e->getMessage());
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('GenerateBulkPayrollJob failed permanently', [
            'exception' => $exception->getMessage(),
            'employee_ids' => $this->employeeIds,
            'period' => "{$this->startDate} to {$this->endDate}"
        ]);

        $this->sendFailureNotification($exception);
    }
}
