<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\CareerHistory;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Infolist;
use Illuminate\Support\Str;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected static string $view = 'filament.resources.employee-resource.pages.view-employee-tabs';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $curr_record = Employee::find($this->record->id);

        $data['npwp'] = $curr_record->npwp;
        $data['bpjs_number'] = $curr_record->bpjs_number;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('promotion')
                    ->color('success')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->form([
                        Forms\Components\Select::make('to_position_id')
                            ->label('New Position')
                            ->relationship('position', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('employment_type')
                            ->label('Employment Status')
                            ->options([
                                'permanent' => 'Permanent',
                                'contract' => 'Contract',
                                'probation' => 'Probation',
                                'internship' => 'Internship',
                            ])
                            ->required()
                            ->default(fn($record) => $record->employment_status),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Supporting Document')
                            ->directory('career-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Create career history record
                            CareerHistory::create([
                                'employee_id' => $record->id,
                                'type' => 'promotion',
                                'from_position_id' => $record->position_id,
                                'to_position_id' => $data['to_position_id'],
                                'effective_date' => $data['effective_date'],
                                'reason' => $data['reason'] ?? null,
                                'document_path' => $data['document_path'] ?? null,
                                'created_by' => auth()->id(),
                            ]);

                            // Update employee record - only position changes
                            $record->update([
                                'position_id' => $data['to_position_id'],
                                'updated_by' => auth()->id(),
                            ]);

                            // Always create a new contract to reflect the promotion
                            $record->contracts()->create([
                                'contract_number' => 'PROM-' . date('Ymd') . '-' . $record->id,
                                'type' => $data['employment_type'],
                                'position_id' => $data['to_position_id'],
                                'start_date' => $data['effective_date'],
                                'end_date' => $data['employment_type'] === 'permanent' ? null : now()->addYear(),
                                'status' => 'active',
                                'notes' => 'Promotion: ' . ($data['reason'] ?? ''),
                                'created_by' => auth()->id(),
                            ]);
                        });

                        Notification::make()
                            ->title('Employee promoted successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('transfer')
                    ->color('primary')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        Forms\Components\Select::make('to_position_id')
                            ->label('New Position')
                            ->relationship('position', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('employment_type')
                            ->label('Employment Status')
                            ->options([
                                'permanent' => 'Permanent',
                                'contract' => 'Contract',
                                'probation' => 'Probation',
                                'internship' => 'Internship',
                            ])
                            ->required()
                            ->default(fn($record) => $record->employment_status),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Supporting Document')
                            ->directory('career-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Create career history record
                            CareerHistory::create([
                                'employee_id' => $record->id,
                                'type' => 'transfer',
                                'from_position_id' => $record->position_id,
                                'to_position_id' => $data['to_position_id'],
                                'effective_date' => $data['effective_date'],
                                'reason' => $data['reason'] ?? null,
                                'document_path' => $data['document_path'] ?? null,
                                'created_by' => auth()->id(),
                            ]);

                            // Update employee record - only position changes
                            $record->update([
                                'position_id' => $data['to_position_id'],
                                'updated_by' => auth()->id(),
                            ]);

                            // Always create a new contract to reflect the transfer
                            $record->contracts()->create([
                                'contract_number' => 'TRANS-' . date('Ymd') . '-' . $record->id,
                                'type' => $data['employment_type'],
                                'position_id' => $data['to_position_id'],
                                'start_date' => $data['effective_date'],
                                'end_date' => $data['employment_type'] === 'permanent' ? null : now()->addYear(),
                                'status' => 'active',
                                'notes' => 'Transfer: ' . ($data['reason'] ?? ''),
                                'created_by' => auth()->id(),
                            ]);
                        });

                        Notification::make()
                            ->title('Employee transferred successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('demotion')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->form([
                        Forms\Components\Select::make('to_position_id')
                            ->label('New Position')
                            ->relationship('position', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('employment_type')
                            ->label('Employment Status')
                            ->options([
                                'permanent' => 'Permanent',
                                'contract' => 'Contract',
                                'probation' => 'Probation',
                                'internship' => 'Internship',
                            ])
                            ->required()
                            ->default(fn($record) => $record->employment_status),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Supporting Document')
                            ->directory('career-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Create career history record
                            CareerHistory::create([
                                'employee_id' => $record->id,
                                'type' => 'demotion',
                                'from_position_id' => $record->position_id,
                                'to_position_id' => $data['to_position_id'],
                                'effective_date' => $data['effective_date'],
                                'reason' => $data['reason'] ?? null,
                                'document_path' => $data['document_path'] ?? null,
                                'created_by' => auth()->id(),
                            ]);

                            // Update employee record - only position changes
                            $record->update([
                                'position_id' => $data['to_position_id'],
                                'updated_by' => auth()->id(),
                            ]);

                            // Always create a new contract to reflect the demotion
                            $record->contracts()->create([
                                'contract_number' => 'DEM-' . date('Ymd') . '-' . $record->id,
                                'type' => $data['employment_type'],
                                'position_id' => $data['to_position_id'],
                                'start_date' => $data['effective_date'],
                                'end_date' => $data['employment_type'] === 'permanent' ? null : now()->addYear(),
                                'status' => 'active',
                                'notes' => 'Demotion: ' . ($data['reason'] ?? ''),
                                'created_by' => auth()->id(),
                            ]);
                        });

                        Notification::make()
                            ->title('Employee demoted successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('resignation')
                    ->color('gray')
                    ->icon('heroicon-o-document-minus')
                    ->requiresConfirmation()
                    ->modalHeading('Employee Resignation')
                    ->modalDescription('Record an employee resignation. This will update their status and create a career history record.')
                    ->form([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Last Working Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('notice_period')
                            ->label('Notice Period Given')
                            ->options([
                                '0' => 'No notice given',
                                '1' => '1 week',
                                '2' => '2 weeks',
                                '4' => '1 month',
                                '8' => '2 months',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Select::make('resignation_reason')
                            ->label('Primary Reason')
                            ->options([
                                'better_opportunity' => 'Better opportunity elsewhere',
                                'compensation' => 'Compensation/benefits issues',
                                'working_conditions' => 'Working conditions',
                                'relocation' => 'Relocation',
                                'health' => 'Health reasons',
                                'career_change' => 'Career change',
                                'conflict' => 'Workplace conflict',
                                'lack_of_growth' => 'Lack of growth opportunities',
                                'retirement' => 'Retirement',
                                'personal' => 'Personal reasons',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Detailed Reason')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('eligible_for_rehire')
                            ->label('Eligible for Rehire?')
                            ->default(true)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Resignation Letter')
                            ->directory('resignation-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Create career history record
                            CareerHistory::create([
                                'employee_id' => $record->id,
                                'type' => 'resignation',
                                'from_position_id' => $record->position_id,
                                'to_position_id' => $record->position_id, // Same position as they're leaving
                                'effective_date' => $data['effective_date'],
                                'reason' => $data['reason'] .
                                    "\nPrimary reason: " . $data['resignation_reason'] .
                                    "\nNotice period: " . $data['notice_period'] .
                                    "\nEligible for rehire: " . ($data['eligible_for_rehire'] ? 'Yes' : 'No'),
                                'document_path' => $data['document_path'] ?? null,
                                'created_by' => auth()->id(),
                            ]);

                            // Update employee record
                            $record->update([
                                'status' => 'terminated', // Using terminated status for both resignation and termination
                                'updated_by' => auth()->id(),
                            ]);

                            // End any active contracts
                            $activeContracts = $record->contracts()->where('status', 'active')->get();
                            foreach ($activeContracts as $contract) {
                                $contract->update([
                                    'status' => 'terminated',
                                    'end_date' => $data['effective_date'],
                                    'notes' => $contract->notes . "\n\nTerminated due to resignation. Reason: " . $data['reason'],
                                    'updated_by' => auth()->id(),
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Employee resignation recorded successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('termination')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->modalHeading('Employee Termination')
                    ->modalDescription('Record an employee termination. This is for cases where the employee is being fired or laid off.')
                    ->form([
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Termination Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('termination_type')
                            ->label('Termination Type')
                            ->options([
                                'performance' => 'Poor performance',
                                'misconduct' => 'Misconduct/policy violation',
                                'layoff' => 'Layoff/redundancy',
                                'probation_failure' => 'Failed probation',
                                'contract_end' => 'End of contract',
                                'restructuring' => 'Company restructuring',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('with_cause')
                            ->label('Termination With Cause?')
                            ->helperText('Termination with cause may affect severance and benefits')
                            ->default(false),

                        Forms\Components\Textarea::make('reason')
                            ->label('Detailed Reason')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('severance_package')
                            ->label('Severance Package')
                            ->placeholder('e.g., 2 months salary'),

                        Forms\Components\Toggle::make('eligible_for_rehire')
                            ->label('Eligible for Rehire?')
                            ->default(false)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document_path')
                            ->label('Termination Document')
                            ->directory('termination-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('additional_documents')
                            ->label('Additional Documents')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Document Title')
                                    ->required(),
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Document File')
                                    ->directory('termination-documents')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->required(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, $record): void {
                        DB::transaction(function () use ($data, $record) {
                            // Handle additional documents by storing their metadata in JSON
                            $additionalDocsDescription = '';
                            if (!empty($data['additional_documents'])) {
                                foreach ($data['additional_documents'] as $index => $document) {
                                    $additionalDocsDescription .= "\nAdditional document " . ($index + 1) . ": " .
                                        $document['title'] . " (File: " . $document['file_path'] . ")";
                                }
                            }

                            // Create career history record
                            CareerHistory::create([
                                'employee_id' => $record->id,
                                'type' => 'termination',
                                'from_position_id' => $record->position_id,
                                'to_position_id' => $record->position_id, // Same position as they're leaving
                                'effective_date' => $data['effective_date'],
                                'reason' => $data['reason'] .
                                    "\nTermination type: " . $data['termination_type'] .
                                    "\nWith cause: " . ($data['with_cause'] ? 'Yes' : 'No') .
                                    "\nSeverance: " . ($data['severance_package'] ?? 'None') .
                                    "\nEligible for rehire: " . ($data['eligible_for_rehire'] ? 'Yes' : 'No') .
                                    $additionalDocsDescription,
                                'document_path' => $data['document_path'] ?? null,
                                'created_by' => auth()->id(),
                            ]);

                            // Update employee record
                            $record->update([
                                'status' => 'terminated',
                                'updated_by' => auth()->id(),
                            ]);

                            // End any active contracts
                            $activeContracts = $record->contracts()->where('status', 'active')->get();
                            foreach ($activeContracts as $contract) {
                                $contract->update([
                                    'status' => 'terminated',
                                    'end_date' => $data['effective_date'],
                                    'notes' => $contract->notes . "\n\nTerminated. Type: " . $data['termination_type'] . ". Reason: " . $data['reason'],
                                    'updated_by' => auth()->id(),
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Employee termination recorded successfully')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Career Actions')
                ->icon('heroicon-m-user-circle')
                ->color('primary')
                ->button(),

            Actions\EditAction::make(),
        ];
    }

    public function getTabsConfiguration(): array
    {
        return ResourceTabsConfiguration::forEmployee()->toArray();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)->schema([
                            // Group for main personal details, spanning 2/3 of the width
                            Group::make()
                                ->schema([
                                    TextEntry::make('nik')
                                        ->label('NIK'),
                                    TextEntry::make('name')
                                        ->label('Full Name'),
                                    TextEntry::make('email')
                                        ->label('Email Address')
                                        ->icon('heroicon-m-envelope'),
                                    TextEntry::make('phone')
                                        ->label('Phone Number')
                                        ->icon('heroicon-m-phone'),
                                    TextEntry::make('birth_place')
                                        ->label('Birth Place'),
                                    TextEntry::make('birth_date')
                                        ->label('Birth Date')
                                        ->date(),
                                    TextEntry::make('gender')
                                        ->formatStateUsing(fn($state) => ucfirst($state)),
                                    TextEntry::make('marital_status')
                                        ->label('Marital Status')
                                        ->formatStateUsing(fn($state) => ucfirst($state)),
                                    TextEntry::make('religion')
                                        ->formatStateUsing(fn($state) => match ($state) {
                                            'islam' => 'Islam',
                                            'kristen_protestant' => 'Kristen Protestant',
                                            'kristen_katolik' => 'Kristen Katolik',
                                            'hindu' => 'Hindu',
                                            'buddha' => 'Buddha',
                                            'konghucu' => 'Konghucu',
                                            'lainnya' => 'Lainnya',
                                            default => ucfirst($state),
                                        }),
                                ])->columns(2),

                            // Group for photo and notes, spanning 1/3 of the width
                            Group::make()
                                ->schema([
                                    ImageEntry::make('photo')
                                        ->label('Photo')
                                        ->circular(),
                                    TextEntry::make('notes')
                                        ->label('Personal Notes')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    ]),

                Section::make('Employment Information')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('position.title')
                                ->label('Position')
                                ->icon('heroicon-m-identification'),
                            TextEntry::make('join_date')
                                ->label('Join Date')
                                ->date(),
                            TextEntry::make('status')
                                ->label('Employee Status')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'active' => 'success',
                                    'on_leave' => 'warning',
                                    'inactive', 'terminated' => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                                ->icon('heroicon-m-user-circle'),
                            TextEntry::make('status_flag')
                                ->label('Performance Status')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'good_standing' => 'success',
                                    'under_review' => 'warning',
                                    'at_risk' => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                                ->icon('heroicon-m-arrow-trending-up'),
                            TextEntry::make('employment_status')
                                ->label('Employment Status')
                                ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                                ->helperText('Auto-calculated from latest contract'),
                            TextEntry::make('shift_override')
                                ->label('Shift Override')
                                ->getStateUsing(fn(Employee $record) => $record->shift?->name)
                                ->placeholder('Using position/department default')
                                ->icon('heroicon-m-clock'),
                        ]),
                    ]),

                Section::make('Government & Financial')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Government Information')
                                ->icon('heroicon-o-building-library')
                                ->schema([
                                    // The main NIK is in the Personal Information section.
                                    TextEntry::make('npwp')
                                        ->label('NPWP (Tax ID)')
                                        ->icon('heroicon-m-document-check'),
                                    TextEntry::make('bpjs_number')
                                        ->label('BPJS Number')
                                        ->icon('heroicon-m-shield-check'),
                                ]),
                            Section::make('Bank Information')
                                ->icon('heroicon-o-banknotes')
                                ->schema([
                                    TextEntry::make('bank_name')
                                        ->label('Bank Name')
                                        ->icon('heroicon-m-building-office'),
                                    TextEntry::make('bank_account')
                                        ->label('Bank Account Number')
                                        ->icon('heroicon-m-credit-card'),
                                    TextEntry::make('bank_account_name')
                                        ->label('Bank Account Holder')
                                        ->icon('heroicon-m-user'),
                                ]),
                        ]),
                    ]),
            ]);
    }
}
