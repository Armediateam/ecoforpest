<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ServiceReport extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $fillable = [
        'work_order_id',
        'customer_name',
        'work_order_number',
        'close_order',
        'technician_name',
        'created_by',
        'updated_by',
        'email_sent',
        'client_approve',
        'technician_approve',
        'signature_token',
        'signature_url',
        'client_signature',
        'client_signature_name',
        'technician_signature',
        'technician_signature_name',
    ];

    protected $casts = [
        'close_order' => 'datetime',
        'email_sent' => 'boolean',
        'client_approve' => 'boolean',
        'technician_approve' => 'boolean',
    ];

    protected $appends = [
        'client_signature_url',
        'technician_signature_url',
        'signature_token_url',
    ];

    protected static $logAttributes = ['*'];
    protected static $logName = 'service_report';
    protected static $logOnlyDirty = true;
    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('service_report')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $causerName = auth()->user()->name ?? 'System';
        $serviceReportId = $this->id;
        $descriptions = [
            'created' => "{$causerName} membuat Service Report #{$serviceReportId}",
            'updated' => "{$causerName} memperbarui Service Report #{$serviceReportId}",
            'deleted' => "{$causerName} menghapus Service Report #{$serviceReportId}",
            'status_changed' => "{$causerName} mengubah status Service Report #{$serviceReportId} menjadi {$this->status}",
            'assigned' => "{$causerName} menugaskan Service Report #{$serviceReportId} kepada {$this->assigned?->name}",
        ];
        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Service Report #{$serviceReportId}";
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public function getClientSignatureUrlAttribute()
    {
        return $this->client_signature ? asset('storage/' . $this->client_signature) : null;
    }

    public function getTechnicianSignatureUrlAttribute()
    {
        return $this->technician_signature ? asset('storage/' . $this->technician_signature) : null;
    }

    public function getSignatureTokenUrlAttribute()
    {
        return $this->signature_token ? url('/service-report/' . $this->signature_token) : null;
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }
}
