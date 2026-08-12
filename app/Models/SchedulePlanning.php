<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SchedulePlanning extends Model
{
    use LogsActivity;

    protected $fillable = [
        'client_name',
        'address',
        'location_maps_url',
        'treatment_start_date',
        'schedule_days',
        'visit_hours',
        'night_treatment',
        'target_pests',
        'visit_frequency',
        'week_one_treatments',
        'week_two_treatments',
        'week_three_treatments',
        'week_four_treatments',
        'leader_notes',
    ];

    protected $casts = [
        'treatment_start_date' => 'date',
        'schedule_days' => 'array',
        'target_pests' => 'array',
        'week_one_treatments' => 'array',
        'week_two_treatments' => 'array',
        'week_three_treatments' => 'array',
        'week_four_treatments' => 'array',
    ];

    public function leaders()
    {
        return $this->belongsToMany(Employee::class, 'schedule_planning_leaders', 'schedule_planning_id', 'employee_id');
    }

    public function teknisi()
    {
        return $this->belongsToMany(Employee::class, 'schedule_planning_teknisi', 'schedule_planning_id', 'employee_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Schedule Planning')
            ->setDescriptionForEvent(fn(string $eventName) => "Attendance {$eventName}")
            ->logUnguarded();
    }
}
