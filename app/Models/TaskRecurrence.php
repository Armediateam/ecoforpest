<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TaskRecurrence extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Task Recurrence')
            ->setDescriptionForEvent(fn(string $eventName) => "Task Recurrence {$eventName}")
            ->logUnguarded();
    }
}
