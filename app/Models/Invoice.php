<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'allowed_payment_method' => 'array',
        'target_detail' => 'array',
        'xendit_data_raw' => 'array',
        'is_quotation' => 'boolean'
    ];

    protected static $logAttributes = ['*'];
    protected static $logName = 'invoice';
    protected static $logOnlyDirty = true;
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            if (empty($invoice->created_by)) {
                $invoice->created_by = auth()->id();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('invoice')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        // Get causer (user) from auth
        $causerName = auth()->user()->name ?? 'System';

        $descriptions = [
            'created' => "{$causerName} membuat Invoice {$this->invoice_number}",
            'updated' => "{$causerName} memperbarui Invoice {$this->invoice_number}",
            'deleted' => "{$causerName} menghapus Invoice {$this->invoice_number}",
            'restored' => "{$causerName} memulihkan Invoice {$this->invoice_number}",
            'status_changed' => "{$causerName} mengubah status Invoice {$this->invoice_number} menjadi {$this->status}",
        ];

        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Invoice {$this->invoice_number}";
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function saleAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sale_agent');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'payment_term');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function invoiceItem(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }

    public function invoiceService(): HasMany
    {
        return $this->hasMany(InvoiceService::class, 'invoice_id', 'id');
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'id');
    }

    public function createXenditPayment()
    {
        try {
            $xenditService = app(\App\Services\XenditPaymentService::class);
            return $xenditService->createPayment($this);
        } catch (\Exception $e) {
            Log::error('Failed to create Xendit payment: ' . $e->getMessage(), [
                'invoice_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function isPaymentExpired(): bool
    {
        if (!$this->payment_url || !$this->payment_status) {
            return false;
        }

        return $this->payment_status === 'EXPIRED';
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'ECOINV';
        $yearMonth = now()->format('m/Y');

        $latestInvoice = self::where('invoice_number', 'like', "{$prefix}%/{$yearMonth}")
            ->latest('invoice_number')
            ->first();

        $sequence = 1;

        if ($latestInvoice) {
            $prefixAndSequence = explode('/', $latestInvoice->invoice_number)[0];
            $lastSequence = (int) str_replace($prefix, '', $prefixAndSequence);

            $sequence = $lastSequence + 1;
        }

        $formattedSequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$formattedSequence}/{$yearMonth}";
    }

    public static function generateQuotationNumber(): string
    {
        $prefix = 'ECOQUO';
        $yearMonth = now()->format('m/Y');

        $latestQuotation = self::where('invoice_number', 'like', "{$prefix}%/{$yearMonth}")
            ->latest('invoice_number')
            ->first();

        $sequence = 1;

        if ($latestQuotation) {
            $prefixAndSequence = explode('/', $latestQuotation->invoice_number)[0];
            $lastSequence = (int) str_replace($prefix, '', $prefixAndSequence);

            $sequence = $lastSequence + 1;
        }

        $formattedSequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$formattedSequence}/{$yearMonth}";
    }
}
