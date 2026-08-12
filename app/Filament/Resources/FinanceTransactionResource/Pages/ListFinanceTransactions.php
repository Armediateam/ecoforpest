<?php

namespace App\Filament\Resources\FinanceTransactionResource\Pages;

use App\Filament\Resources\FinanceTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;

class ListFinanceTransactions extends ListRecords
{
    protected static string $resource = FinanceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Transaksi Baru')
                ->icon('heroicon-o-plus')
                ->color('success'),

            Actions\Action::make('export_journal')
                ->label('Export Journal')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Export Journal Keuangan')
                ->modalDescription('Export journal dengan running balance, filter berdasarkan tanggal, bulan, tahun, dan kategori. Saldo awal akan ditampilkan.')
                ->form([
                    \Filament\Forms\Components\Section::make('Filter Tanggal')
                        ->schema([
                            \Filament\Forms\Components\Grid::make(2)
                                ->schema([
                                    \Filament\Forms\Components\DatePicker::make('from')
                                        ->label('Dari Tanggal')
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-calendar-days'),
                                    \Filament\Forms\Components\DatePicker::make('until')
                                        ->label('Sampai Tanggal')
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-calendar-days'),
                                ]),
                        ]),

                    \Filament\Forms\Components\Section::make('Filter Periode')
                        ->description('Alternatif filter berdasarkan bulan dan tahun')
                        ->schema([
                            \Filament\Forms\Components\Grid::make(2)
                                ->schema([
                                    \Filament\Forms\Components\Select::make('month')
                                        ->label('Bulan')
                                        ->options([
                                            '01' => 'Januari',
                                            '02' => 'Februari',
                                            '03' => 'Maret',
                                            '04' => 'April',
                                            '05' => 'Mei',
                                            '06' => 'Juni',
                                            '07' => 'Juli',
                                            '08' => 'Agustus',
                                            '09' => 'September',
                                            '10' => 'Oktober',
                                            '11' => 'November',
                                            '12' => 'Desember',
                                        ])
                                        ->placeholder('Semua Bulan')
                                        ->prefixIcon('heroicon-m-calendar'),
                                    \Filament\Forms\Components\Select::make('year')
                                        ->label('Tahun')
                                        ->options(array_combine(range(date('Y') - 5, date('Y') + 1), range(date('Y') - 5, date('Y') + 1)))
                                        ->placeholder('Semua Tahun')
                                        ->prefixIcon('heroicon-m-calendar'),
                                ]),
                        ]),

                    \Filament\Forms\Components\Section::make('Filter Lainnya')
                        ->schema([
                            \Filament\Forms\Components\Grid::make(2)
                                ->schema([
                                    \Filament\Forms\Components\Select::make('category_id')
                                        ->label('Kategori')
                                        ->options(\App\Models\FinanceCategory::pluck('name', 'id'))
                                        ->searchable()
                                        ->placeholder('Semua Kategori')
                                        ->prefixIcon('heroicon-m-tag'),
                                    \Filament\Forms\Components\Select::make('format')
                                        ->label('Format File')
                                        ->options(['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)'])
                                        ->default('xlsx')
                                        ->prefixIcon('heroicon-m-document-arrow-down'),
                                ]),
                        ]),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\FinanceTransaction::query();
                    if (!empty($data['month']) && !empty($data['year'])) {
                        $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        $from = $data['year'] . '-' . $data['month'] . '-01';
                        $until = date('Y-m-t', strtotime($from));
                    } else {
                        if (!empty($data['from'])) {
                            $query->whereDate('date', '>=', $data['from']);
                        }
                        if (!empty($data['until'])) {
                            $query->whereDate('date', '<=', $data['until']);
                        }
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;
                    }
                    if (!empty($data['category_id'])) {
                        $query->where('finance_category_id', $data['category_id']);
                    }
                    $transactions = $query->orderBy('date')->get();
                    $saldo_awal_query = \App\Models\FinanceTransaction::query();
                    if (!empty($data['category_id'])) {
                        $saldo_awal_query->where('finance_category_id', $data['category_id']);
                    }
                    if ($from) {
                        $saldo_awal_query->whereDate('date', '<', $from);
                    }
                    $saldo_awal = 0;
                    foreach ($saldo_awal_query->get() as $trx) {
                        $saldo_awal += $trx->type === 'income' ? $trx->amount : -$trx->amount;
                    }
                    $export = new \App\Exports\FinanceTransactionJournalExport($transactions, array_merge($data, ['saldo_awal' => $saldo_awal, 'from' => $from, 'until' => $until]));
                    $filename = 'finance-journal-' . now()->format('Ymd_His') . '.' . $data['format'];
                    return \Maatwebsite\Excel\Facades\Excel::download($export, $filename, $data['format'] === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX);
                }),
        ];
    }
}
