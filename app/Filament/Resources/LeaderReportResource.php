<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaderReportResource\Pages;
use App\Filament\Resources\LeaderReportResource\RelationManagers;
use App\Models\LeaderReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class LeaderReportResource extends Resource
{
    protected static ?string $model = LeaderReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Leader Reports';

    protected static ?string $navigationLabel = 'Leader Reports';

    protected static ?string $modelLabel = 'Leader Reports';

    protected static ?string $pluralModelLabel = 'Leader Reports';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_approved', false)->where('is_rejected', false)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = (int) static::getNavigationBadge();
        return $count > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📋 Identitas')
                    ->description('Masukkan Identitas laporan evaluasi teknisi')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('periode_laporan')
                                    ->required()
                                    ->label('Periode Laporan')
                                    ->helperText('Pilih tanggal periode laporan yang akan dievaluasi')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->prefixIcon('heroicon-o-calendar-days'),
                                Forms\Components\Select::make('teknisi_id')
                                    ->relationship('teknisi', 'name')
                                    ->label('Nama Teknisi yang Dinilai')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Pilih teknisi yang akan dievaluasi')
                                    ->prefixIcon('heroicon-o-wrench-screwdriver')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $teknisi = \App\Models\Employee::find($state);
                                            $set('jabatan_teknisi', $teknisi?->position->title ?? '');
                                        }
                                    }),
                                Forms\Components\TextInput::make('jabatan_teknisi')
                                    ->label('Jabatan Teknisi yang Dinilai')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-briefcase')
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, ?LeaderReport $record) {
                                        if ($record && $record->teknisi) {
                                            $component->state($record->teknisi->position->title ?? '');
                                        }
                                    }),
                                Forms\Components\Select::make('leader_id')
                                    ->relationship('leader', 'name')
                                    ->label('Penilai')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Pilih leader yang melakukan evaluasi')
                                    ->prefixIcon('heroicon-o-user-circle'),
                            ]),
                    ]),

                Forms\Components\Section::make('🎯 Kehadiran & Disiplin (BOBOT: 20%)')
                    ->description('Penilaian Kehadiran & Disiplin teknisi')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('kehadiran_tepat_waktu')
                            ->label('Kehadiran tepat waktu (%)')
                            ->helperText('Skor Maks: 10')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('tidak_terlambat')
                            ->label('Tidak ada keterlambatan tanpa alasan')
                            ->helperText('Skor Maks: 5')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(5)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('izin_dengan_bukti')
                            ->label('Izin/sakit dengan bukti')
                            ->helperText('Skor Maks: 5')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(5)
                            ->live(onBlur: true),
                    ]),

                Forms\Components\Section::make('📈 Produktivitas Kerja (BOBOT: 30%)')
                    ->description('Penilaian Produktivitas Kerja')
                    ->icon('heroicon-o-star')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_lokasi')
                                    ->label('Jumlah lokasi treatment diselesaikan')
                                    ->helperText('Skor Maks: 15')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(15)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('kecepatan_treatment')
                                    ->label('Kecepatan penyelesaian treatment')
                                    ->helperText('Skor Maks: 10')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('update_laporan')
                                    ->label('Update laporan harian')
                                    ->helperText('Skor Maks: 5')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->live(onBlur: true),
                            ]),
                    ]),

                Forms\Components\Section::make('⭐ Kualitas Layanan & Profesionalisme (BOBOT: 30%)')
                    ->description('Penilaian Kualitas Layanan & Profesionalisme Kerja')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('penggunaan_apd')
                            ->label('Penggunaan seragam dan APD')
                            ->helperText('Skor Maks: 10')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('foto_dokumentasi')
                            ->label('Foto dokumentasi lokasi')
                            ->helperText('Skor Maks: 10')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('rating_kepuasan')
                            ->label('Rating kepuasan pelanggan')
                            ->helperText('Skor Maks: 10')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10)
                            ->live(onBlur: true),
                    ]),
                Forms\Components\Section::make('📄 Kepatuhan Administratif (BOBOT: 10%)')
                    ->description('Penilaian Kepatuhan Administratif Kerja')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('laporan_sesuai_sop')
                                    ->label('Laporan teknisi sesuai SOP')
                                    ->helperText('Skor Maks: 5')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('penggunaan_aplikasi')
                                    ->label('Penggunaan aplikasi Ecoforpest')
                                    ->helperText('Skor Maks: 5')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->live(onBlur: true),
                            ]),
                    ]),

                Forms\Components\Section::make('🔧 Pengelolaan Alat & Obat (BOBOT: 10%)')
                    ->description('Penilaian Pengelolaan Alat & Obat Kerja')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('tidak_kehilangan_alat')
                                    ->label('Tidak ada kehilangan alat')
                                    ->helperText('Skor Maks: 5')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('laporan_bahan_kimia')
                                    ->label('Laporan penggunaan bahan kimia')
                                    ->helperText('Skor Maks: 5')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->live(onBlur: true),
                            ]),
                    ]),

                Forms\Components\Section::make('🏆 Total Penilaian')
                    ->description('Total Skor Penilaian dari Semua Aspek')
                    ->schema([
                        Forms\Components\Placeholder::make('total_score_display')
                            ->label('Total Skor (dari 100)')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-xl font-bold p-4 bg-gray-50 rounded-lg'])
                            ->content(function (Forms\Get $get): HtmlString {
                                $total = self::calculateTotal($get);
                                return new HtmlString(self::formatTotalScore($total)); // ✅ wrap jadi HtmlString
                            }),
                        ]),
                Forms\Components\Hidden::make('total_score')
                ->default(0),

                Forms\Components\Section::make('�📝 Evaluasi & Rekomendasi')
                    ->description('Evaluasi Pengelolaan Alat & Obat Kerja')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('komentar_penilai')
                                    ->label('Komentar Penilai')
                                    ->placeholder('Catatan khusus dari penilai...')
                                    ->rows(3),

                                Forms\Components\CheckboxList::make('rekomendasi_sanksi')
                                    ->label('⚠️ REKOMENDASI SANKSI')
                                    ->helperText('Skor di bawah 30% memerlukan tindakan perbaikan')
                                    ->options([
                                        'teguran_lisan' => 'Teguran Lisan',
                                        'teguran_tertulis' => 'Teguran Tertulis',
                                        'pelatihan' => 'Pelatihan Tambahan',
                                        'potong_insentif' => 'Pemotongan Insentif',
                                        'evaluasi_khusus' => 'Evaluasi Khusus dalam 30 Hari',
                                    ])
                                    ->columns(1),

                                Forms\Components\Textarea::make('catatan_sanksi')
                                    ->label('Catatan Sanksi')
                                    ->placeholder('Jelaskan detail sanksi dan target perbaikan...')
                                    ->rows(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('periode_laporan')
                    ->label('Periode')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('leader.name')
                    ->label('Leader')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-circle')
                    ->wrap(),

                Tables\Columns\TextColumn::make('teknisi.name')
                    ->label('Teknisi')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Skor Total')
                    ->numeric()
                    // ->sortable() // ❌ DIHAPUS: Tidak bisa sort karena bukan kolom DB
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 85 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => "{$state} / 100")
                    ->icon('heroicon-o-star'),

                Tables\Columns\TextColumn::make('rekomendasi_sanksi')
                    ->label('Rekomendasi')
                    ->badge()
                    ->separator(',')
                    ->color('danger')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return 'Tidak ada';
                        
                        $sanksiMap = [
                            'teguran_lisan' => 'Lisan',
                            'teguran_tertulis' => 'Tertulis',
                            'pelatihan' => 'Pelatihan',
                            'potong_insentif' => 'Insentif',
                            'evaluasi_khusus' => 'Evaluasi',
                        ];
                        // Mengubah ['teguran_lisan', 'pelatihan'] menjadi "Lisan, Pelatihan"
                        return collect($state)->map(fn($item) => $sanksiMap[$item] ?? ucfirst(str_replace('_', ' ', $item)))->implode(', ');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status_approval')
                    ->label('Status')
                    ->state(function ($record) {
                        if ($record->is_approved) return 'Disetujui';
                        if ($record->is_rejected) return 'Ditolak';
                        return 'Menunggu';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->is_approved) return 'success';
                        if ($record->is_rejected) return 'danger';
                        return 'warning';
                    })
                    ->icon(function ($record) {
                        if ($record->is_approved) return 'heroicon-o-check-circle';
                        if ($record->is_rejected) return 'heroicon-o-x-circle';
                        return 'heroicon-o-clock';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('total_score')
                    ->label('Filter Kinerja')
                    ->options([
                        'excellent' => 'Sangat Baik (Skor ≥ 90)',
                        'good'      => 'Baik (Skor 75-89)',
                        'poor'      => 'Perlu Perbaikan (Skor 60-74)',
                        'bad'       => 'Perlu Tindakan (Skor < 60)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['value'] === 'excellent', fn ($q) => $q->where('total_score', '>=', 90))
                            ->when($data['value'] === 'good', fn ($q) => $q->whereBetween('total_score', [75, 99.99]))
                            ->when($data['value'] === 'poor', fn ($q) => $q->whereBetween('total_score', [60, 74.99]))
                            ->when($data['value'] === 'bad', fn ($q) => $q->where('total_score', '<=', 59.99));
                    }),

                Tables\Filters\SelectFilter::make('leader_id')
                    ->relationship('leader', 'name')
                    ->label('Filter Leader')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('teknisi_id')
                    ->relationship('teknisi', 'name')
                    ->label('Filter Teknisi')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Status Persetujuan')
                    ->options([
                        'pending' => '⏳ Menunggu Persetujuan',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak'
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'pending' => $query->where('is_approved', false)->where('is_rejected', false),
                            'approved' => $query->where('is_approved', true),
                            'rejected' => $query->where('is_rejected', true),
                            default => $query
                        };
                    }),

                Tables\Filters\TernaryFilter::make('ada_sanksi')
                    ->label('Ada Rekomendasi Sanksi')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('rekomendasi_sanksi')->where('rekomendasi_sanksi', '!=', '[]'),
                        false: fn (Builder $query) => $query->whereNull('rekomendasi_sanksi')->orWhere('rekomendasi_sanksi', '=', '[]'),
                    ),

                Tables\Filters\Filter::make('ada_masalah')
                    ->label('Ada Masalah')
                    ->toggle()
                    ->query(function (Builder $query) {
                        return $query->where(function ($q) {
                            $q->where('ada_keterlambatan', true)
                                ->orWhere('peralatan_lengkap', false)
                                ->orWhere('apd_lengkap', false);
                        });
                    }),

                Tables\Filters\Filter::make('periode_laporan')
                    ->label('Periode Laporan')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'], fn(Builder $query, $date): Builder => $query->whereDate('periode_laporan', '>=', $date))
                            ->when($data['sampai'], fn(Builder $query, $date): Builder => $query->whereDate('periode_laporan', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Edit Laporan')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn($record) => !$record->is_approved),

                    Tables\Actions\Action::make('approve')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn($record) => !$record->is_approved && !$record->is_rejected)
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Laporan')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui laporan evaluasi ini?')
                        ->modalSubmitActionLabel('Ya, Setujui')
                        ->action(function ($record) {
                            $record->update([
                                'is_approved' => true,
                                'is_rejected' => false,
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'rejection_reason' => null,
                                'rejected_by' => null,
                                'rejected_at' => null,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('✅ Laporan Disetujui')
                                ->body('Laporan evaluasi teknisi telah berhasil disetujui.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn($record) => !$record->is_rejected)
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->helperText('Jelaskan alasan penolakan untuk membantu perbaikan di masa mendatang')
                                ->required()
                                ->minLength(10)
                                ->rows(4)
                                ->placeholder('Contoh: Data kunjungan tidak lengkap, perlu verifikasi ulang jumlah customer...')
                        ])
                        ->modalHeading('Tolak Laporan')
                        ->modalDescription('Silakan berikan alasan penolakan laporan evaluasi ini.')
                        ->modalSubmitActionLabel('Tolak Laporan')
                        ->action(function ($record, array $data) {
                            $record->update([
                                'is_rejected' => true,
                                'is_approved' => false,
                                'rejection_reason' => $data['rejection_reason'],
                                'rejected_by' => auth()->id(),
                                'rejected_at' => now(),
                                'approved_by' => null,
                                'approved_at' => null,
                            ]);

                            Notification::make()
                                ->warning()
                                ->title('❌ Laporan Ditolak')
                                ->body('Laporan evaluasi teknisi telah ditolak dengan alasan yang diberikan.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('reset_status')
                        ->label('Reset Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn($record) => $record->is_approved || $record->is_rejected)
                        ->requiresConfirmation()
                        ->modalHeading('Reset Status Persetujuan')
                        ->modalDescription('Apakah Anda yakin ingin mereset status laporan ini kembali ke pending?')
                        ->action(function ($record) {
                            $record->update([
                                'is_approved' => false,
                                'is_rejected' => false,
                                'rejection_reason' => null,
                                'approved_by' => null,
                                'approved_at' => null,
                                'rejected_by' => null,
                                'rejected_at' => null,
                            ]);

                            Notification::make()
                                ->info()
                                ->title('🔄 Status Direset')
                                ->body('Status laporan telah dikembalikan ke pending.')
                                ->send();
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash'),

                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Setujui Semua')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Laporan Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui semua laporan yang dipilih?')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->is_approved && !$record->is_rejected) {
                                    $record->update([
                                        'is_approved' => true,
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('✅ Laporan Disetujui')
                                ->body("{$count} laporan berhasil disetujui.")
                                ->send();
                        }),

                    // Tables\Actions\BulkAction::make('bulk_export')
                    //     ->label('Ekspor ke Excel')
                    //     ->icon('heroicon-o-document-arrow-down')
                    //     ->color('info')
                    //     ->action(function ($records) {
                    //         // This would need to be implemented with Excel export functionality
                    //         Notification::make()
                    //             ->info()
                    //             ->title('📊 Fitur Ekspor')
                    //             ->body('Fitur ekspor Excel akan segera tersedia.')
                    //             ->send();
                    //     }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    private static function calculateTotal(Forms\Get $get): float
    {
        $fields = [
            'kehadiran_tepat_waktu',
            'tidak_terlambat', 
            'izin_dengan_bukti',
            'jumlah_lokasi',
            'kecepatan_treatment',
            'update_laporan',
            'penggunaan_apd',
            'foto_dokumentasi', 
            'rating_kepuasan',
            'laporan_sesuai_sop',
            'penggunaan_aplikasi',
            'tidak_kehilangan_alat',
            'laporan_bahan_kimia'
        ];

        $total = 0;
        foreach ($fields as $field) {
            $value = $get($field);
            $total += is_numeric($value) ? (float) $value : 0;
        }

        return min(100, max(0, $total));
    }


    private static function formatTotalScore(float $total): string
    {
        $color = match(true) {
            $total >= 90 => 'text-green-600',
            $total >= 75 => 'text-blue-600',
            $total >= 60 => 'text-yellow-600',
            default => 'text-red-600'
        };

        $badge = match(true) {
            $total >= 90 => '🏆 Sangat Baik',
            $total >= 75 => '✨ Baik',
            $total >= 60 => '⚠️ Cukup',
            default => '❌ Perlu Perbaikan'
        };

        $icon = match(true) {
            $total >= 90 => '🏆',
            $total >= 75 => '✨',
            $total >= 60 => '⚠️',
            default => '❌',
        };

        return "
            <div class='flex items-center justify-between p-3 rounded border bg-gray'>
                <span class='text-lg font-semibold'>Total Skor:</span>
                <span class='text-2xl font-bold {$color}'>
                    {$icon} " . number_format($total, 1) . " / 100
                </span>
                <span class='text-lg font-medium {$color}'>- {$badge}</span>
            </div>
        ";
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaderReports::route('/'),
            'create' => Pages\CreateLeaderReport::route('/create'),
            'view' => Pages\ViewLeaderReport::route('/{record}'),
            'edit' => Pages\EditLeaderReport::route('/{record}/edit'),
        ];
    }
}
