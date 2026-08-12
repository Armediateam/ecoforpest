<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProposalOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'target_detail' => 'array',
        'client_note' => 'string',
        'terms_condition' => 'string',
        'subtotal' => 'double:2',
        'discount_fixed' => 'double:2',
        'discount_percent' => 'double:2',
        'adjustment' => 'double:2',
        'total' => 'double:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $fields = ['subtotal', 'discount_fixed', 'discount_percent', 'adjustment', 'total'];

            foreach ($fields as $field) {
                if (isset($model->attributes[$field])) {
                    $value = $model->attributes[$field];
                    if (is_string($value)) {
                        // remove all non-numeric characters
                        $cleanedValue = preg_replace('/[^\d]/', '', $value);
                        // convert to float
                        $model->attributes[$field] = (float) $cleanedValue;
                    }
                }
            }
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function proposalItems(): HasMany
    {
        return $this->hasMany(ProposalItem::class, 'proposal_order_id', 'id');
    }

    public function proposalServices(): HasMany
    {
        return $this->hasMany(ProposalService::class, 'proposal_order_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Proposal Order')
            ->setDescriptionForEvent(fn(string $eventName) => "Proposal Order {$eventName}")
            ->logUnguarded();
    }
}
