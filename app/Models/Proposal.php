<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class Proposal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected static $logAttributes = ['*'];
    protected static $logName = 'proposal';
    protected static $logOnlyDirty = true;
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('proposal')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        // Get causer (user) from auth
        $causerName = auth()->user()->name ?? 'System';

        $descriptions = [
            'created' => "{$causerName} membuat Proposal {$this->subject}",
            'updated' => "{$causerName} memperbarui Proposal {$this->subject}",
            'deleted' => "{$causerName} menghapus Proposal {$this->subject}",
            'restored' => "{$causerName} memulihkan Proposal {$this->subject}",
            'status_changed' => "{$causerName} mengubah status Proposal {$this->subject} menjadi {$this->status}",
            'assigned' => "{$causerName} menugaskan Proposal {$this->subject} kepada {$this->assigned?->name}",
            'commented' => "{$causerName} menambahkan komentar pada Proposal {$this->subject}",
        ];

        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Proposal {$this->subject}";
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assigned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function proposalTemplate(): BelongsTo
    {
        return $this->belongsTo(ProposalTemplate::class, 'template_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'payment_term');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'proposal_tags');
    }

    public function proposalOrder(): HasMany
    {
        return $this->HasMany(ProposalOrder::class, 'proposal_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProposalComment::class)->latest();
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Models\Task::class, 'proposal_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($proposal) {
            // Handle status changes
            if ($proposal->isDirty('status')) {
                activity()
                    ->performedOn($proposal)
                    ->causedBy(auth()->user())
                    ->event('status_changed')
                    ->withProperties([
                        'old_status' => Str::title($proposal->getOriginal('status')),
                        'new_status' => Str::title($proposal->status)
                    ])
                    ->log("Status changed from " . Str::title($proposal->getOriginal('status')) . " to " . Str::title($proposal->status));
            }

            // Handle assignment changes
            if ($proposal->isDirty('assigned_id')) {
                $oldAssignedUser = User::find($proposal->getOriginal('assigned_id'));
                $newAssignedUser = User::find($proposal->assigned_id);

                activity()
                    ->performedOn($proposal)
                    ->causedBy(auth()->user())
                    ->event('assigned')
                    ->withProperties([
                        'old_assigned' => optional($oldAssignedUser)->name,
                        'new_assigned' => optional($newAssignedUser)->name
                    ])
                    ->log("Assigned user changed");
            }
        });
    }
}
