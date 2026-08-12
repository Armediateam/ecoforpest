<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FinanceCategory extends Model
{
    use SoftDeletes, LogsActivity;
    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(FinanceTransaction::class, 'finance_category_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Finance Category')
            ->setDescriptionForEvent(fn(string $eventName) => "Finance Category {$eventName}")
            ->logUnguarded();
    }
}
