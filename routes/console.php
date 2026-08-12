<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatic Payroll Generation Scheduling
Schedule::command('payroll:generate --month=current --year=current')
    ->monthlyOn(1, '00:00')
    ->environments(['production'])
    ->description('Generate monthly payroll for all employees')
    ->withoutOverlapping()
    ->when(function () {
        // Only run if it's the first day of the month
        return now()->day === 1;
    });

// Also schedule a reminder/check on the last day of the month
Schedule::command('payroll:generate --month=current --year=current --dry-run')
    ->monthlyOn(date('t'), '23:00') // Last day of month at 11 PM
    ->environments(['production'])
    ->description('Check monthly payroll generation readiness');
