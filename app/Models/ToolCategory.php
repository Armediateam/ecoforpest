<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ToolCategory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'tools_categories';

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Tool Categories')
            ->setDescriptionForEvent(fn(string $eventName) => "Tool Categories {$eventName}")
            ->logUnguarded();
    }
}
