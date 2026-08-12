<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Shift extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];
    protected $casts = [
        'workhour' => 'array',
    ];

    /**
     * Get the employees that have this shift as override.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the departments that use this shift as default.
     */
    public function defaultForDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'default_shift_id');
    }

    /**
     * Get the positions that use this shift as default.
     */
    public function defaultForPositions(): HasMany
    {
        return $this->hasMany(Position::class, 'default_shift_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Shift')
            ->setDescriptionForEvent(fn(string $eventName) => "Shift {$eventName}")
            ->logUnguarded();
    }
}
