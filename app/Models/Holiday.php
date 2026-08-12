<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Holiday extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    /**
     * Check if a given date is a holiday.
     *
     * @param string|\DateTimeInterface $date (format: Y-m-d)
     * @return Holiday|null
     */
    public static function isHoliday($date)
    {
        return static::where('date', is_string($date) ? $date : $date->format('Y-m-d'))->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Holiday')
            ->setDescriptionForEvent(fn(string $eventName) => "Holiday {$eventName}")
            ->logUnguarded();
    }
}
