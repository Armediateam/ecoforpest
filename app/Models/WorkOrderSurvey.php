<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkOrderSurvey extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'answers' => 'array'
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function surveyForm(): BelongsTo
    {
        return $this->belongsTo(SurveyForm::class);
    }

    public function filledBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'filled_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Work Order Survey')
            ->setDescriptionForEvent(fn(string $eventName) => "Work Order Survey {$eventName}")
            ->logUnguarded();
    }
}
