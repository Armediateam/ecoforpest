<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Task extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected static $logAttributes = ['*'];
    protected static $logName = 'task';
    protected static $logOnlyDirty = true;
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('task')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $causerName = auth()->user()->name ?? 'System';
        $taskId = $this->id;
        $descriptions = [
            'created' => "{$causerName} membuat Task #{$taskId}",
            'updated' => "{$causerName} memperbarui Task #{$taskId}",
            'deleted' => "{$causerName} menghapus Task #{$taskId}",
            'restored' => "{$causerName} memulihkan Task #{$taskId}",
            'status_changed' => "{$causerName} mengubah status Task #{$taskId} menjadi {$this->status}",
            'assigned' => "{$causerName} menugaskan Task #{$taskId} kepada {$this->assigned?->name}",
        ];
        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Task #{$taskId}";
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function taskRecurrence(): HasMany
    {
        return $this->hasMany(TaskRecurrence::class, 'task_id', 'id');
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_viewers');
    }
}
