<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attendance extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'image_clock_in_url',
        'image_clock_out_url',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'coordinate_clock_in' => 'array',
        'coordinate_clock_out' => 'array'
    ];

    /**
     * Get the employee that owns the address.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the image clock in URL attribute.
     */
    public function getImageClockInUrlAttribute(): ?string
    {
        return $this->image_clock_in ? asset('storage/' . $this->image_clock_in) : null;
    }

    /**
     * Get the image clock out URL attribute.
     */
    public function getImageClockOutUrlAttribute(): ?string
    {
        return $this->image_clock_out ? asset('storage/' . $this->image_clock_out) : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Attendance')
            ->setDescriptionForEvent(fn(string $eventName) => "Attendance {$eventName}")
            ->logUnguarded();
    }
}
