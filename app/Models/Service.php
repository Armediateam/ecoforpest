<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Service extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'image' => 'array',
        'tindakan' => 'array',
    ];

    public function surveyForms()
    {
        return $this->hasMany(\App\Models\SurveyForm::class, 'service_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Service')
            ->setDescriptionForEvent(fn(string $eventName) => "Service {$eventName}")
            ->logUnguarded();
    }
}
