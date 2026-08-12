<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FinanceTransaction extends Model
{
    use SoftDeletes, LogsActivity;
    protected $guarded = ['id'];

    /**
     * Mutator untuk amount - hapus semua karakter non-numerik
     */
    public function setAmountAttribute($value)
    {
        // Hapus semua karakter kecuali angka
        $cleanValue = preg_replace('/[^0-9]/', '', (string) $value);

        // Konversi ke numeric dan simpan
        $this->attributes['amount'] = is_numeric($cleanValue) ? (int) $cleanValue : 0;
    }

    public function category()
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function workorder()
    {
        return $this->belongsTo(WorkOrder::class, 'workorder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Finance Transaction')
            ->setDescriptionForEvent(fn(string $eventName) => "Finance Transaction {$eventName}")
            ->logUnguarded();
    }
}
