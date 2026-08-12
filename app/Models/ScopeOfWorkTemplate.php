<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ScopeOfWorkTemplate extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Scope Of Work Template')
            ->setDescriptionForEvent(fn(string $eventName) => "Scope Of Work Template {$eventName}")
            ->logUnguarded();
    }
}
