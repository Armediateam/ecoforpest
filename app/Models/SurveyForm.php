<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class SurveyForm extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'fields',
        'service_id'
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean'
    ];

    public const TYPE_IDENTIFICATION = 'identification';
    public const TYPE_INITIAL_CHECK = 'initial_check';
    public const TYPE_FINAL_CHECK = 'final_check';

    public static $types = [
        self::TYPE_IDENTIFICATION,
        self::TYPE_INITIAL_CHECK,
        self::TYPE_FINAL_CHECK
    ];

    public function workOrderSurveys(): HasMany
    {
        return $this->hasMany(WorkOrderSurvey::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Survey Form')
            ->setDescriptionForEvent(fn(string $eventName) => "Survey Form {$eventName}")
            ->logUnguarded();
    }
}
