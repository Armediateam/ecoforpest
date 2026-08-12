<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccessRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'date',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the access request.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who reviewed this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Check if request is approved for specific employee and date
     */
    public static function isApproved($employeeId, $date)
    {
        return static::where('employee_id', $employeeId)
            ->where('request_date', $date)
            ->where('status', 'approved')
            ->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Access Request')
            ->setDescriptionForEvent(fn(string $eventName) => "Access Request {$eventName}")
            ->logUnguarded();
    }
}
