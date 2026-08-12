<?php

namespace App\Filament\Resources\LeaderReportResource\Pages;

use App\Filament\Resources\LeaderReportResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaderReport extends ViewRecord
{
    protected static string $resource = LeaderReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => !$this->record->is_approved),
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.leader-report', [
                        'record' => $this->record->load(['leader', 'teknisi', 'approvedBy']),
                    ]);
                    
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        "leader-report-{$this->record->id}.pdf"
                    );
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Bagian 1: Identitas
                Components\Section::make('📋 Identitas')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Components\TextEntry::make('periode_laporan')
                                ->label('Periode Laporan')
                                ->date('d F Y')
                                ->icon('heroicon-o-calendar-days'),
                            Components\TextEntry::make('leader.name')
                                ->label('Penilai')
                                ->icon('heroicon-o-user-circle'),
                            Components\TextEntry::make('teknisi.name')
                                ->label('Teknisi yang Dinilai')
                                ->icon('heroicon-o-wrench-screwdriver'),
                            Components\TextEntry::make('teknisi.position.title')
                                ->label('Jabatan Teknisi')
                                ->icon('heroicon-o-briefcase')
                                ->placeholder('Tidak ada data jabatan.'),
                        ]),
                    ]),

                // Bagian 2: Kehadiran & Disiplin
                Components\Section::make('🎯 Kehadiran & Disiplin (BOBOT: 20%)')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('kehadiran_tepat_waktu')
                                ->label('Kehadiran tepat waktu (%)')
                                ->formatStateUsing(fn ($state) => "{$state} / 10"),
                            Components\TextEntry::make('tidak_terlambat')
                                ->label('Tidak ada keterlambatan')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                            Components\TextEntry::make('izin_dengan_bukti')
                                ->label('Izin/sakit dengan bukti')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                        ]),
                    ]),

                // Bagian 3: Produktivitas Kerja
                Components\Section::make('📈 Produktivitas Kerja (BOBOT: 30%)')
                    ->icon('heroicon-o-star')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('jumlah_lokasi')
                                ->label('Jumlah lokasi treatment')
                                ->formatStateUsing(fn ($state) => "{$state} / 15"),
                            Components\TextEntry::make('kecepatan_treatment')
                                ->label('Kecepatan penyelesaian')
                                ->formatStateUsing(fn ($state) => "{$state} / 10"),
                            Components\TextEntry::make('update_laporan')
                                ->label('Update laporan harian')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                        ]),
                    ]),

                // Bagian 4: Kualitas Layanan & Profesionalisme
                Components\Section::make('⭐ Kualitas Layanan & Profesionalisme (BOBOT: 30%)')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Components\Grid::make(3)->schema([
                            Components\TextEntry::make('penggunaan_apd')
                                ->label('Penggunaan seragam & APD')
                                ->formatStateUsing(fn ($state) => "{$state} / 10"),
                            Components\TextEntry::make('foto_dokumentasi')
                                ->label('Foto dokumentasi lokasi')
                                ->formatStateUsing(fn ($state) => "{$state} / 10"),
                            Components\TextEntry::make('rating_kepuasan')
                                ->label('Rating kepuasan pelanggan')
                                ->formatStateUsing(fn ($state) => "{$state} / 10"),
                        ]),
                    ]),

                // Bagian 5: Kepatuhan Administratif
                Components\Section::make('📄 Kepatuhan Administratif (BOBOT: 10%)')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Components\TextEntry::make('laporan_sesuai_sop')
                                ->label('Laporan teknisi sesuai SOP')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                            Components\TextEntry::make('penggunaan_aplikasi')
                                ->label('Penggunaan aplikasi Ecoforpest')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                        ]),
                    ]),

                // Bagian 6: Pengelolaan Alat & Obat
                Components\Section::make('🔧 Pengelolaan Alat & Obat (BOBOT: 10%)')
                    ->schema([
                        Components\Grid::make(2)->schema([
                            Components\TextEntry::make('tidak_kehilangan_alat')
                                ->label('Tidak ada kehilangan alat')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                            Components\TextEntry::make('laporan_bahan_kimia')
                                ->label('Laporan penggunaan bahan kimia')
                                ->formatStateUsing(fn ($state) => "{$state} / 5"),
                        ]),
                    ]),

                // Bagian 7: Total Skor
                Components\Section::make('🏆 Total Penilaian')
                    ->schema([
                        Components\TextEntry::make('total_score')
                            ->label('Total Skor Akhir')
                            ->badge()
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->color(fn (int $state): string => match (true) {
                                $state >= 85 => 'success',
                                $state >= 70 => 'warning',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (int $state) => "{$state} / 100"),
                    ])->columnSpanFull(),

                // Bagian 8: Evaluasi & Rekomendasi
                Components\Section::make('📝 Evaluasi & Rekomendasi')
                    ->schema([
                        Components\TextEntry::make('komentar_penilai')
                            ->label('Komentar Penilai')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada komentar.'),
                        
                        // Menampilkan daftar sanksi yang dipilih
                        Components\TextEntry::make('rekomendasi_sanksi')
                            ->label('⚠️ REKOMENDASI SANKSI')
                            ->badge()
                            ->color('danger')
                            ->separator(',') // Anda bisa biarkan ini, tapi implode lebih eksplisit
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'Tidak ada rekomendasi sanksi.';
                                }
                                $sanksiMap = [
                                    'teguran_lisan' => 'Teguran Lisan',
                                    'teguran_tertulis' => 'Teguran Tertulis',
                                    'pelatihan' => 'Pelatihan Tambahan',
                                    'potong_insentif' => 'Pemotongan Insentif',
                                    'evaluasi_khusus' => 'Evaluasi Khusus dalam 30 Hari',
                                ];

                                // Mengubah array key menjadi label dan menggabungkannya menjadi string
                                return collect($state)
                                    ->map(fn($item) => $sanksiMap[$item] ?? $item)
                                    ->implode(', '); // ✅ INI PERBAIKANNYA
                            })
                            ->placeholder('Tidak ada rekomendasi sanksi.'),

                        Components\TextEntry::make('catatan_sanksi')
                            ->label('Catatan Sanksi')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan sanksi.'),
                    ])->columns(1),
            ])->columns(2); // Mengatur layout utama menjadi 2 kolom
    }

}
