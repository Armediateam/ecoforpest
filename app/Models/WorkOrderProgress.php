<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkOrderProgress extends Model
{

    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'photos' => 'array',
        'location' => 'array',
        'completed_at' => 'datetime',
    ];
    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        if (!is_array($this->photos)) return [];
        return array_map(function ($path) {
            return $path ? asset('storage/' . ltrim($path, '/')) : null;
        }, $this->photos);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'completed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Work Order Progress')
            ->setDescriptionForEvent(fn(string $eventName) => "Work Order Progress {$eventName}")
            ->logUnguarded();
    }
}
