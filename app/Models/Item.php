<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Item extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'attachment' => 'array',
    ];

    public function itemGroup()
    {
        return $this->belongsTo(ItemGroup::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function stockMovement()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function toolCategory()
    {
        return $this->belongsTo(ToolCategory::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Item')
            ->setDescriptionForEvent(fn(string $eventName) => "Item {$eventName}")
            ->logUnguarded();
    }
}
