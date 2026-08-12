<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProposalComment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;    protected static $logAttributes = ['*'];
    protected static $logName = 'proposal_comment';
    protected static $logOnlyDirty = true;
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];

    protected $guarded = ['id'];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('proposal_comment')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $causerName = auth()->user()->name ?? 'System';
        $proposalSubject = $this->proposal->subject ?? $this->proposal_id;
        
        return match($eventName) {
            'created' => "{$causerName} menambahkan komentar pada Proposal {$proposalSubject}",
            'updated' => "{$causerName} memperbarui komentar pada Proposal {$proposalSubject}",
            'deleted' => "{$causerName} menghapus komentar dari Proposal {$proposalSubject}",
            'commented' => "{$causerName} mengomentari Proposal {$proposalSubject}",
            default => "{$causerName} melakukan {$eventName} pada komentar Proposal {$proposalSubject}"
        };
    }
}
