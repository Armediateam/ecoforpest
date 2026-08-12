<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchedulePlanningResource\Pages;
use App\Filament\Resources\SchedulePlanningResource\RelationManagers;
use App\Models\SchedulePlanning;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchedulePlanningResource extends Resource
{
    protected static ?string $model = SchedulePlanning::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Leader Reports';

    protected static ?string $navigationLabel = 'Schedule Planning';

    protected static ?string $modelLabel = 'Schedule Planning';

    protected static ?string $pluralModelLabel = 'Schedule Planning';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Klien')
                    ->description('Detail informasi klien dan lokasi yang akan dilayani')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->required()
                            ->label('Nama Klien')
                            ->placeholder('Masukkan nama klien')
                            ->prefixIcon('heroicon-o-user')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->label('Alamat Lokasi')
                            ->placeholder('Masukkan alamat lengkap lokasi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('location_maps_url')
                            ->required()
                            ->label('Link Google Maps')
                            ->placeholder('https://maps.google.com/...')
                            ->prefixIcon('heroicon-o-map')
                            ->url()
                            ->columnSpanFull()
                            ->helperText('Masukkan link Google Maps untuk memudahkan tim menemukan lokasi'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Penugasan Tim')
                    ->description('Pilih leader dan teknisi yang akan bertanggung jawab')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Select::make('leader_ids')
                            ->label('Leader Penanggung Jawab')
                            ->multiple()
                            ->relationship('leaders', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->prefixIcon('heroicon-o-shield-check')
                            ->helperText('Pilih satu atau lebih leader yang akan mengawasi pekerjaan'),

                        Forms\Components\Select::make('teknisi_ids')
                            ->label('Teknisi Pelaksana')
                            ->multiple()
                            ->relationship('teknisi', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->prefixIcon('heroicon-o-wrench-screwdriver')
                            ->helperText('Pilih teknisi yang akan mengerjakan treatment'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Jadwal Treatment')
                    ->description('Atur jadwal dan frekuensi kunjungan treatment')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('treatment_start_date')
                                    ->required()
                                    ->label('Tanggal Mulai')
                                    ->prefixIcon('heroicon-o-play')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('visit_frequency')
                                    ->label('Frekuensi Kunjungan')
                                    ->options([
                                        '1x Sebulan' => '1x Sebulan',
                                        '2x Sebulan' => '2x Sebulan',
                                        '3x Sebulan' => '3x Sebulan',
                                        '4x Sebulan' => '4x Sebulan',
                                        '8x Sebulan' => '8x Sebulan',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-o-arrow-path')
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('schedule_info')
                                    ->label('Info Jadwal')
                                    ->content('Pilih hari dan atur jam kunjungan dengan detail di bawah')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\CheckboxList::make('schedule_days')
                            ->label('Hari Pelaksanaan')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                                'Minggu' => 'Minggu',
                            ])
                            ->columns(7)
                            ->gridDirection('row')
                            ->required(),

                        Forms\Components\Textarea::make('visit_hours')
                            ->label('🕐 Detail Jam Kunjungan')
                            ->placeholder('Contoh: Senin jam 05:00, Rabu jam 06:00, Kamis jam 20:00')
                            ->rows(3)
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('night_treatment')
                            ->label('🌙 Treatment Malam (Opsional)')
                            ->placeholder('Contoh: Area Kitchen dilakukan tanggal 10 Juli jam 22:00')
                            ->rows(2)
                            ->nullable()
                            ->helperText('Khusus untuk treatment yang dilakukan di malam hari')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Target Hama')
                    ->description('Pilih jenis hama yang akan di-treatment')
                    ->icon('heroicon-o-bug-ant')
                    ->schema([
                        Forms\Components\CheckboxList::make('target_pests')
                            ->label('Jenis Hama yang Ditargetkan')
                            ->options([
                                'Tikus' => '🐭 Tikus',
                                'Nyamuk' => '🦟 Nyamuk',
                                'Kecoa' => '🪳 Kecoa',
                                'Lalat' => '🪰 Lalat',
                                'Bedbugs' => '🐛 Bedbugs',
                                'Rayap' => '🐜 Rayap',
                                'Semut' => '🐜 Semut',
                                'Ulat Kaki Seribu' => '🐛 Ulat Kaki Seribu',
                                'Laba Laba' => '🕷️ Laba Laba',
                            ])
                            ->columns(3)
                            ->gridDirection('row')
                            ->required(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Metode Treatment')
                    ->description('Pilih metode treatment untuk setiap minggu')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Tabs::make('treatment_weeks')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Minggu 1')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        self::getTreatmentMethodsSchema('week_one_treatments'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Minggu 2')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        self::getTreatmentMethodsSchema('week_two_treatments'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Minggu 3')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        self::getTreatmentMethodsSchema('week_three_treatments'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Minggu 4')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        self::getTreatmentMethodsSchema('week_four_treatments'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Catatan Tambahan')
                    ->description('Tambahkan catatan dan rekomendasi dari leader')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\RichEditor::make('leader_notes')
                            ->label('Catatan & Rekomendasi Leader')
                            ->required()
                            ->placeholder('Tambahkan catatan khusus, rekomendasi, atau instruksi penting lainnya...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    protected static function getTreatmentMethodsSchema(string $fieldName): Forms\Components\Component
    {
        return Forms\Components\Repeater::make($fieldName)
            ->label('Metode Treatment')
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Toggle::make('blower')
                            ->label('🌪️ Blower')
                            ->inline(false),
                        Forms\Components\Toggle::make('fogging')
                            ->label('💨 Fogging')
                            ->inline(false),
                        Forms\Components\Toggle::make('spraying')
                            ->label('💧 Spraying')
                            ->inline(false),
                        Forms\Components\Toggle::make('rodent')
                            ->label('🐭 Rodent Control')
                            ->inline(false),
                        Forms\Components\Toggle::make('fumigasi')
                            ->label('☁️ Fumigasi')
                            ->inline(false),
                        Forms\Components\Toggle::make('misting')
                            ->label('🌫️ Misting')
                            ->inline(false),
                        Forms\Components\Toggle::make('vacum')
                            ->label('🔌 Vacuum')
                            ->inline(false),
                        Forms\Components\Toggle::make('monitoring')
                            ->label('👁️ Monitoring')
                            ->inline(false),
                    ]),
            ])
            ->defaultItems(1)
            ->maxItems(1)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull()
            ->required();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Nama Klien')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->copyable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn($record) => $record->address),

                Tables\Columns\TextColumn::make('treatment_start_date')
                    ->label('Tanggal Mulai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->treatment_start_date > now() ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('schedule_days')
                    ->label('Hari')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return $state;
                        $shortDays = [
                            'Senin' => 'Sen',
                            'Selasa' => 'Sel',
                            'Rabu' => 'Rab',
                            'Kamis' => 'Kam',
                            'Jumat' => 'Jum',
                            'Sabtu' => 'Sab',
                            'Minggu' => 'Min'
                        ];
                        return collect($state)->map(fn($day) => $shortDays[$day] ?? $day)->take(3)->join(', ') . (count($state) > 3 ? '...' : '');
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('visit_frequency')
                    ->label('Frekuensi')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            '8x Sebulan' => 'danger',
                            '4x Sebulan' => 'warning',
                            default => 'success'
                        };
                    }),

                Tables\Columns\TextColumn::make('leaders.name')
                    ->label('Leader')
                    ->formatStateUsing(fn($state, $record) => $record->leaders->first()?->name ?? '-')
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('treatment_start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('visit_frequency')
                    ->label('Frekuensi')
                    ->options([
                        '1x Sebulan' => '1x Sebulan',
                        '2x Sebulan' => '2x Sebulan',
                        '3x Sebulan' => '3x Sebulan',
                        '4x Sebulan' => '4x Sebulan',
                        '8x Sebulan' => '8x Sebulan',
                    ]),

                Tables\Filters\Filter::make('treatment_date_range')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('treatment_start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('treatment_start_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\ViewAction::make()
                    //     ->icon('heroicon-o-eye')
                    //     ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning'),
                    Tables\Actions\Action::make('maps')
                        ->label('Buka Maps')
                        ->icon('heroicon-o-map')
                        ->color('success')
                        ->url(fn($record) => $record->location_maps_url)
                        ->openUrlInNewTab(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Schedule Planning Baru')
                    ->icon('heroicon-o-plus'),
            ])
            ->striped()
            ->paginated([25, 50, 100]);
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
            'index' => Pages\ListSchedulePlannings::route('/'),
            'create' => Pages\CreateSchedulePlanning::route('/create'),
            'edit' => Pages\EditSchedulePlanning::route('/{record}/edit'),
        ];
    }
}
