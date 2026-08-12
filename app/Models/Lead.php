<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected static $logAttributes = ['*'];
    protected static $logName = 'lead';
    protected static $logOnlyDirty = true;

    protected $guarded = ['id'];

    protected $casts = [
        'date_contacted' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('lead')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        // Get causer (user) dari auth
        $causerName = auth()->user()->name ?? 'System';

        $descriptions = [
            'created' => "{$causerName} membuat Lead {$this->name}",
            'updated' => "{$causerName} memperbarui Lead {$this->name}",
            'deleted' => "{$causerName} menghapus Lead {$this->name}",
            'restored' => "{$causerName} memulihkan Lead {$this->name}",
            'converted' => "{$causerName} mengkonversi Lead {$this->name} menjadi customer",
            'status_changed' => "{$causerName} mengubah status Lead {$this->name} menjadi {$this->status?->name}",
            'assigned' => "{$causerName} menugaskan Lead {$this->name} kepada {$this->assigned?->name}",
        ];

        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Lead {$this->name}";
    }
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }
    public function recentActivities()
    {
        return $this->activities()
            ->latest()
            ->with('causer')
            ->limit(3);
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function assigned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'lead_tags');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    public function proposal()
    {
        return $this->hasMany(Proposal::class);
    }
    public function tasks()
    {
        return $this->hasMany(Task::class, 'lead_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'lead_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'lead_id');
    }
}
