<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payroll extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'employee_income' => 'array',
        'employee_expense' => 'array',
        'generated_at' => 'datetime',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
    ];
    protected $appends = [
        'employee_income_view',
        'employee_expense_view',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // employee_income and employee_expense are stored as JSON arrays
    public function getEmployeeIncomeViewAttribute($value): array
    {
        $incomeArray = $this->employee_income ?? [];
        // dd($incomeArray);
        $formattedIncome = [];
        foreach ($incomeArray as $name => $nominal) {
            if ($name === 'total_penghasilan') {
                continue; // skip total_income entry
            }
            // reformat name from snake_case to Title Case
            $name = ucwords(str_replace('_', ' ', $name));
            $formattedIncome[] = [
                'name' => $name,
                'nominal' => $nominal,
            ];
        }
        return $formattedIncome;
    }

    public function getEmployeeExpenseViewAttribute($value): array
    {
        $expenseArray = $this->employee_expense ?? [];
        $formattedExpense = [];
        foreach ($expenseArray as $name => $nominal) {
            if ($name === 'total_potongan') {
                continue; // skip total_expense entry
            }
            // reformat name from snake_case to Title Case
            $name = ucwords(str_replace('_', ' ', $name));
            $formattedExpense[] = [
                'name' => $name,
                'nominal' => $nominal,
            ];
        }
        return $formattedExpense;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Payroll')
            ->setDescriptionForEvent(fn(string $eventName) => "Payroll {$eventName}")
            ->logUnguarded();
    }
}
