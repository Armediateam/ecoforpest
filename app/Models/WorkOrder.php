<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Log;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_OPEN = 'Open';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_HOLD_CONFIRM = 'Hold Confirm';
    public const STATUS_CONFIRM = 'Confirm';
    public const STATUS_ASSIGNED = 'Assigned';
    public const STATUS_ON_PROGRESS = 'On Progress';
    public const STATUS_CLOSED = 'Closed';
    public const STATUS_CANCELLED = 'Cancelled';

    public static $statuses = [
        self::STATUS_OPEN,
        self::STATUS_PENDING,
        self::STATUS_HOLD_CONFIRM,
        self::STATUS_CONFIRM,
        self::STATUS_ASSIGNED,
        self::STATUS_ON_PROGRESS,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'position' => 'array',
        'leaflet_map_picker' => 'array',
        'tindakan' => 'array',
        'recurring_dates' => 'array',
        'total' => 'float',
    ];

    public function setTotalAttribute($value)
    {
        if (is_string($value)) {
            $cleaned = str_replace('.', '', $value);
            $cleaned = str_replace(',', '.', $cleaned);
            $this->attributes['total'] = (float) $cleaned;
        } else {
            $this->attributes['total'] = $value;
        }
    }


    // Mutator untuk LeafletMapPicker
    public function setPositionAttribute($value)
    {
        if (is_array($value) && isset($value['lat']) && isset($value['lng'])) {
            $formatted = [
                [
                    'latitude' => (string) $value['lat'],
                    'longitude' => (string) $value['lng']
                ]
            ];
            $this->attributes['position'] = json_encode($formatted);
        } else {
            // Set default empty JSON array when no position is provided
            $this->attributes['position'] = json_encode([]);
        }
    }

    // Accessor untuk LeafletMapPicker
    public function getLeafletMapPickerAttribute()
    {
        if (isset($this->position[0]['latitude']) && isset($this->position[0]['longitude'])) {
            return [$this->position[0]['latitude'], $this->position[0]['longitude']];
        }
        return [0, 0];
    }

    protected static $logAttributes = ['*'];
    protected static $logName = 'workorder';
    protected static $logOnlyDirty = true;
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('workorder')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $causerName = auth()->user()->name ?? 'System';
        $orderId = $this->id;
        $descriptions = [
            'created' => "{$causerName} membuat Work Order #{$orderId}",
            'updated' => "{$causerName} memperbarui Work Order #{$orderId}",
            'deleted' => "{$causerName} menghapus Work Order #{$orderId}",
            'restored' => "{$causerName} memulihkan Work Order #{$orderId}",
            'status_changed' => "{$causerName} mengubah status Work Order #{$orderId} menjadi {$this->status}",
            'assigned' => "{$causerName} menugaskan Work Order #{$orderId} kepada {$this->assigned?->name}",
        ];
        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Work Order #{$orderId}";
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'work_order_employees');
    }
    public function assigned(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_id');
    }
    public function helpers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'work_order_employees');
    }
    public function workOrderPackage(): HasMany
    {
        return $this->hasMany(WorkOrderPackage::class, 'work_order_id', 'id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(WorkOrderProgress::class);
    }

    public function latestProgress(): BelongsTo
    {
        return $this->belongsTo(WorkOrderProgress::class)->latestOfMany();
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(WorkOrderSurvey::class);
    }

    public function getIdentificationSurvey()
    {
        return $this->surveys()
            ->whereHas('surveyForm', function ($query) {
                $query->where('type', SurveyForm::TYPE_IDENTIFICATION);
            })
            ->latest()
            ->first();
    }

    public function getInitialCheckSurvey()
    {
        return $this->surveys()
            ->whereHas('surveyForm', function ($query) {
                $query->where('type', SurveyForm::TYPE_INITIAL_CHECK);
            })
            ->latest()
            ->first();
    }

    public function getFinalCheckSurvey()
    {
        return $this->surveys()
            ->whereHas('surveyForm', function ($query) {
                $query->where('type', SurveyForm::TYPE_FINAL_CHECK);
            })
            ->latest()
            ->first();
    }

    public function serviceReport()
    {
        return $this->hasOne(\App\Models\ServiceReport::class);
    }

    /**
     * Get all surveys and their answers in a structured array
     */
    public function getSurveyWithAnswersAttribute()
    {
        return $this->surveys()
            ->with(['surveyForm'])
            ->get()
            ->map(function ($survey) {
                $fields = $survey->surveyForm?->fields ?? [];
                $answers = $survey->answers ?? [];

                $fieldsWithAnswers = collect($fields)->map(function ($field) use ($answers, $survey) {
                    if (!is_array($field)) {
                        \Illuminate\Support\Facades\Log::warning('Invalid field structure in survey', [
                            'survey_id' => $survey->id,
                            'field' => $field
                        ]);
                        return null;
                    }

                    $fieldId = $field['id'] ?? null;
                    $fieldName = $field['name'] ?? null;
                    $fieldLabel = $field['label'] ?? null;
                    $fieldType = $field['type'] ?? 'text';

                    $possibleKeys = array_filter([
                        $fieldId,
                        $fieldName,
                        $fieldLabel,
                        $fieldName ? strtolower($fieldName) : null,
                        $fieldLabel ? strtolower($fieldLabel) : null,
                        $fieldLabel ? str_replace(' ', '_', strtolower($fieldLabel)) : null,
                    ]);

                    $answer = null;
                    foreach ($possibleKeys as $key) {
                        if ($key && isset($answers[$key])) {
                            $answer = $answers[$key];
                            break;
                        }
                    }

                    $processedAnswer = $answer;
                    
                    if ($fieldType === 'file') {
                        if (is_array($answer)) {
                            $processedAnswer = array_map(function($filePath) {
                                return [
                                    'path' => $filePath,
                                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($filePath)
                                ];
                            }, $answer);
                        } else {
                            $processedAnswer = [];
                        }
                    }
                    elseif ($fieldType === 'signature' && !empty($answer)) {
                        try {
                            $processedAnswer = \Illuminate\Support\Facades\Storage::disk('public')->url($answer);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error processing signature answer', [
                                'survey_id' => $survey->id,
                                'field' => $field,
                                'answer' => $answer,
                                'error' => $e->getMessage()
                            ]);
                            $processedAnswer = null;
                        }
                    }

                    return array_merge($field, [
                        'answer' => $processedAnswer,
                        'original_answer' => $answer,
                    ]);
                });

                return [
                    'id' => $survey->id,
                    'form' => $survey->surveyForm?->name,
                    'form_id' => $survey->surveyForm?->id,
                    'fields' => $fieldsWithAnswers->toArray(),
                    'raw_answers' => $answers,
                ];
            })
            ->toArray();
    }
}
