<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProposalService extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function proposalOrder(): BelongsTo
    {
        return $this->belongsTo(ProposalOrder::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'proposal_service_taxes');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Proposal Service')
            ->setDescriptionForEvent(fn(string $eventName) => "Proposal Service {$eventName}")
            ->logUnguarded();
    }
}
