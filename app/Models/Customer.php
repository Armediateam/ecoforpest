<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;


class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected static $logAttributes = ['*'];
    protected static $logName = 'customer';
    protected static $logOnlyDirty = true;

    protected $guarded = ['id'];

    protected $casts = [
        'position' => 'array',
        'attachments' => 'array',
        'attachment_original_names' => 'array',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('customer')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $causerName = auth()->user()->name ?? 'System';

        $descriptions = [
            'created' => "{$causerName} membuat Customer {$this->name}",
            'updated' => "{$causerName} memperbarui Customer {$this->name}",
            'deleted' => "{$causerName} menghapus Customer {$this->name}",
            'restored' => "{$causerName} memulihkan Customer {$this->name}",
            'status_changed' => "{$causerName} mengubah status Customer {$this->name} menjadi {$this->status}",
            'group_changed' => "{$causerName} memindahkan Customer {$this->name} ke grup {$this->customerGroup?->name}",
        ];

        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Customer {$this->name}";
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function leadProposals(): HasMany
    {
        return $this->lead()->has('lead')->with('lead')
            ->join('proposals', 'proposals.lead_id', '=', 'leads.id')
            ->select('proposals.*');
    }

    /**
     * Get all proposals (both from customer and related lead)
     */
    public function allProposals()
    {
        return Proposal::where(function ($query) {
            $query->where('customer_id', $this->id)
                ->orWhere('lead_id', $this->lead_id);
        })->latest();
    }


    public function invoices()
    {
        return Invoice::where(function ($query) {
            $query->where('customer_id', $this->id);
        })->latest();
    }

    /**
     * Get all invoices (both from customer and related lead)
     */
    public function allInvoices()
    {
        return Invoice::where(function ($query) {
            $query->where('customer_id', $this->id)
                ->orWhereHas('lead', function ($leadQuery) {
                    $leadQuery->where('id', $this->lead_id);
                });
        })->latest();
    }

    /**
     * Get all contracts for the customer.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Models\Task::class, 'customer_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
