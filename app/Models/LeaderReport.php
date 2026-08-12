<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LeaderReport extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $appends = ['bukti_penilaian_url'];

    protected $casts = [
        'rekomendasi_sanksi' => 'array',
        'periode_laporan' => 'date',
        'tanggal' => 'date',
        'ada_keterlambatan' => 'boolean',
        'peralatan_lengkap' => 'boolean',
        'apd_lengkap' => 'boolean',
        'is_approved' => 'boolean',
        'is_rejected' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];
    public function getTotalScoreAttribute()
    {
        $score = 0;
        // Menjumlahkan semua skor dari setiap field penilaian
        $score += $this->kehadiran_tepat_waktu ?? 0;
        $score += $this->tidak_terlambat ?? 0;
        $score += $this->izin_dengan_bukti ?? 0;
        $score += $this->jumlah_lokasi ?? 0;
        $score += $this->kecepatan_treatment ?? 0;
        $score += $this->update_laporan ?? 0;
        $score += $this->penggunaan_apd ?? 0;
        $score += $this->foto_dokumentasi ?? 0;
        $score += $this->rating_kepuasan ?? 0;
        $score += $this->laporan_sesuai_sop ?? 0;
        $score += $this->penggunaan_aplikasi ?? 0;
        $score += $this->tidak_kehilangan_alat ?? 0;
        $score += $this->laporan_bahan_kimia ?? 0;

        return $score;
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'leader_id');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teknisi_id');
    }

    public function getBuktiPenilaianUrlAttribute(): ?string
    {
        return $this->bukti_penilaian
            ? Storage::disk('public')->url($this->bukti_penilaian)
            : null;
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Leader Report')
            ->setDescriptionForEvent(fn(string $eventName) => "Leader Report {$eventName}")
            ->logUnguarded();
    }
}
