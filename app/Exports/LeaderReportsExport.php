<?php

namespace App\Exports;

use App\Models\LeaderReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class LeaderReportsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return LeaderReport::with(['leader', 'teknisi'])->get();
    }

    public function map($lr): array
    {
        return [
            $lr->id,
            $lr->periode_laporan,
            $lr->leader->name ?? "-",
            $lr->teknisi->name ?? "-",
            $lr->jumlah_customer,
            $lr->kunjungan_tepat_waktu,
            $lr->penilaian_harian_skor,
            $lr->is_approved ? "Disetujui" : ($lr->is_rejected ? "Ditolak" : "Menunggu Persetujuan"),
            $lr->created_at->format('d-m-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Periode Laporan',
            'Nama Leader',
            'Nama Teknisi',
            'Total Customer',
            'Kunjungan Tepat Waktu',
            'Skor Penilaian Harian',
            'Status Persetujuan',
            'Tanggal Laporan Dibuat'
        ];
    }
}
