<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CashAdvance extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $appends = ['attachment_url'];

    protected $casts = [
        'amount' => 'integer',
    ];

    // Mutator untuk membersihkan format angka
    public function setAmountAttribute($value)
    {
        if (is_string($value)) {
            $value = preg_replace('/[^0-9]/', '', $value);
        }
        $this->attributes['amount'] = $value;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function getAttachmentUrlAttribute(): string
    {
        return $this->attachment ? asset('storage/' . $this->attachment) : '';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Cash Advance')
            ->setDescriptionForEvent(fn(string $eventName) => "Cash Advance {$eventName}")
            ->logUnguarded();
    }
}
