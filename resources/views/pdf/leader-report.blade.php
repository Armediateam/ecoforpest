<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leader Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 200px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
        }
        .grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .row {
            display: table-row;
        }
        .col {
            display: table-cell;
            padding: 5px;
        }
        .score {
            font-weight: bold;
            text-align: right;
        }
        .total-score {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo_horizontal.png') }}" alt="Logo">
        <h2>Leader Report</h2>
    </div>

    <div class="section">
        <div class="section-title">Informasi Umum</div>
        <div class="grid">
            <div class="row">
                <div class="col">Periode Laporan:</div>
                <div class="col">{{ $record->periode_laporan->format('d F Y') }}</div>
            </div>
            <div class="row">
                <div class="col">Tanggal:</div>
                <div class="col">{{ $record->created_at->format('d F Y') }}</div>
            </div>
            <div class="row">
                <div class="col">Leader:</div>
                <div class="col">{{ $record->leader->name }}</div>
            </div>
            <div class="row">
                <div class="col">Teknisi:</div>
                <div class="col">{{ $record->teknisi->name }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">A. Kehadiran & Disiplin (20%)</div>
        <div class="grid">
            <div class="row">
                <div class="col">1. Kehadiran Tepat Waktu</div>
                <div class="col score">{{ number_format($record->kehadiran_tepat_waktu, 0) }}/10</div>
            </div>
            <div class="row">
                <div class="col">2. Tidak Terlambat</div>
                <div class="col score">{{ number_format($record->tidak_terlambat, 0) }}/5</div>
            </div>
            <div class="row">
                <div class="col">3. Izin Dengan Bukti</div>
                <div class="col score">{{ number_format($record->izin_dengan_bukti, 0) }}/5</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">B. Produktivitas Kerja (30%)</div>
        <div class="grid">
            <div class="row">
                <div class="col">1. Jumlah Lokasi</div>
                <div class="col score">{{ number_format($record->jumlah_lokasi, 0) }}/15</div>
            </div>
            <div class="row">
                <div class="col">2. Kecepatan Treatment</div>
                <div class="col score">{{ number_format($record->kecepatan_treatment, 0) }}/10</div>
            </div>
            <div class="row">
                <div class="col">3. Update Laporan</div>
                <div class="col score">{{ number_format($record->update_laporan, 0) }}/5</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">C. Kualitas Layanan & Profesionalisme (30%)</div>
        <div class="grid">
            <div class="row">
                <div class="col">1. Penggunaan APD</div>
                <div class="col score">{{ number_format($record->penggunaan_apd, 0) }}/10</div>
            </div>
            <div class="row">
                <div class="col">2. Foto Dokumentasi</div>
                <div class="col score">{{ number_format($record->foto_dokumentasi, 0) }}/10</div>
            </div>
            <div class="row">
                <div class="col">3. Rating Kepuasan</div>
                <div class="col score">{{ number_format($record->rating_kepuasan, 0) }}/10</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">D. Kepatuhan Administratif (10%)</div>
        <div class="grid">
            <div class="row">
                <div class="col">1. Laporan Sesuai SOP</div>
                <div class="col score">{{ number_format($record->laporan_sesuai_sop, 0) }}/5</div>
            </div>
            <div class="row">
                <div class="col">2. Penggunaan Aplikasi</div>
                <div class="col score">{{ number_format($record->penggunaan_aplikasi, 0) }}/5</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">E. Pengelolaan Alat & Obat (10%)</div>
        <div class="grid">
            <div class="row">
                <div class="col">1. Tidak Kehilangan Alat</div>
                <div class="col score">{{ number_format($record->tidak_kehilangan_alat, 0) }}/5</div>
            </div>
            <div class="row">
                <div class="col">2. Laporan Bahan Kimia</div>
                <div class="col score">{{ number_format($record->laporan_bahan_kimia, 0) }}/5</div>
            </div>
        </div>
    </div>

    <div class="total-score">
        Total Skor: {{ 
            number_format(
                $record->kehadiran_tepat_waktu + 
                $record->tidak_terlambat + 
                $record->izin_dengan_bukti +
                $record->jumlah_lokasi + 
                $record->kecepatan_treatment + 
                $record->update_laporan +
                $record->penggunaan_apd + 
                $record->foto_dokumentasi + 
                $record->rating_kepuasan +
                $record->laporan_sesuai_sop + 
                $record->penggunaan_aplikasi +
                $record->tidak_kehilangan_alat + 
                $record->laporan_bahan_kimia,
                0
            )
        }}/100
    </div>

    @if($record->komentar_penilai)
    <div class="section">
        <div class="section-title">Komentar Penilai</div>
        <p>{{ $record->komentar_penilai }}</p>
    </div>
    @endif

    @if($record->rekomendasi_sanksi)
    <div class="section">
        <div class="section-title">Rekomendasi Sanksi</div>
        @php
            $sanksiMap = [
                'teguran_lisan' => 'Teguran Lisan',
                'teguran_tertulis' => 'Teguran Tertulis',
                'pelatihan' => 'Pelatihan Tambahan',
                'potong_insentif' => 'Pemotongan Insentif',
                'evaluasi_khusus' => 'Evaluasi Khusus dalam 30 Hari',
            ];
            $sanksiArray = is_array($record->rekomendasi_sanksi) ? $record->rekomendasi_sanksi : [$record->rekomendasi_sanksi];
            $formattedSanksi = collect($sanksiArray)
                ->map(fn($item) => $sanksiMap[$item] ?? $item)
                ->filter()
                ->implode(', ');
        @endphp
        <p>{{ $formattedSanksi }}</p>
    </div>
    @endif

    @if($record->catatan_sanksi)
    <div class="section">
        <div class="section-title">Catatan Sanksi</div>
        <p>{{ $record->catatan_sanksi }}</p>
    </div>
    @endif

    @if($record->is_approved)
    <div class="section">
        <div class="section-title">Status Approval</div>
        <p>Disetujui pada: {{ $record->approved_at ? $record->approved_at->format('d F Y H:i:s') : '-' }}</p>
        <p>Disetujui oleh: {{ $record->approvedBy?->name ?? '-' }}</p>
    </div>
    @endif
</body>
</html>