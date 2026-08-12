<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\TableRepeaterCustom;
use App\Filament\Resources\ProposalResource\Pages;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Item;
use App\Models\Tax;
use App\Models\ProposalTemplate;
use App\Models\Lead;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\RawJs;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    private static function calculateServiceAmount($get, $set, $qtyState = null): void
    {
        $rate = (float) str_replace(['.', ','], '', $get('rate') ?? '0');
        $qty = (float) ($qtyState ?? $get('qty') ?? 1);
        $taxes = $get('taxes') ?: [];

        $subtotal = $rate * $qty;
        $taxAmount = 0;

        // Calculate tax properly if taxes are selected
        if (!empty($taxes)) {
            foreach ($taxes as $taxId) {
                $tax = \App\Models\Tax::find($taxId);
                if ($tax) {
                    $taxAmount += $subtotal * ($tax->value / 100);
                }
            }
        }

        $set('amount', $subtotal + $taxAmount);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Proposal Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('subject')->columnSpan(2),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match (strtolower($state)) {
                                'draft' => 'gray',
                                'send' => 'info',
                                'open' => 'primary',
                                'revised' => 'warning',
                                'declined' => 'danger',
                                'accepted' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state) => Str::title($state)),
                        TextEntry::make('related')
                            ->formatStateUsing(fn(string $state) => Str::title($state)),
                        TextEntry::make('lead.name')->label('Lead Name')
                            ->visible(fn(Proposal $record) => $record->related === 'lead'),
                        TextEntry::make('customer.name')->label('Customer Name')
                            ->visible(fn(Proposal $record) => $record->related === 'customer'),
                        TextEntry::make('to')->label('To (Contact Person/Company)'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                        TextEntry::make('proposalTemplate.name')->label('Template'),
                    ]),
                InfolistSection::make('Address Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('address')->columnSpanFull(),
                        TextEntry::make('city'),
                        TextEntry::make('state'),
                        TextEntry::make('zip_code'),
                        TextEntry::make('country.name')->label('Country'),
                    ])->collapsible(),
                InfolistSection::make('Dates & Terms')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('date')->date(),
                        TextEntry::make('open_till')->date()->label('Valid Until'),
                        TextEntry::make('paymentTerm.name')->label('Payment Term'),
                        TextEntry::make('contract_start_date')->date(),
                        TextEntry::make('contract_end_date')->date(),
                        TextEntry::make('discount_type')
                            ->formatStateUsing(fn(string $state) => Str::title(str_replace('_', ' ', $state))),
                        TextEntry::make('warranty_term'),
                        TextEntry::make('warranty_type')
                            ->formatStateUsing(fn(string $state) => Str::title($state)),
                        IconEntry::make('allow_comments')->boolean(),
                    ])->collapsible(),

                InfolistSection::make('Items & Services')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        RepeatableEntry::make('proposalOrder')
                            ->schema([
                                RepeatableEntry::make('proposalItems')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Item')
                                            ->columnSpan(2),
                                        TextEntry::make('qty')
                                            ->label('Qty'),
                                        TextEntry::make('rate')
                                            ->label('Rate')
                                            ->money('IDR'),
                                        TextEntry::make('taxes.name')
                                            ->label('Taxes')
                                            ->badge(),
                                        TextEntry::make('amount')
                                            ->label('Total')
                                            ->money('IDR'),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->hidden(fn($state) => blank($state)),
                                    ]),
                                RepeatableEntry::make('proposalServices')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Service')
                                            ->columnSpan(2),
                                        TextEntry::make('qty')
                                            ->label('Qty'),
                                        TextEntry::make('rate')
                                            ->label('Rate')
                                            ->money('IDR'),
                                        TextEntry::make('amount')
                                            ->label('Total')
                                            ->money('IDR'),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->hidden(fn($state) => blank($state)),
                                    ]),
                                TextEntry::make('subtotal')
                                    ->money('IDR'),
                                TextEntry::make('discount_fixed')
                                    ->money('IDR')
                                    ->placeholder('-'),
                                TextEntry::make('discount_percent')
                                    ->suffix('%')
                                    ->placeholder('-'),
                                TextEntry::make('adjusment')
                                    ->money('IDR')
                                    ->placeholder('-'),
                                TextEntry::make('total')
                                    ->money('IDR'),
                            ])
                    ]),

                InfolistSection::make('Additional Information')
                    ->schema([
                        TextEntry::make('tags.name')->label('Tags')->badge()->separator(','),
                        TextEntry::make('email_text')->label('Email Content Preview')->html()->columnSpanFull()
                            ->visible(fn($state) => !empty($state)),
                        TextEntry::make('createdBy.name')->label('Created By'),
                        TextEntry::make('assigned.name')->label('Assigned To'),
                        TextEntry::make('created_at')->dateTime()->since(),
                        TextEntry::make('updated_at')->dateTime()->since(),
                    ])->columns(2)->collapsible(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Proposal Details')
                            ->icon('heroicon-o-document-text')
                            ->compact()
                            ->description('Basic proposal information and assignment')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('created_by')
                                            ->label('Created By')
                                            ->relationship('createdBy', 'name')
                                            ->default(auth()->user()->id)
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\Select::make('status')
                                            ->formatStateUsing(fn($state) => $state ?? 'send')
                                            ->label('Status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'send' => 'Send',
                                                'open' => 'Open',
                                                'revised' => 'Revised',
                                                'declined' => 'Declined',
                                                'accepted' => 'Accepted',
                                            ])
                                            ->default('draft')
                                            ->required()
                                            ->native(false),
                                        Forms\Components\Select::make('assigned_id')
                                            ->formatStateUsing(fn($state) => $state ?? auth()->user()->id)
                                            ->label('Assigned To')
                                            ->relationship('assigned', 'name')
                                            ->default(auth()->user()->id)
                                            ->searchable()
                                            ->preload(),
                                    ]),
                                Forms\Components\TextInput::make('subject')
                                    ->label('Proposal Subject')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter proposal subject')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Client Information')
                            ->icon('heroicon-o-user')
                            ->compact()
                            ->description('Select whether this proposal is for a lead or existing customer')
                            ->schema([
                                Forms\Components\Select::make('related')
                                    ->label('Client Type')
                                    ->live()
                                    ->options([
                                        'lead' => 'Lead (Potential Customer)',
                                        'customer' => 'Existing Customer',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('lead')
                                    ->afterStateUpdated(fn($state, $set) => $state === 'lead' ? $set('customer_id', null) : $set('lead_id', null)),

                                Forms\Components\Select::make('lead_id')
                                    ->label('Select Lead')
                                    ->visible(fn($get) => $get('related') === 'lead')
                                    ->relationship('lead', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(Lead $record) => "{$record->name} - {$record->company}")
                                    ->searchable(['name', 'company'])
                                    ->preload()
                                    ->required(fn($get) => $get('related') === 'lead')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Select Customer')
                                    ->visible(fn($get) => $get('related') === 'customer')
                                    ->relationship('customer', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->name} - {$record->company}")
                                    ->searchable(['name', 'company'])
                                    ->preload()
                                    ->required(fn($get) => $get('related') === 'customer')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Contact Information')
                            ->icon('heroicon-o-phone')
                            ->compact()
                            ->description('Client contact details and address')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('to')
                                            ->label('Contact Person')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Contact person name'),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('contact@example.com'),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('+62 xxx xxxx xxxx'),
                                    ]),
                                Forms\Components\Textarea::make('address')
                                    ->label('Address')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->placeholder('Enter full address'),
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('city')
                                            ->label('City')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->label('State/Province')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('zip_code')
                                            ->label('ZIP/Postal Code')
                                            ->maxLength(255),
                                        Forms\Components\Select::make('country_id')
                                            ->label('Country')
                                            ->relationship('country', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Template & Terms')
                            ->icon('heroicon-o-document-duplicate')
                            ->compact()
                            ->description('Select proposal template and configure terms')
                            ->schema([
                                Forms\Components\Hidden::make('template_selected')
                                    ->default(false),
                                Forms\Components\Select::make('template_id')
                                    ->label('Proposal Template')
                                    ->required()
                                    ->relationship('proposalTemplate', 'name')
                                    ->live(debounce: '1s')
                                    ->extraAttributes(fn($get) => $get('template_selected') ? [] : ['style' => 'background-color: #fee2e2;'])
                                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                        // remove extra attributes to reset background color
                                        $set('template_selected', $state ? true : false);
                                    })
                                    ->default(fn() => ProposalTemplate::query()->value('id'))
                                    // ->selectablePlaceholder(false)
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('payment_term')
                                            ->label('Payment Terms')
                                            ->relationship('paymentTerm', 'name')
                                            ->selectablePlaceholder(false),
                                        Forms\Components\DatePicker::make('date')
                                            ->label('Proposal Date')
                                            ->required()
                                            ->formatStateUsing(fn() => now()->format('Y-m-d')),
                                        Forms\Components\DatePicker::make('open_till')
                                            ->label('Valid Until')
                                            ->required()
                                            ->formatStateUsing(fn() => now()->addDays(7)->format('Y-m-d'))
                                            ->after('date'),
                                    ]),

                                Forms\Components\Select::make('discount_type')
                                    ->label('Discount Calculation')
                                    ->formatStateUsing(fn($state) => $state ?? 'after_tax')
                                    ->options([
                                        'after_tax' => 'Apply Discount After Tax',
                                        'before_tax' => 'Apply Discount Before Tax',
                                    ])
                                    ->default('after_tax')
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),

                Forms\Components\Repeater::make('proposalOrder')
                    ->relationship()
                    // ->visible(fn($get) => $get('is_proposal_order'))
                    ->schema([
                        Forms\Components\Section::make('Items')
                            ->description('Add items for this proposal')
                            ->schema([
                                TableRepeaterCustom::make('proposalItems')
                                    ->relationship('proposalItems')
                                    ->schema([
                                        Forms\Components\Select::make('item_id')
                                            ->label('Select Items')
                                            ->relationship('item', 'name')
                                            ->preload()
                                            ->live()
                                            ->required()
                                            ->searchable()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (!$state) return;

                                                $item = Item::find($state);
                                                if (!$item) return;

                                                $qty = $item->min_inventory_qty ?? 1;
                                                $rate = $item->rate ?? 0;
                                                $taxRate = $item->tax ? $item->tax->value / 100 : 0;
                                                $subtotal = $rate * $qty;
                                                $taxAmount = $subtotal * $taxRate;
                                                $amount = $subtotal + $taxAmount;

                                                $set('name', $item->name);
                                                $set('description', $item->description);
                                                $set('qty', $qty);
                                                $set('rate', $rate);
                                                if ($item->tax_id) {
                                                    $set('taxes', [$item->tax_id]);
                                                } else {
                                                    $set('taxes', []);
                                                }
                                                $set('amount', $amount);
                                            }),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Item Name')
                                            ->maxLength(255)
                                            ->required(),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(1),

                                        Forms\Components\TextInput::make('qty')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $rate = (float) $get('rate') ?: 0;
                                                $qty = (float) $state ?: 1;
                                                $taxes = $get('taxes') ?: [];

                                                $subtotal = $rate * $qty;
                                                $taxAmount = 0;

                                                // Calculate tax if selected
                                                if (!empty($taxes)) {
                                                    foreach ($taxes as $taxId) {
                                                        $tax = Tax::find($taxId);
                                                        if ($tax) {
                                                            $taxAmount += $subtotal * ($tax->value / 100);
                                                        }
                                                    }
                                                }

                                                $set('amount', $subtotal + $taxAmount);
                                            }),
                                        Forms\Components\Select::make('unit')
                                            ->label('Unit')
                                            ->options(fn() => \App\Models\Unit::pluck('name', 'name'))
                                            ->preload()
                                            ->searchable()
                                            ->placeholder('Select unit'),

                                        Forms\Components\TextInput::make('rate')
                                            ->label('Unit Price')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $rate = (float) str_replace(['.', ','], '', $state) ?: 0;
                                                $qty = (float) $get('qty') ?: 1;
                                                $taxes = $get('taxes') ?: [];

                                                $subtotal = $rate * $qty;
                                                $taxAmount = 0;

                                                if (!empty($taxes)) {
                                                    foreach ($taxes as $taxId) {
                                                        $tax = Tax::find($taxId);
                                                        if ($tax) {
                                                            $taxAmount += $subtotal * ($tax->value / 100);
                                                        }
                                                    }
                                                }

                                                $set('amount', $subtotal + $taxAmount);
                                            }),


                                        Forms\Components\Select::make('taxes')
                                            ->label('Tax')
                                            ->relationship('taxes', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->multiple()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $rate = (float) str_replace(['.', ','], '', $get('rate')) ?: 0;

                                                $qty = (float) $get('qty') ?: 1;

                                                $subtotal = $rate * $qty;
                                                $taxAmount = 0;

                                                if (!empty($state)) {
                                                    foreach ($state as $taxId) {
                                                        $tax = Tax::find($taxId);
                                                        if ($tax) {
                                                            $taxAmount += $subtotal * ($tax->value / 100);
                                                        }
                                                    }
                                                }
                                                $set('amount', $subtotal + $taxAmount);
                                            }),

                                        Forms\Components\TextInput::make('amount')
                                            ->label('Total Inc. Tax')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated(),
                                    ])
                                    ->colStyles([
                                        'qty' => 'width: 5px;',
                                        'taxes' => 'width: 160px;',
                                        'rate' => 'width: 200px;',
                                        'amount' => 'width: 200px;',
                                    ])
                                    ->addActionLabel('Add Another Item')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Services')
                            ->description('Add services to this proposal')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                TableRepeaterCustom::make('proposalServices')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('service_id')
                                            ->label('Service')
                                            ->relationship('service', 'name')
                                            ->preload()
                                            ->live()
                                            ->searchable()
                                            ->placeholder('Search services...')
                                            ->suffixIcon('heroicon-m-magnifying-glass')
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (!$state) return;

                                                $service = Service::find($state);
                                                if (!$service) return;

                                                $qty =  1;
                                                $rate = $service->price ?? 0;

                                                $amount = $rate * $qty;

                                                $set('name', $service->name);
                                                $set('description', $service->description);
                                                $set('rate', $rate);
                                                $set('amount', $amount);
                                            }),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Service Name')
                                            ->maxLength(255)
                                            ->placeholder('Service name...'),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(1)
                                            ->placeholder('Optional item description...'),

                                        Forms\Components\TextInput::make('qty')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(9999)
                                            ->live(debounce: 500)
                                            ->rules(['min:1', 'max:9999'])
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateServiceAmount($get, $set, $state);
                                            }),
                                        Forms\Components\Select::make('unit')
                                            ->label('Unit')
                                            ->options(fn() => \App\Models\Unit::pluck('name', 'name'))
                                            ->preload()
                                            ->searchable()
                                            ->placeholder('Select unit'),

                                        Forms\Components\TextInput::make('rate')
                                            ->label('Service Price')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateServiceAmount($get, $set);
                                            }),

                                        Forms\Components\Select::make('taxes')
                                            ->label('Tax')
                                            ->relationship('taxes', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->multiple()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateServiceAmount($get, $set);
                                            }),

                                        Forms\Components\TextInput::make('amount')
                                            ->label('Total Inc. Tax')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->extraInputAttributes(['class' => 'bg-gray-50 font-semibold']),
                                    ])
                                    ->colStyles([
                                        'service_id' => 'width: 200px;',
                                        'qty' => 'width: 5px;',
                                        'rate' => 'width: 200px;',
                                        'taxes' => 'width: 160px;',
                                        'amount' => 'width: 200px;',
                                    ])
                                    ->addActionLabel('Add Another Service')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('empty_left')
                                    ->hiddenLabel()
                                    ->content('')
                                    ->columnSpan(1),

                                Forms\Components\Section::make('Pricing Summary')
                                    ->compact()
                                    ->description('Discounts, adjustments, and total calculations')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\Placeholder::make('sub_total')
                                                    ->label('Subtotal')
                                                    ->columnStart(3)
                                                    ->columnSpan(2)
                                                    ->extraAttributes(['class' => 'text-right'])
                                                    ->content(function ($get) {
                                                        $amount_item = collect($get('proposalItems'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount_service = collect($get('proposalServices'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount = $amount_item + $amount_service;
                                                        return "Rp. " . number_format($amount, 0, ',', '.');
                                                    }),

                                                Forms\Components\Hidden::make('subtotal')
                                                    ->dehydrateStateUsing(function ($get) {
                                                        $amount_item = collect($get('proposalItems'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount_service = collect($get('proposalServices'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount = $amount_item + $amount_service;
                                                        return "Rp. " . number_format($amount, 0, ',', '.');
                                                    }),

                                                Forms\Components\TextInput::make('discount_fixed')
                                                    ->label('Fixed Discount')
                                                    ->prefix('Rp.')
                                                    ->mask(RawJs::make('$money($input, \',\')'))
                                                    ->stripCharacters('.')
                                                    ->numeric()
                                                    ->columnSpan(2)
                                                    ->live()
                                                    ->disabled(fn($get) => !empty($get('discount_percent')))
                                                    ->extraAttributes(['class' => 'text-right']),

                                                Forms\Components\Placeholder::make('nom_discount_fixed')
                                                    ->hiddenLabel()
                                                    ->columnSpan(2)
                                                    ->extraAttributes(['class' => 'text-right'])
                                                    ->content(function ($get) {
                                                        $discount_fixed = (int) preg_replace('/[^0-9]/', '', $get('discount_fixed') ?? '0');
                                                        return $discount_fixed > 0 ? "- Rp. " . number_format($discount_fixed, 0, ',', '.') : "";
                                                    }),

                                                Forms\Components\TextInput::make('discount_percent')
                                                    ->label('Percentage Discount')
                                                    ->suffix('%')
                                                    ->inputMode('decimal')
                                                    ->columnSpan(2)
                                                    ->disabled(fn($get) => !empty($get('discount_fixed')))
                                                    ->live()
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->extraAttributes(['class' => 'text-right']),

                                                Forms\Components\Placeholder::make('nom_discount_percent')
                                                    ->hiddenLabel()
                                                    ->columnSpan(2)
                                                    ->extraAttributes(['class' => 'text-right'])
                                                    ->content(function ($get) {
                                                        $amount_item = collect($get('proposalItem'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount_service = collect($get('proposalServices'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });
                                                        $amount = $amount_item + $amount_service;
                                                        $discount_percent = (float) $get('discount_percent') ?: 0;
                                                        $total_discount = $amount * ($discount_percent / 100);
                                                        return $discount_percent > 0 ? "- Rp. " . number_format($total_discount, 0, ',', '.') : "";
                                                    }),

                                                Forms\Components\TextInput::make('adjustment')
                                                    ->label('Adjustment')
                                                    ->helperText('Additional charges or credits')
                                                    ->prefix('Rp.')
                                                    ->live()
                                                    ->stripCharacters('.')
                                                    ->mask(RawJs::make('$money($input, \',\')'))
                                                    ->columnSpan(2)
                                                    ->numeric()
                                                    ->extraAttributes(['class' => 'text-right']),

                                                Forms\Components\Placeholder::make('nom_adjustment')
                                                    ->hiddenLabel()
                                                    ->columnSpan(2)
                                                    ->extraAttributes(['class' => 'text-right'])
                                                    ->content(function ($get) {
                                                        $adjustment = (int) preg_replace('/[^0-9]/', '', $get('adjustment') ?? '0');
                                                        return $adjustment != 0 ? number_format($adjustment, 0, ',', '.') . " Rp." : "";
                                                    }),

                                                Forms\Components\Placeholder::make('total')
                                                    ->label('Final Total')
                                                    ->columnStart(3)
                                                    ->columnSpan(2)
                                                    ->extraAttributes(['class' => 'text-right font-bold text-lg'])
                                                    ->content(function ($get) {
                                                        $amount_item = collect($get('proposalItems'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount_service = collect($get('proposalServices'))->sum(function ($item) {
                                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                        });

                                                        $amount = $amount_item + $amount_service;

                                                        $discount_fixed = (int) preg_replace('/[^0-9]/', '', $get('discount_fixed') ?? '0');
                                                        $discount_percent = (float) $get('discount_percent') ?: 0;
                                                        $adjustment = (int) preg_replace('/[^0-9]/', '', $get('adjustment') ?? '0');

                                                        $total_discount_percent = $amount * ($discount_percent / 100);
                                                        $discount = $discount_fixed ?: $total_discount_percent;
                                                        $total = $amount - $discount + $adjustment;

                                                        return "Rp. " . number_format(max(0, $total), 0, ',', '.');
                                                    }),
                                                Forms\Components\Hidden::make('total')
                                                    ->dehydrateStateUsing(
                                                        function ($get) {
                                                            $amount_item = collect($get('proposalItems'))->sum(function ($item) {
                                                                return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                            });

                                                            $amount_service = collect($get('proposalServices'))->sum(function ($item) {
                                                                return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                            });

                                                            $amount = $amount_item + $amount_service;

                                                            $discount_fixed = (int) preg_replace('/[^0-9]/', '', $get('discount_fixed') ?? '0');
                                                            $discount_percent = (float) $get('discount_percent') ?: 0;
                                                            $adjustment = (int) preg_replace('/[^0-9]/', '', $get('adjustment') ?? '0');

                                                            $total_discount_percent = $amount * ($discount_percent / 100);
                                                            $discount = $discount_fixed ?: $total_discount_percent;
                                                            $total = $amount - $discount + $adjustment;

                                                            return $total;
                                                        }
                                                    )
                                            ]),
                                    ])
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Section::make('Service Details')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->collapsed(true)
                            ->collapsible()
                            ->description('Pest control targets and treatment information')
                            ->schema([
                                Forms\Components\Repeater::make('target_detail')
                                    ->schema([
                                        Forms\Components\TextInput::make('target')
                                            ->label('Target Pest')
                                            ->placeholder('e.g., Cockroaches, Ants')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('treatment_area')
                                            ->label('Treatment Area')
                                            ->placeholder('e.g., Kitchen, Office')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('method_use')
                                            ->label('Treatment Method')
                                            ->placeholder('e.g., Spray, Bait')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('unit_amount')
                                            ->label('Unit/Amount')
                                            ->placeholder('e.g., per room, per m²')
                                            ->maxLength(255),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->reorderable()
                                    ->collapsible()
                                    ->addActionLabel('Add Target')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull(),



                Forms\Components\Section::make('Contract Period')
                    ->icon('heroicon-o-calendar')
                    ->collapsed(true)
                    ->collapsible()
                    ->icon('heroicon-o-calendar')
                    ->description('Define contract duration and warranty terms')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('contract_start_date')
                                    ->label('Contract Start Date'),
                                Forms\Components\DatePicker::make('contract_end_date')
                                    ->label('Contract End Date'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('warranty_term')
                                    ->label('Warranty Period'),
                                Forms\Components\Select::make('warranty_type')
                                    ->label('Warranty Unit')
                                    ->options([
                                        'day' => 'Days',
                                        'month' => 'Months',
                                        'year' => 'Years',
                                    ]),
                            ]),
                    ])->columns(1),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Additional Information')
                            ->compact()
                            ->icon('heroicon-o-document-text')
                            ->collapsible()
                            ->collapsed(true)
                            ->description('Notes and terms for the client')
                            ->schema([
                                Forms\Components\RichEditor::make('client_note')
                                    ->label('Client Notes')
                                    ->helperText('Special notes or instructions for the client')
                                    ->columnSpanFull(),
                                Forms\Components\RichEditor::make('terms_condition')
                                    ->label('Terms & Conditions')
                                    ->helperText('Specific terms and conditions for this proposal')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),

                        Forms\Components\Section::make('Additional Settings')
                            ->compact()
                            ->icon('heroicon-o-cog')
                            ->collapsible()
                            ->collapsed(true)
                            ->description('Configure additional proposal options')
                            ->schema([
                                Forms\Components\Toggle::make('allow_comments')
                                    ->label('Allow Client Comments')
                                    ->helperText('Enable clients to add comments on the proposal')
                                    ->default(true),

                                Forms\Components\Select::make('tags')
                                    ->label('Tags')
                                    ->relationship('tags', 'name')
                                    ->createOptionForm([
                                        Forms\Components\Section::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Tag Name')
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                                    ->preload()
                                    ->searchable()
                                    ->multiple()
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('email_text')
                                    ->label('Email Message')
                                    ->helperText('Custom message to include when sending the proposal via email')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('createdBy.name'),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn(string $state) => Str::title($state))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'send' => 'info',
                        'open' => 'primary',
                        'revised' => 'warning',
                        'declined' => 'danger',
                        'accepted' => 'success',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('assigned.name'),
                Tables\Columns\TextColumn::make('related')
                    ->formatStateUsing(fn(string $state) => Str::title($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('lead.name'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country.name'),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('open_till')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract_start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract_end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warranty_term')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('allow_comments')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assigned')
                    ->relationship('assigned', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('lead')
                    ->relationship('lead', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('createdBy')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'send' => 'Send',
                        'open' => 'Open',
                        'revised' => 'Revised',
                        'declined' => 'Declined',
                        'accepted' => 'Accepted',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters()
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-document')
                    ->url(fn($record) => route('proposals.preview', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'create' => Pages\CreateProposal::route('/create'),
            'view' => Pages\ViewProposal::route('/{record}'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
