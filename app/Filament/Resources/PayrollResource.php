<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use App\Models\Employee;
use App\Services\PayrollCalculatorService;
use App\Services\PayrollGenerationService;
use App\Jobs\GenerateBulkPayrollJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Filament\Support\RawJs;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * PayrollResource
 * 
 * Enhanced Filament resource for managing employee payroll records.
 * Features:
 * - Month/Year based filtering (replaces date range filters)
 * - Quick filter presets for better data management
 * - Default view shows current month data
 * - Smart attendance calculation and salary components
 */
class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * Helper function to safely convert value to numeric
     */
    public static function safeNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove common currency symbols and separators
            $cleaned = preg_replace('/[^\d.,\-]/', '', $value);
            // Replace comma with dot for decimal separator
            $cleaned = str_replace(',', '.', $cleaned);
            // Handle multiple dots (keep only the last one as decimal separator)
            $parts = explode('.', $cleaned);
            if (count($parts) > 2) {
                $cleaned = implode('', array_slice($parts, 0, -1)) . '.' . end($parts);
            }
            return is_numeric($cleaned) ? (float) $cleaned : 0;
        }

        return 0;
    }

    /**
     * Helper function to safely sum nominal values from collection
     */
    public static function safeSumNominal($items): float
    {
        return collect($items)->sum(function ($item) {
            return static::safeNumeric($item['nominal'] ?? 0);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📋 Information')
                    ->description('💡 After using "Calculate from Attendance", please save the form to persist the calculated data.')
                    ->schema([])
                    ->collapsible()
                    ->collapsed()
                    ->compact(),

                Forms\Components\Section::make('Employee & Period')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    // Load default salary data from employee
                                    static::loadEmployeeDefaults($state, $set);

                                    // Auto-fill from attendance if period is set
                                    if ($get('period_start_date') && $get('period_end_date')) {
                                        static::autoFillFromAttendance($state, $get('period_start_date'), $get('period_end_date'), $set, $get);
                                    }
                                }
                            })
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    // Load default salary data when editing existing record
                                    static::loadEmployeeDefaults($state, $set, true);
                                }
                            }),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start_date')
                                    ->required()
                                    ->default(now()->startOfMonth())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state && $get('employee_id') && $get('period_end_date')) {
                                            static::autoFillFromAttendance($get('employee_id'), $state, $get('period_end_date'), $set, $get);
                                        }
                                    }),
                                Forms\Components\DatePicker::make('period_end_date')
                                    ->required()
                                    ->default(now()->endOfMonth())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state && $get('employee_id') && $get('period_start_date')) {
                                            static::autoFillFromAttendance($get('employee_id'), $get('period_start_date'), $state, $set, $get);
                                        }
                                    }),
                            ]),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('calculate_from_attendance')
                                ->label('🤖 Calculate from Attendance')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Calculate from Attendance')
                                ->modalDescription('This will calculate salary data based on attendance records. Any existing data will be overwritten.')
                                ->modalSubmitActionLabel('Calculate & Fill')
                                ->action(function (callable $set, callable $get) {
                                    if ($get('employee_id') && $get('period_start_date') && $get('period_end_date')) {
                                        static::autoFillFromAttendance($get('employee_id'), $get('period_start_date'), $get('period_end_date'), $set, $get);

                                        // Trigger recalculation to ensure all totals are updated
                                        static::calculateTotals($set, $get);

                                        \Filament\Notifications\Notification::make()
                                            ->title('✅ Data Calculated Successfully')
                                            ->body('Attendance data has been calculated and filled. ⚠️ Don\'t forget to SAVE the form to persist these changes!')
                                            ->success()
                                            ->persistent()
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('remind_later')
                                                    ->label('Remind me to save')
                                                    ->close(),
                                            ])
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title('❌ Missing Information')
                                            ->body('Please select an employee and set the period dates first.')
                                            ->warning()
                                            ->send();
                                    }
                                }),
                        ]),
                    ])->columns(1),

                Forms\Components\Section::make('Attendance Summary')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('work_days')
                                    ->label('Work Days')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),
                                Forms\Components\TextInput::make('leave_days')
                                    ->label('Leave Days')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),
                                Forms\Components\TextInput::make('permission_days')
                                    ->label('Permission Days')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),
                                Forms\Components\TextInput::make('absent_days')
                                    ->label('Absent Days')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),
                            ]),
                        Forms\Components\TextInput::make('overtime_hours')
                            ->label('Overtime Hours')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('hours'),
                    ]),

                Forms\Components\Section::make('Salary Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Repeater::make('employee_income_view')
                                    ->label('Income Components')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Component Name')
                                            ->required()
                                            ->placeholder('e.g., Gaji Pokok, Uang Makan, etc.'),
                                        Forms\Components\TextInput::make('nominal')
                                            ->label('Amount')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money(String($input), \'.\', \',\', 0)'))
                                            ->stripCharacters(',')
                                            ->required()
                                            ->numeric()
                                            ->live(debounce: '1s')
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                static::calculateTotals($set, $get);
                                            }),
                                    ])
                                    ->live(debounce: '1s')
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        static::calculateTotals($set, $get);
                                    })
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Income Component')
                                    ->columns(2),
                                Forms\Components\Repeater::make('employee_expense_view')
                                    ->label('Deduction Components')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Component Name')
                                            ->required()
                                            ->placeholder('e.g., BPJS, Tax, etc.'),
                                        Forms\Components\TextInput::make('nominal')
                                            ->label('Amount')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money(String($input), \'.\', \',\', 0)'))
                                            ->stripCharacters(',')
                                            ->required()
                                            ->numeric()
                                            ->live(debounce: '1s')
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                static::calculateTotals($set, $get);
                                            }),
                                    ])
                                    ->live(debounce: '1s')
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        static::calculateTotals($set, $get);
                                    })
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Deduction Component')
                                    ->columns(2),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('total_income_display')
                                    ->label('💰 Total Income')
                                    ->content(function (callable $get): string {
                                        $incomes = collect($get('employee_income_view'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['nominal']);
                                        });
                                        return 'Rp. ' . number_format($incomes, 0, ',', '.');
                                    }),
                                Forms\Components\Placeholder::make('total_expense_display')
                                    ->label('📉 Total Deductions')
                                    ->content(function (callable $get): string {
                                        $expenses = collect($get('employee_expense_view'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['nominal']);
                                        });
                                        return 'Rp. ' . number_format($expenses, 0, ',', '.');
                                    }),
                                Forms\Components\Placeholder::make('net_salary_display')
                                    ->label('🎯 Net Salary Preview')
                                    ->content(function (callable $get): string {
                                        $incomes = collect($get('employee_income_view'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['nominal']);
                                        });
                                        $expenses = collect($get('employee_expense_view'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['nominal']);
                                        });
                                        $netSalary = $incomes - $expenses;
                                        return 'Rp. ' . number_format($netSalary, 0, ',', '.');
                                    }),
                            ]),
                    ]),
                Forms\Components\Section::make('Final Calculation')
                    ->schema([
                        Forms\Components\TextInput::make('final_salary')
                            ->label('🎯 Final Salary')
                            ->required()
                            ->columnSpanFull()
                            ->numeric()
                            ->prefix('Rp.')
                            ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                            ->stripCharacters(',')
                            ->extraAttributes(['class' => 'text-lg font-bold'])
                            ->live()
                            ->helperText('This value is automatically calculated from income and expense components above. You can manually adjust if needed.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('👤 Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->tooltip('Click to copy employee name'),

                Tables\Columns\TextColumn::make('employee.employee_id')
                    ->label('🆔 ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('period')
                    ->label('📅 Period')
                    ->getStateUsing(
                        fn($record) =>
                        Carbon::parse($record->period_start_date)->format('d M') .
                            ' - ' .
                            Carbon::parse($record->period_end_date)->format('d M Y')
                    )
                    ->searchable(['period_start_date', 'period_end_date'])
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('employee.position.department.name')
                    ->label('🏢 Department')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('employee.position.title')
                    ->label('💼 Position')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('secondary'),

                Tables\Columns\ViewColumn::make('attendance_summary')
                    ->label('📊 Attendance Summary')
                    ->view('filament.tables.columns.attendance-summary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('work_days')
                    ->label('✅ Work Days')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state >= 20 ? 'success' : ($state >= 15 ? 'warning' : 'danger'))
                    ->suffix(' days')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('leave_days')
                    ->label('🏖️ Leave')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 3 ? 'warning' : 'success')
                    ->suffix(' days')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('permission_days')
                    ->label('📝 Permission')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->suffix(' days')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('absent_days')
                    ->label('❌ Absent')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->suffix(' days')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('⏰ Overtime')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'warning' : 'gray')
                    ->suffix(' hrs')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('final_salary')
                    ->label('💰 Final Salary')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->copyable()
                    ->tooltip('Click to copy salary amount')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total Salary'),
                        Tables\Columns\Summarizers\Average::make()
                            ->money('IDR')
                            ->label('Average Salary'),
                    ]),

                Tables\Columns\TextColumn::make('generatedBy.name')
                    ->label('👨‍💼 Generated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('generated_at')
                    ->label('🕒 Generated At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn($record) => $record->generated_at
                        ? 'Generated ' . \Carbon\Carbon::parse($record->generated_at)->format('l, d F Y \a\t H:i:s')
                        : 'Not generated yet')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('📈 Status')
                    ->getStateUsing(function ($record) {
                        $now = now();
                        $generatedAt = $record->generated_at;

                        if (!$generatedAt) return 'Draft';

                        // Ensure generatedAt is a Carbon instance
                        if (is_string($generatedAt)) {
                            $generatedAt = \Carbon\Carbon::parse($generatedAt);
                        }

                        $daysSince = $generatedAt->diffInDays($now);

                        if ($daysSince <= 7) return 'Recent';
                        if ($daysSince <= 30) return 'Current';
                        return 'Archived';
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Draft' => 'gray',
                        'Recent' => 'success',
                        'Current' => 'info',
                        'Archived' => 'warning',
                        default => 'gray'
                    })
                    ->icon(fn($state) => match ($state) {
                        'Draft' => 'heroicon-m-pencil',
                        'Recent' => 'heroicon-m-check-circle',
                        'Current' => 'heroicon-m-clock',
                        'Archived' => 'heroicon-m-archive-box',
                        default => 'heroicon-m-question-mark-circle'
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('👤 Employee'),

                Tables\Filters\SelectFilter::make('department')
                    ->options(function () {
                        return \App\Models\Department::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['values'])) {
                            $query->whereHas('employee.position', function (Builder $q) use ($data) {
                                $q->whereIn('department_id', $data['values']);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->multiple()
                    ->label('🏢 Department'),

                Tables\Filters\SelectFilter::make('position')
                    ->options(function () {
                        return \App\Models\Position::pluck('title', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['values'])) {
                            $query->whereHas('employee', function (Builder $q) use ($data) {
                                $q->whereIn('position_id', $data['values']);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->multiple()
                    ->label('💼 Position'),

                Tables\Filters\SelectFilter::make('period_month')
                    ->label('🗓️ Month')
                    ->options([
                        '1' => 'January',
                        '2' => 'February',
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->default(now()->month)
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereMonth('period_start_date', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('period_year')
                    ->label('📅 Year')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($i = $currentYear - 5; $i <= $currentYear + 2; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->default(now()->year)
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereYear('period_start_date', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\Filter::make('salary_range')
                    ->form([
                        Forms\Components\TextInput::make('salary_from')
                            ->label('Salary From')
                            ->numeric()
                            ->prefix('Rp.'),
                        Forms\Components\TextInput::make('salary_until')
                            ->label('Salary Until')
                            ->numeric()
                            ->prefix('Rp.'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['salary_from'],
                                fn(Builder $query, $amount): Builder => $query->where('final_salary', '>=', $amount),
                            )
                            ->when(
                                $data['salary_until'],
                                fn(Builder $query, $amount): Builder => $query->where('final_salary', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['salary_from'] ?? null) {
                            $indicators['salary_from'] = 'Salary from Rp ' . number_format($data['salary_from'], 0, ',', '.');
                        }
                        if ($data['salary_until'] ?? null) {
                            $indicators['salary_until'] = 'Salary until Rp ' . number_format($data['salary_until'], 0, ',', '.');
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('attendance_issues')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('absent_days', '>', 0))
                    ->label('🚨 Has Attendance Issues'),

                Tables\Filters\Filter::make('has_overtime')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('overtime_hours', '>', 0))
                    ->label('⏰ Has Overtime'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'recent' => '🟢 Recent (Last 7 days)',
                        'current' => '🔵 Current (Last 30 days)',
                        'archived' => '🟡 Archived (Older than 30 days)',
                        'draft' => '⚪ Draft',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return $query;

                        $now = now();

                        return match ($data['value']) {
                            'recent' => $query->where('generated_at', '>=', $now->subDays(7)),
                            'current' => $query->where('generated_at', '>=', $now->subDays(30))
                                ->where('generated_at', '<', $now->subDays(7)),
                            'archived' => $query->where('generated_at', '<', $now->subDays(30)),
                            'draft' => $query->whereNull('generated_at'),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('generated_by')
                    ->relationship('generatedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->label('👨‍💼 Generated By'),

                Tables\Filters\SelectFilter::make('quick_filter')
                    ->label('🚀 Quick Filters')
                    ->options([
                        'current_month' => '📅 Current Month (' . now()->format('F Y') . ')',
                        'last_month' => '📆 Last Month (' . now()->subMonth()->format('F Y') . ')',
                        'last_3_months' => '📊 Last 3 Months',
                        'current_year' => '🗓️ Current Year (' . now()->year . ')',
                        'recent_generated' => '🕐 Recently Generated (Last 7 Days)',
                        'drafts_only' => '📝 Drafts Only',
                        'high_salary' => '💰 High Salary (> 10M)',
                        'attendance_issues' => '🚨 Has Attendance Issues',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $now = now();

                            switch ($data['value']) {
                                case 'current_month':
                                    $query->whereMonth('period_start_date', $now->month)
                                        ->whereYear('period_start_date', $now->year);
                                    break;

                                case 'last_month':
                                    $lastMonth = $now->copy()->subMonth();
                                    $query->whereMonth('period_start_date', $lastMonth->month)
                                        ->whereYear('period_start_date', $lastMonth->year);
                                    break;

                                case 'last_3_months':
                                    $query->where('period_start_date', '>=', $now->copy()->subMonths(3)->startOfMonth());
                                    break;

                                case 'current_year':
                                    $query->whereYear('period_start_date', $now->year);
                                    break;

                                case 'recent_generated':
                                    $query->where('generated_at', '>=', $now->copy()->subDays(7));
                                    break;

                                case 'drafts_only':
                                    $query->whereNull('generated_at');
                                    break;

                                case 'high_salary':
                                    $query->where('final_salary', '>', 10000000);
                                    break;

                                case 'attendance_issues':
                                    $query->where('absent_days', '>', 0);
                                    break;
                            }
                        }
                        return $query;
                    })
                    ->placeholder('Select quick filter...'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filters')
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View')
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->color('warning'),
                    Tables\Actions\Action::make('recalculate')
                        ->label('Recalculate')
                        ->icon('heroicon-m-calculator')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Recalculate Payroll')
                        ->modalDescription('This will recalculate the payroll based on current attendance data. Continue?')
                        ->action(function ($record) {
                            try {
                                $calculator = app(\App\Services\PayrollCalculatorService::class);
                                $payrollData = $calculator->calculatePayrollData(
                                    $record->employee,
                                    $record->period_start_date,
                                    $record->period_end_date
                                );

                                $record->update([
                                    'work_days' => $payrollData['work_days'],
                                    'leave_days' => $payrollData['leave_days'],
                                    'permission_days' => $payrollData['permission_days'],
                                    'absent_days' => $payrollData['absent_days'],
                                    'overtime_hours' => $payrollData['overtime_hours'],
                                    'employee_income' => $payrollData['employee_income'],
                                    'employee_expense' => $payrollData['employee_expense'],
                                    'final_salary' => $payrollData['final_salary'],
                                    'generated_by' => auth()->id(),
                                    'generated_at' => now(),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('✅ Payroll Recalculated')
                                    ->body("Payroll for {$record->employee->name} has been recalculated successfully.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('❌ Recalculation Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->action(function ($record) {
                            $newPayroll = $record->replicate();
                            $newPayroll->generated_by = auth()->id();
                            $newPayroll->generated_at = now();
                            $newPayroll->save();

                            \Filament\Notifications\Notification::make()
                                ->title('✅ Payroll Duplicated')
                                ->body("Payroll for {$record->employee->name} has been duplicated.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('export_pdf')
                        ->label('Export PDF')
                        ->icon('heroicon-m-document-arrow-down')
                        ->color('info')
                        ->action(function ($record) {
                            return static::exportPayrollPdf($record);
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Delete')
                        ->color('danger'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('recalculate_selected')
                        ->label('Recalculate from Attendance')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Recalculate Selected Payrolls')
                        ->modalDescription('This will recalculate payroll data based on attendance and overtime records. Are you sure?')
                        ->action(function (Collection $records) {
                            return static::recalculateSelectedPayrolls($records);
                        }),
                    Tables\Actions\BulkAction::make('generate_bulk_payroll')
                        ->label('Generate Bulk Payroll')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('period_start_date')
                                        ->label('Period Start Date')
                                        ->required()
                                        ->default(now()->startOfMonth()),
                                    Forms\Components\DatePicker::make('period_end_date')
                                        ->label('Period End Date')
                                        ->required()
                                        ->default(now()->endOfMonth()),
                                ]),
                            Forms\Components\Select::make('generation_mode')
                                ->label('Generation Mode')
                                ->options([
                                    'department' => 'By Department',
                                    'position' => 'By Position',
                                    'all_active' => 'All Active Employees',
                                ])
                                ->required()
                                ->live(),
                            Forms\Components\Select::make('department_id')
                                ->label('Department')
                                ->options(\App\Models\Department::pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn($get) => $get('generation_mode') === 'department'),
                            Forms\Components\Select::make('position_id')
                                ->label('Position')
                                ->options(\App\Models\Position::pluck('title', 'id'))
                                ->searchable()
                                ->visible(fn($get) => $get('generation_mode') === 'position'),
                            Forms\Components\Toggle::make('run_in_background')
                                ->label('Run in Background')
                                ->default(true),
                        ])
                        ->action(function (array $data) {
                            return static::generateBulkPayroll($data);
                        }),
                    Tables\Actions\BulkAction::make('export_bulk_pdf')
                        ->label('Export PDF (Bulk)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Collection $records) {
                            return static::exportBulkPayrollPdf($records);
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('auto_calculate')
                    ->label('Auto Calculate from Attendance')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start_date')
                                    ->label('Period Start Date')
                                    ->required()
                                    ->default(now()->startOfMonth()),
                                Forms\Components\DatePicker::make('period_end_date')
                                    ->label('Period End Date')
                                    ->required()
                                    ->default(now()->endOfMonth()),
                            ]),
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $get('period_start_date') && $get('period_end_date')) {
                                    static::previewCalculation($state, $get('period_start_date'), $get('period_end_date'), $set);
                                }
                            }),
                        Forms\Components\Section::make('Attendance Preview')
                            ->schema([
                                Forms\Components\Placeholder::make('work_days_preview')
                                    ->label('Work Days')
                                    ->content(fn($get) => $get('preview_work_days') ?? 'Select employee and period'),
                                Forms\Components\Placeholder::make('overtime_hours_preview')
                                    ->label('Overtime Hours')
                                    ->content(fn($get) => $get('preview_overtime_hours') ?? 'Select employee and period'),
                                Forms\Components\Placeholder::make('final_salary_preview')
                                    ->label('Estimated Final Salary')
                                    ->content(fn($get) => 'Rp. ' . number_format($get('preview_final_salary') ?? 0, 0, ',', '.')),
                            ])
                            ->columns(3)
                            ->visible(fn($get) => $get('employee_id') && $get('period_start_date') && $get('period_end_date')),
                        Forms\Components\Toggle::make('create_payroll')
                            ->label('Create Payroll Record')
                            ->helperText('Create payroll record based on calculated data')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        return static::autoCalculatePayroll($data);
                    })
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start_date')
                                    ->label('Period Start Date')
                                    ->required()
                                    ->default(now()->startOfMonth()),
                                Forms\Components\DatePicker::make('period_end_date')
                                    ->label('Period End Date')
                                    ->required()
                                    ->default(now()->endOfMonth()),
                            ]),
                    ])
                    ->action(function (array $data) {
                        return static::autoCalculatePayroll($data);
                    }),
                Tables\Actions\Action::make('export_all_pdf')
                    ->label('Export All PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Export All Payroll PDFs')
                    ->modalDescription('This will export all visible payroll records as PDF files in a ZIP archive. Continue?')
                    ->action(function () {
                        return static::exportAllPayrollPdfs();
                    }),
                Tables\Actions\Action::make('generate_payroll')
                    ->label('Generate Payroll')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start_date')
                                    ->label('Period Start Date')
                                    ->required()
                                    ->default(now()->startOfMonth()),
                                Forms\Components\DatePicker::make('period_end_date')
                                    ->label('Period End Date')
                                    ->required()
                                    ->default(now()->endOfMonth()),
                            ]),
                        Forms\Components\Select::make('target_type')
                            ->label('Target')
                            ->options([
                                'all' => 'All Active Employees',
                                'department' => 'By Department',
                                'position' => 'By Position',
                                'employees' => 'Specific Employees',
                            ])
                            ->required()
                            ->default('all')
                            ->live(),
                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->options(\App\Models\Department::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn($get) => $get('target_type') === 'department'),
                        Forms\Components\Select::make('position_id')
                            ->label('Position')
                            ->options(\App\Models\Position::pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn($get) => $get('target_type') === 'position'),
                        Forms\Components\Select::make('employee_ids')
                            ->label('Employees')
                            ->options(\App\Models\Employee::pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn($get) => $get('target_type') === 'employees'),
                        Forms\Components\Toggle::make('run_in_background')
                            ->label('Run in Background')
                            ->disabled()
                            ->helperText('Process payroll generation as a background job')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        return static::generatePayroll($data);
                    }),
            ])
            ->defaultSort('generated_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->extremePaginationLinks()
            ->poll('30s')
            ->emptyStateHeading('📋 No Payroll Records Found')
            ->emptyStateDescription('Start by generating payroll for your employees.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\Action::make('generate_first_payroll')
                    ->label('🚀 Generate First Payroll')
                    ->button()
                    ->color('primary'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        // dd($infolist->getRecord()->employee_income_view);
        return $infolist
            ->schema([
                Infolists\Components\Section::make('👤 Employee Information')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('employee.name')
                                            ->label('Full Name')
                                            ->icon('heroicon-m-user')
                                            ->copyable()
                                            ->weight('bold')
                                            ->size('lg')
                                            ->color('primary'),
                                        Infolists\Components\TextEntry::make('employee.employee_id')
                                            ->label('Employee ID')
                                            ->icon('heroicon-m-identification')
                                            ->badge()
                                            ->color('gray')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('employee.position.title')
                                            ->label('Position')
                                            ->icon('heroicon-m-briefcase')
                                            ->badge()
                                            ->color('primary'),
                                        Infolists\Components\TextEntry::make('employee.position.department.name')
                                            ->label('Department')
                                            ->icon('heroicon-m-building-office')
                                            ->badge()
                                            ->color('info'),
                                        Infolists\Components\TextEntry::make('employee.email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->copyable()
                                            ->placeholder('No email provided')
                                            ->color('info'),
                                        Infolists\Components\TextEntry::make('employee.join_date')
                                            ->label('Join Date')
                                            ->icon('heroicon-m-calendar')
                                            ->date('d F Y')
                                            ->copyable()
                                            ->placeholder('No join date provided')
                                            ->color('info'),
                                    ])->columnSpan(1),
                                    Infolists\Components\Group::make([
                                        Infolists\Components\ImageEntry::make('employee.photo')
                                            ->label('')
                                            ->circular()
                                            ->size(120)
                                            ->defaultImageUrl(function ($record) {
                                                return 'https://ui-avatars.com/api/?name=' . urlencode($record->employee->name ?? 'Employee') . '&color=7F9CF5&background=EBF4FF&size=120';
                                            }),
                                        Infolists\Components\TextEntry::make('employee.phone')
                                            ->label('Phone')
                                            ->icon('heroicon-m-phone')
                                            ->copyable()
                                            ->placeholder('No phone provided'),
                                    ])->columnSpan(1),
                                ]),
                        ]),
                    ])
                    ->icon('heroicon-m-user-circle')
                    ->description('Employee details and contact information')
                    ->collapsible()
                    ->extraAttributes(['class' => 'border-l-4 border-l-blue-500 bg-gradient-to-r from-blue-50/50 to-transparent dark:from-blue-900/20 dark:to-transparent']),

                Infolists\Components\Section::make('📅 Payroll Period')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('period_start_date')
                                    ->label('Period Start')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar')
                                    ->badge()
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('period_end_date')
                                    ->label('Period End')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar')
                                    ->badge()
                                    ->color('danger'),
                                Infolists\Components\TextEntry::make('period_duration')
                                    ->label('Duration')
                                    ->getStateUsing(function ($record) {
                                        $start = Carbon::parse($record->period_start_date);
                                        $end = Carbon::parse($record->period_end_date);
                                        return $start->diffInDays($end) + 1 . ' days';
                                    })
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ])
                    ->icon('heroicon-m-calendar-days')
                    ->collapsible(),

                Infolists\Components\Section::make('📊 Attendance Summary')
                    ->schema([
                        Infolists\Components\Grid::make(5)
                            ->schema([
                                Infolists\Components\TextEntry::make('work_days')
                                    ->label('Work Days')
                                    ->numeric()
                                    ->suffix(' days')
                                    ->icon('heroicon-m-check-circle')
                                    ->badge()
                                    ->color(fn($state) => $state >= 20 ? 'success' : ($state >= 15 ? 'warning' : 'danger')),
                                Infolists\Components\TextEntry::make('leave_days')
                                    ->label('Leave Days')
                                    ->numeric()
                                    ->suffix(' days')
                                    ->icon('heroicon-m-sun')
                                    ->badge()
                                    ->color(fn($state) => $state > 3 ? 'warning' : 'success'),
                                Infolists\Components\TextEntry::make('permission_days')
                                    ->label('Permission Days')
                                    ->numeric()
                                    ->suffix(' days')
                                    ->icon('heroicon-m-document-text')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('absent_days')
                                    ->label('Absent Days')
                                    ->numeric()
                                    ->suffix(' days')
                                    ->icon('heroicon-m-x-circle')
                                    ->badge()
                                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),
                                Infolists\Components\TextEntry::make('overtime_hours')
                                    ->label('Overtime Hours')
                                    ->numeric()
                                    ->suffix(' hrs')
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color(fn($state) => $state > 0 ? 'warning' : 'gray'),
                            ]),
                        Infolists\Components\TextEntry::make('attendance_performance')
                            ->label('📈 Attendance Performance')
                            ->getStateUsing(function ($record) {
                                $totalDays = $record->work_days + $record->leave_days + $record->permission_days + $record->absent_days;
                                if ($totalDays > 0) {
                                    $workPercentage = round(($record->work_days / $totalDays) * 100, 1);
                                    $attendanceScore = $record->absent_days == 0 ? 'Excellent' : ($record->absent_days <= 2 ? 'Good' : 'Needs Improvement');
                                    return "Work Rate: {$workPercentage}% • Performance: {$attendanceScore}";
                                }
                                return 'No attendance data';
                            })
                            ->badge()
                            ->color(function ($record) {
                                $totalDays = $record->work_days + $record->leave_days + $record->permission_days + $record->absent_days;
                                if ($totalDays > 0) {
                                    $workPercentage = ($record->work_days / $totalDays) * 100;
                                    return $workPercentage >= 80 ? 'success' : ($workPercentage >= 60 ? 'warning' : 'danger');
                                }
                                return 'gray';
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-center mt-4']),
                    ])
                    ->icon('heroicon-m-chart-bar')
                    ->description('Daily attendance breakdown and performance metrics')
                    ->extraAttributes(['class' => 'border-l-4 border-l-indigo-500 bg-gradient-to-r from-indigo-50/50 to-transparent dark:from-indigo-900/20 dark:to-transparent'])
                    ->collapsible(),

                Infolists\Components\Section::make('💰 Income Components')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('employee_income_view')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Component Name')
                                            ->weight('medium')
                                            ->icon('heroicon-m-plus-circle')
                                            ->color('success')
                                            ->extraAttributes(['class' => 'capitalize']),
                                        Infolists\Components\TextEntry::make('nominal')
                                            ->label('Amount')
                                            ->money('IDR')
                                            ->weight('bold')
                                            ->color('success')
                                            ->copyable(),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(false)
                            ->extraAttributes(['class' => 'space-y-2']),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('income_count')
                                    ->label('📊 Components Count')
                                    ->getStateUsing(function ($record) {
                                        $incomes = $record->employee_income_view ?? [];
                                        return count($incomes) . ' components';
                                    })
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('total_income')
                                    ->label('💰 Total Income')
                                    ->getStateUsing(function ($record) {
                                        $incomes = $record->employee_income_view ?? [];
                                        return static::safeSumNominal($incomes);
                                    })
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success')
                                    ->copyable()
                                    ->extraAttributes(['class' => 'text-right']),
                            ])
                            ->extraAttributes(['class' => 'mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800']),
                    ])
                    ->icon('heroicon-m-plus-circle')
                    ->description('All income components for this payroll period')
                    ->extraAttributes(['class' => 'border-l-4 border-l-green-500 bg-gradient-to-r from-green-50/50 to-transparent dark:from-green-900/20 dark:to-transparent'])
                    ->collapsible(),

                Infolists\Components\Section::make('📉 Deduction Components')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('employee_expense_view')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Component Name')
                                            ->weight('medium')
                                            ->icon('heroicon-m-minus-circle')
                                            ->color('danger')
                                            ->extraAttributes(['class' => 'capitalize']),
                                        Infolists\Components\TextEntry::make('nominal')
                                            ->label('Amount')
                                            ->money('IDR')
                                            ->weight('bold')
                                            ->color('danger')
                                            ->copyable(),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(false)
                            ->extraAttributes(['class' => 'space-y-2']),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('expense_count')
                                    ->label('📊 Components Count')
                                    ->getStateUsing(function ($record) {
                                        $expenses = $record->employee_expense_view ?? [];
                                        return count($expenses) . ' components';
                                    })
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('total_expense')
                                    ->label('📉 Total Deductions')
                                    ->getStateUsing(function ($record) {
                                        $expenses = $record->employee_expense_view ?? [];
                                        return static::safeSumNominal($expenses);
                                    })
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('danger')
                                    ->copyable()
                                    ->extraAttributes(['class' => 'text-right']),
                            ])
                            ->extraAttributes(['class' => 'mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800']),
                    ])
                    ->icon('heroicon-m-minus-circle')
                    ->description('All deduction components for this payroll period')
                    ->extraAttributes(['class' => 'border-l-4 border-l-red-500 bg-gradient-to-r from-red-50/50 to-transparent dark:from-red-900/20 dark:to-transparent'])
                    ->collapsible(),

                Infolists\Components\Section::make('🎯 Final Calculation')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('gross_income')
                                    ->label('💰 Gross Income')
                                    ->getStateUsing(function ($record) {
                                        $incomes = $record->employee_income_view ?? [];
                                        return collect($incomes)->sum('nominal');
                                    })
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success')
                                    ->copyable()
                                    ->extraAttributes(['class' => 'text-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800']),
                                Infolists\Components\TextEntry::make('total_deductions')
                                    ->label('📉 Total Deductions')
                                    ->getStateUsing(function ($record) {
                                        $expenses = $record->employee_expense_view ?? [];
                                        return collect($expenses)->sum('nominal');
                                    })
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('danger')
                                    ->copyable()
                                    ->extraAttributes(['class' => 'text-center p-6 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800']),
                                Infolists\Components\TextEntry::make('final_salary')
                                    ->label('🎯 Net Salary')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->size('xl')
                                    ->color('primary')
                                    ->copyable()
                                    ->extraAttributes(['class' => 'text-center p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-300 dark:border-blue-700 shadow-lg transform hover:scale-105 transition-transform duration-200']),
                            ]),
                        Infolists\Components\TextEntry::make('salary_percentage')
                            ->label('📊 Salary Analysis')
                            ->getStateUsing(function ($record) {
                                $incomes = $record->employee_income_view ?? [];
                                $expenses = $record->employee_expense_view ?? [];
                                $totalIncome = collect($incomes)->sum('nominal');
                                $totalExpense = collect($expenses)->sum('nominal');

                                if ($totalIncome > 0) {
                                    $deductionPercentage = round(($totalExpense / $totalIncome) * 100, 2);
                                    $netPercentage = round((($totalIncome - $totalExpense) / $totalIncome) * 100, 2);

                                    return "Deductions: {$deductionPercentage}% • Net Take-home: {$netPercentage}%";
                                }
                                return 'No income data available';
                            })
                            ->badge()
                            ->color('info')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-center mt-4']),
                    ])
                    ->icon('heroicon-m-calculator')
                    ->description('Final salary calculation with breakdown')
                    ->extraAttributes(['class' => 'border-l-4 border-l-green-500 bg-gradient-to-r from-green-50/50 to-transparent dark:from-green-900/20 dark:to-transparent'])
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('recalculate')
                            ->label('Recalculate')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Recalculate Payroll')
                            ->modalDescription('This will recalculate the payroll based on current attendance data. Continue?')
                            ->action(function ($record) {
                                try {
                                    $calculator = app(\App\Services\PayrollCalculatorService::class);
                                    $payrollData = $calculator->calculatePayrollData(
                                        $record->employee,
                                        $record->period_start_date,
                                        $record->period_end_date
                                    );

                                    $record->update([
                                        'work_days' => $payrollData['work_days'],
                                        'leave_days' => $payrollData['leave_days'],
                                        'permission_days' => $payrollData['permission_days'],
                                        'absent_days' => $payrollData['absent_days'],
                                        'overtime_hours' => $payrollData['overtime_hours'],
                                        'employee_income' => $payrollData['employee_income'],
                                        'employee_expense' => $payrollData['employee_expense'],
                                        'final_salary' => $payrollData['final_salary'],
                                        'generated_by' => auth()->id(),
                                        'generated_at' => now(),
                                    ]);

                                    \Filament\Notifications\Notification::make()
                                        ->title('✅ Payroll Recalculated')
                                        ->body("Payroll has been recalculated successfully.")
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('❌ Recalculation Failed')
                                        ->body('Error: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                        Infolists\Components\Actions\Action::make('export_pdf')
                            ->label('Export PDF')
                            ->icon('heroicon-m-document-arrow-down')
                            ->color('info')
                            ->action(function ($record) {
                                return static::exportPayrollPdf($record);
                            }),
                    ]),

                Infolists\Components\Section::make('📋 Payroll Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('generatedBy.name')
                                    ->label('Generated By')
                                    ->icon('heroicon-m-user')
                                    ->badge()
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('generated_at')
                                    ->label('Generated At')
                                    ->dateTime('d F Y, H:i:s')
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->getStateUsing(function ($record) {
                                        $now = now();
                                        $generatedAt = $record->generated_at;

                                        if (!$generatedAt) return 'Draft';

                                        if (is_string($generatedAt)) {
                                            $generatedAt = \Carbon\Carbon::parse($generatedAt);
                                        }

                                        $daysSince = $generatedAt->diffInDays($now);

                                        if ($daysSince <= 7) return 'Recent';
                                        if ($daysSince <= 30) return 'Current';
                                        return 'Archived';
                                    })
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'Draft' => 'gray',
                                        'Recent' => 'success',
                                        'Current' => 'info',
                                        'Archived' => 'warning',
                                        default => 'gray'
                                    })
                                    ->icon(fn($state) => match ($state) {
                                        'Draft' => 'heroicon-m-pencil',
                                        'Recent' => 'heroicon-m-check-circle',
                                        'Current' => 'heroicon-m-clock',
                                        'Archived' => 'heroicon-m-archive-box',
                                        default => 'heroicon-m-question-mark-circle'
                                    }),
                            ]),
                    ])
                    ->icon('heroicon-m-information-circle')
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('🧮 Calculation Breakdown')
                    ->schema([
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('calculation_summary')
                                    ->label('Summary')
                                    ->getStateUsing(function ($record) {
                                        $incomes = $record->employee_income_view ?? [];
                                        $expenses = $record->employee_expense_view ?? [];
                                        $totalIncome = collect($incomes)->sum('nominal');
                                        $totalExpense = collect($expenses)->sum('nominal');

                                        $summary = "📊 Calculation Details:\n\n";
                                        $summary .= "• Work Days: {$record->work_days} days\n";
                                        $summary .= "• Leave Days: {$record->leave_days} days\n";
                                        $summary .= "• Permission Days: {$record->permission_days} days\n";
                                        $summary .= "• Absent Days: {$record->absent_days} days\n";
                                        $summary .= "• Overtime Hours: {$record->overtime_hours} hours\n\n";
                                        $summary .= "💰 Income Components: " . count($incomes) . " items\n";
                                        $summary .= "📉 Deduction Components: " . count($expenses) . " items\n\n";
                                        $summary .= "💵 Total Income: Rp " . number_format($totalIncome, 0, ',', '.') . "\n";
                                        $summary .= "💸 Total Deductions: Rp " . number_format($totalExpense, 0, ',', '.') . "\n";
                                        $summary .= "🎯 Net Salary: Rp " . number_format($record->final_salary, 0, ',', '.');

                                        return $summary;
                                    })
                                    ->prose()
                                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg font-mono text-sm border border-gray-200 dark:border-gray-700']),
                            ]),
                    ])
                    ->icon('heroicon-m-calculator')
                    ->collapsible()
                    ->collapsed(),
            ])->columns(1);
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
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Generate payroll based on form data
     */
    public static function generatePayroll(array $data): void
    {
        try {
            $options = [];

            // Set period options
            if (isset($data['period_start_date']) && isset($data['period_end_date'])) {
                $startDate = Carbon::parse($data['period_start_date']);
                $endDate = Carbon::parse($data['period_end_date']);

                $options['month'] = $startDate->month;
                $options['year'] = $startDate->year;
            }

            // Set target options
            switch ($data['target_type']) {
                case 'department':
                    if (isset($data['department_id'])) {
                        $options['department_id'] = $data['department_id'];
                    }
                    break;
                case 'position':
                    if (isset($data['position_id'])) {
                        $options['position_id'] = $data['position_id'];
                    }
                    break;
                case 'employees':
                    if (isset($data['employee_ids'])) {
                        $options['employee_ids'] = $data['employee_ids'];
                    }
                    break;
            }

            if ($data['run_in_background'] ?? false) {
                // Dispatch as background job
                \App\Jobs\GenerateMonthlyPayrollJob::dispatch($options, auth()->id());

                \Filament\Notifications\Notification::make()
                    ->title('Payroll Generation Started')
                    ->body('Payroll generation has been queued and will run in the background. You will be notified when it completes.')
                    ->success()
                    ->send();
            } else {
                // Run synchronously
                $payrollService = app(\App\Services\PayrollGenerationService::class);
                $calculator = app(\App\Services\PayrollCalculatorService::class);

                $period = $calculator->getPayrollPeriod($options['month'] ?? now()->month, $options['year'] ?? now()->year);
                if (isset($options['department_id'])) {
                    $results = $payrollService->generatePayrollByDepartment(
                        $options['department_id'],
                        $period['start_date'],
                        $period['end_date'],
                        auth()->id()
                    );
                } elseif (isset($options['position_id'])) {
                    $results = $payrollService->generatePayrollByPosition(
                        $options['position_id'],
                        $period['start_date'],
                        $period['end_date'],
                        auth()->id()
                    );
                } elseif (isset($options['employee_ids'])) {
                    $results = $payrollService->generateBulkPayroll(
                        $options['employee_ids'],
                        $period['start_date'],
                        $period['end_date'],
                        auth()->id()
                    );
                } else {
                    $results = $payrollService->generateAllEmployeesPayroll(
                        $period['start_date'],
                        $period['end_date'],
                        auth()->id()
                    );
                }

                \Filament\Notifications\Notification::make()
                    ->title('Payroll Generated Successfully')
                    ->body("Generated payroll for {$results['total_processed']} employees. Success: " . count($results['success']) . ", Failed: " . count($results['failed']))
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Payroll Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Auto calculate payroll from attendance data
     */
    public static function autoCalculatePayroll(array $data): void
    {
        try {
            $calculator = app(\App\Services\PayrollCalculatorService::class);
            $employee = \App\Models\Employee::find($data['employee_id']);

            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $payrollData = $calculator->calculatePayrollData(
                $employee,
                $data['period_start_date'],
                $data['period_end_date']
            );

            if ($data['create_payroll'] ?? false) {
                // Create the payroll record
                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'period_start_date' => $data['period_start_date'],
                    'period_end_date' => $data['period_end_date'],
                    'work_days' => $payrollData['work_days'],
                    'leave_days' => $payrollData['leave_days'],
                    'permission_days' => $payrollData['permission_days'],
                    'absent_days' => $payrollData['absent_days'],
                    'overtime_hours' => $payrollData['overtime_hours'],
                    'employee_income' => $payrollData['employee_income'],
                    'employee_expense' => $payrollData['employee_expense'],
                    'final_salary' => $payrollData['final_salary'],
                    'generated_by' => auth()->id(),
                    'generated_at' => now(),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Payroll Created Successfully')
                    ->body("Auto-calculated and created payroll for {$employee->name}. Final salary: Rp " . number_format($payroll->final_salary, 0, ',', '.'))
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Payroll Calculated Successfully')
                    ->body("Auto-calculated payroll for {$employee->name}. Final salary: Rp " . number_format($payrollData['final_salary'], 0, ',', '.'))
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Auto Calculation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Preview calculation for live update
     */
    public static function previewCalculation(int $employeeId, string $startDate, string $endDate, callable $set): void
    {
        try {
            $calculator = app(\App\Services\PayrollCalculatorService::class);
            $employee = Employee::find($employeeId);

            if (!$employee) {
                return;
            }

            $payrollData = $calculator->calculatePayrollData($employee, $startDate, $endDate);

            $set('preview_work_days', $payrollData['work_days'] . ' days');
            $set('preview_overtime_hours', $payrollData['overtime_hours'] . ' hours');
            $set('preview_final_salary', $payrollData['final_salary']);
        } catch (\Exception $e) {
            $set('preview_work_days', 'Error calculating');
            $set('preview_overtime_hours', 'Error calculating');
            $set('preview_final_salary', 0);
        }
    }

    /**
     * Recalculate selected payrolls
     */
    public static function recalculateSelectedPayrolls(Collection $records): void
    {
        try {
            $calculator = app(\App\Services\PayrollCalculatorService::class);
            $successCount = 0;
            $failedCount = 0;

            foreach ($records as $payroll) {
                try {
                    $payrollData = $calculator->calculatePayrollData(
                        $payroll->employee,
                        $payroll->period_start_date,
                        $payroll->period_end_date
                    );

                    $payroll->update([
                        'work_days' => $payrollData['work_days'],
                        'leave_days' => $payrollData['leave_days'],
                        'permission_days' => $payrollData['permission_days'],
                        'absent_days' => $payrollData['absent_days'],
                        'overtime_hours' => $payrollData['overtime_hours'],
                        'employee_income' => $payrollData['employee_income'],
                        'employee_expense' => $payrollData['employee_expense'],
                        'final_salary' => $payrollData['final_salary'],
                        'generated_by' => auth()->id(),
                        'generated_at' => now(),
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            \Filament\Notifications\Notification::make()
                ->title('Payroll Recalculation Completed')
                ->body("Recalculated {$successCount} payrolls successfully. Failed: {$failedCount}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Recalculation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Generate bulk payroll based on criteria
     */
    public static function generateBulkPayroll(array $data): void
    {
        try {
            $employees = collect();

            switch ($data['generation_mode']) {
                case 'department':
                    $employees = Employee::where('department_id', $data['department_id'])
                        ->where('status', 'active')
                        ->get();
                    break;
                case 'position':
                    $employees = Employee::where('position_id', $data['position_id'])
                        ->where('status', 'active')
                        ->get();
                    break;
                case 'all_active':
                    $employees = Employee::where('status', 'active')->get();
                    break;
            }

            if ($employees->isEmpty()) {
                throw new \Exception('No employees found for the selected criteria');
            }

            if ($data['run_in_background']) {
                GenerateBulkPayrollJob::dispatch(
                    $employees->pluck('id')->toArray(),
                    $data['period_start_date'],
                    $data['period_end_date'],
                    auth()->id()
                );

                \Filament\Notifications\Notification::make()
                    ->title('Bulk Payroll Generation Started')
                    ->body("Started generating payroll for {$employees->count()} employees in background")
                    ->success()
                    ->send();
            } else {
                $generator = app(PayrollGenerationService::class);
                $results = $generator->generateBulkPayroll(
                    $employees->pluck('id')->toArray(),
                    $data['period_start_date'],
                    $data['period_end_date'],
                    auth()->id()
                );

                \Filament\Notifications\Notification::make()
                    ->title('Bulk Payroll Generation Completed')
                    ->body("Generated payroll for {$results['total_processed']} employees. Success: " . count($results['success']) . ", Failed: " . count($results['failed']))
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Bulk Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Auto-fill form from attendance data
     */
    public static function autoFillFromAttendance(int $employeeId, string $startDate, string $endDate, callable $set, callable $get): void
    {
        try {
            $calculator = app(PayrollCalculatorService::class);
            $employee = Employee::find($employeeId);

            if (!$employee) {
                throw new \Exception('Employee not found');
            }

            $payrollData = $calculator->calculatePayrollData($employee, $startDate, $endDate);
            // Fill attendance data
            $set('work_days', $payrollData['work_days']);
            $set('leave_days', $payrollData['leave_days']);
            $set('permission_days', $payrollData['permission_days']);
            $set('absent_days', $payrollData['absent_days']);
            $set('overtime_hours', $payrollData['overtime_hours']);

            // Convert calculated income to repeater format
            $incomeRepeaterData = [];
            if (isset($payrollData['employee_income']) && is_array($payrollData['employee_income'])) {
                foreach ($payrollData['employee_income'] as $key => $value) {
                    if ($key !== 'total_penghasilan') {
                        $incomeRepeaterData[] = [
                            'name' => ucwords(str_replace('_', ' ', $key)),
                            'nominal' => (int) $value
                        ];
                    }
                }
            }

            // Convert calculated expense to repeater format
            $expenseRepeaterData = [];
            if (isset($payrollData['employee_expense']) && is_array($payrollData['employee_expense'])) {
                foreach ($payrollData['employee_expense'] as $key => $value) {
                    if ($key !== 'total_potongan') {
                        $expenseRepeaterData[] = [
                            'name' => ucwords(str_replace('_', ' ', $key)),
                            'nominal' => (int) $value
                        ];
                    }
                }
            }

            // Set income and expense data
            $set('employee_income_view', $incomeRepeaterData);
            $set('employee_expense_view', $expenseRepeaterData);

            // Fill final salary
            $set('final_salary', (int) $payrollData['final_salary']);

            // No success notification here since it will be handled by the action
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('❌ Auto-Fill Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Calculate totals and update final salary
     */
    public static function calculateTotals(callable $set, callable $get): void
    {
        $incomes = collect($get('employee_income_view'))->sum(function ($item) {
            return (int) preg_replace('/[^0-9]/', '', $item['nominal']);
        });
        $expenses = collect($get('employee_expense_view'))->sum(function ($item) {
            return (int) preg_replace('/[^0-9]/', '', $item['nominal']);
        });
        $finalSalary = $incomes - $expenses;

        $set('final_salary', $finalSalary);
    }

    /**
     * Load default income and expense data from employee
     */
    public static function loadEmployeeDefaults(int $employeeId, callable $set, bool $preserveExisting = false): void
    {
        try {
            $employee = Employee::find($employeeId);

            if (!$employee) {
                return;
            }

            // Only load defaults if fields are empty or not preserving existing
            if (!$preserveExisting) {
                // Convert employee income to repeater format
                $incomeRepeaterData = [];
                if ($employee->employee_income) {
                    foreach ($employee->employee_income as $key => $value) {
                        if ($key !== 'total_penghasilan' && is_numeric($value) && $value > 0) {
                            $incomeRepeaterData[] = [
                                'name' => ucwords(str_replace('_', ' ', $key)),
                                'nominal' => $value
                            ];
                        }
                    }
                }

                // Convert employee expense to repeater format
                $expenseRepeaterData = [];
                if ($employee->employee_expense_view) {
                    foreach ($employee->employee_expense_view as $key => $value) {
                        if ($key !== 'total_potongan' && is_numeric($value) && $value > 0) {
                            $expenseRepeaterData[] = [
                                'name' => ucwords(str_replace('_', ' ', $key)),
                                'nominal' => $value
                            ];
                        }
                    }
                }

                // Set default values if available
                if (!empty($incomeRepeaterData)) {
                    $set('employee_income_view', $incomeRepeaterData);
                }

                if (!empty($expenseRepeaterData)) {
                    $set('employee_expense_view', $expenseRepeaterData);
                }

                // Calculate initial totals
                static::calculateTotals($set, function ($key) use ($incomeRepeaterData, $expenseRepeaterData) {
                    if ($key === 'employee_income_view') return $incomeRepeaterData;
                    if ($key === 'employee_expense_view') return $expenseRepeaterData;
                    return null;
                });
            }
        } catch (\Exception $e) {
            // Silently fail - default loading is not critical
        }
    }

    /**
     * Export single payroll to PDF
     */
    public static function exportPayrollPdf($record)
    {
        try {
            // Load relationships needed for PDF
            $payroll = $record->load(['employee.position.department', 'generatedBy']);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.payroll-slip', [
                'payroll' => $payroll,
            ])->setPaper('a4', 'portrait');

            // Generate filename
            $employeeName = str_replace(' ', '_', $payroll->employee->name);
            $period = $payroll->period_start_date->format('Y-m');
            $fileName = "Slip_Gaji_{$employeeName}_{$period}.pdf";

            // Download PDF
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, $fileName);
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Export Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Export multiple payrolls to ZIP file containing PDFs
     */
    public static function exportBulkPayrollPdf(Collection $records)
    {
        try {
            if ($records->isEmpty()) {
                throw new \Exception('No records selected for export');
            }

            // Create temporary ZIP file
            $zipFileName = 'Slip_Gaji_Bulk_' . now()->format('Y-m-d_H-i-s') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);

            // Ensure temp directory exists
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create ZIP file');
            }

            $successCount = 0;
            $failedCount = 0;

            foreach ($records as $payroll) {
                try {
                    // Load relationships
                    $payroll->load(['employee.position.department', 'generatedBy']);

                    // Generate PDF
                    $pdf = Pdf::loadView('pdf.payroll-slip', [
                        'payroll' => $payroll,
                    ])->setPaper('a4', 'portrait');

                    // Generate filename for this PDF
                    $employeeName = str_replace(' ', '_', $payroll->employee->name);
                    $period = $payroll->period_start_date->format('Y-m');
                    $pdfFileName = "Slip_Gaji_{$employeeName}_{$period}.pdf";

                    // Add PDF to ZIP
                    $zip->addFromString($pdfFileName, $pdf->output());
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    // Continue with other records
                }
            }

            $zip->close();

            if ($successCount === 0) {
                throw new \Exception('Failed to generate any PDF files');
            }

            // Show success notification
            Notification::make()
                ->title('✅ Bulk Export Completed')
                ->body("Successfully exported {$successCount} slip gaji. Failed: {$failedCount}")
                ->success()
                ->send();

            // Download ZIP file
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Bulk Export Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Export all visible payroll records to ZIP
     */
    public static function exportAllPayrollPdfs()
    {
        try {
            // Get all visible payroll records based on current filters
            $query = static::getEloquentQuery();

            // Apply current table filters if any
            $records = $query->with(['employee.position.department', 'generatedBy'])->get();

            if ($records->isEmpty()) {
                throw new \Exception('No payroll records found to export');
            }

            return static::exportBulkPayrollPdf($records);
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Export All Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
