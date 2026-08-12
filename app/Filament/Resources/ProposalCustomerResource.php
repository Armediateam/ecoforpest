<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProposalCustomerResource\Pages;
use App\Filament\Resources\ProposalCustomerResource\RelationManagers;
use App\Models\ProposalCustomer;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\RawJs;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;

class ProposalCustomerResource extends Resource
{
    protected static ?string $model = ProposalCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                            ->visible(fn(ProposalCustomer $record) => $record->related === 'lead'),
                        TextEntry::make('customer.name')->label('Customer Name')
                            ->visible(fn(ProposalCustomer $record) => $record->related === 'customer'),
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

                InfolistSection::make('Proposal Order Details')
                    ->relationship('proposalOrder')
                    ->visible(function (ProposalCustomer $record) {
                        $order = $record->proposalOrder;
                        if ($order instanceof \Illuminate\Database\Eloquent\Collection) {
                            $order = $order->first();
                        }
                        return $order && $order->proposalItems && $order->proposalItems->count() > 0;
                    })
                    ->schema(function ($record) {
                        $order = $record->proposalOrder;
                        if ($order instanceof \Illuminate\Database\Eloquent\Collection) {
                            $order = $order->first();
                        }
                        if (!$order || !$order->proposalItems || $order->proposalItems->count() === 0) {
                            return [];
                        }
                        return [
                            RepeatableEntry::make('proposalItems')
                                ->label('Items')
                                ->schema([
                                    TextEntry::make('name')->label('Item Name'),
                                    TextEntry::make('description')->html()->limit(50)->tooltip(fn($state) => strip_tags($state)),
                                    TextEntry::make('qty')->numeric(),
                                    TextEntry::make('rate')->money('IDR'),
                                    TextEntry::make('amount')->money('IDR'),
                                    TextEntry::make('taxes.name')->label('Taxes')->badge()->separator(','),
                                ])->columns(3)->grid(2),
                            TextEntry::make('subtotal')->money('IDR'),
                            TextEntry::make('discount_fixed')->money('IDR'),
                            TextEntry::make('discount_percent')->suffix('%'),
                            TextEntry::make('adjustment')->money('IDR'),
                            TextEntry::make('total')->money('IDR')->weight('bold'),
                            TextEntry::make('client_note')->label('Client Note')->html()->columnSpanFull(),
                            TextEntry::make('terms_condition')->label('Terms & Condition')->html()->columnSpanFull(),
                        ];
                    })
                    ->columns(2)
                    ->collapsible(),

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
                Forms\Components\Tabs::make('Proposal Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Proposal Details')
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
                                                    ->label('Assigned To')
                                                    ->relationship('assigned', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                            ]),
                                        Forms\Components\TextInput::make('subject')
                                            ->label('Proposal Subject')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter proposal subject')
                                            ->columnSpanFull(),
                                    ])->columns(1),

                                Forms\Components\Section::make('Client Information')
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
                                            ->default('customer')
                                            ->afterStateUpdated(fn($state, $set) => $state === 'lead' ? $set('customer_id', null) : $set('lead_id', null)),

                                        Forms\Components\Select::make('lead_id')
                                            ->label('Select Lead')
                                            ->visible(fn($get) => $get('related') === 'lead')
                                            ->relationship('lead', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(fn($get) => $get('related') === 'lead')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('customer_id')
                                            ->label('Select Customer')
                                            ->visible(fn($get) => $get('related') === 'customer')
                                            ->relationship('customer', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(fn($get) => $get('related') === 'customer')
                                            ->columnSpanFull(),
                                    ])->columns(1),

                                Forms\Components\Section::make('Contact Information')
                                    ->description('Client contact details and address')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
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
                                            ]),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('+62 xxx xxxx xxxx'),
                                        Forms\Components\Textarea::make('address')
                                            ->label('Address')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Enter full address'),
                                        Forms\Components\Grid::make(3)
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
                                            ]),
                                        Forms\Components\Select::make('country_id')
                                            ->label('Country')
                                            ->relationship('country', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ])->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make('Proposal Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Template & Terms')
                                    ->description('Select proposal template and configure terms')
                                    ->schema([
                                        Forms\Components\Select::make('template_id')
                                            ->label('Proposal Template')
                                            ->relationship('proposalTemplate', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('payment_term')
                                                    ->label('Payment Terms')
                                                    ->relationship('paymentTerm', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                                Forms\Components\DatePicker::make('date')
                                                    ->label('Proposal Date')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false),
                                                Forms\Components\DatePicker::make('open_till')
                                                    ->label('Valid Until')
                                                    ->required()
                                                    ->default(now()->addDays(30))
                                                    ->native(false)
                                                    ->after('date'),
                                            ]),

                                        Forms\Components\Select::make('discount_type')
                                            ->label('Discount Calculation')
                                            ->options([
                                                'after_tax' => 'Apply Discount After Tax',
                                                'before_tax' => 'Apply Discount Before Tax',
                                            ])
                                            ->default('before_tax')
                                            ->required()
                                            ->native(false),
                                    ])->columns(1),

                                Forms\Components\Section::make('Contract Period')
                                    ->description('Define contract duration and warranty terms')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('contract_start_date')
                                                    ->label('Contract Start Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->default(now()),
                                                Forms\Components\DatePicker::make('contract_end_date')
                                                    ->label('Contract End Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->after('contract_start_date'),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('warranty_term')
                                                    ->label('Warranty Period')
                                                    ->required()
                                                    ->numeric()
                                                    ->default(12)
                                                    ->minValue(1),
                                                Forms\Components\Select::make('warranty_type')
                                                    ->label('Warranty Unit')
                                                    ->options([
                                                        'day' => 'Days',
                                                        'month' => 'Months',
                                                        'year' => 'Years',
                                                    ])
                                                    ->default('month')
                                                    ->required()
                                                    ->native(false),
                                            ]),
                                    ])->columns(1),

                                Forms\Components\Section::make('Additional Settings')
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
                                    ])->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make('Items & Pricing')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                // Forms\Components\Toggle::make('is_proposal_order')
                                //     ->label('Include Items & Pricing')
                                //     ->helperText('Add items, pricing, and service details to this proposal')
                                //     ->live()
                                //     ->default(true),
                                Forms\Components\Repeater::make('proposalOrder')
                                    ->relationship()
                                    // ->visible(fn($get) => $get('is_proposal_order'))
                                    ->schema([
                                        Forms\Components\Section::make('Items & Services')
                                            ->description('Add items and services for this proposal')
                                            ->schema([
                                                Forms\Components\Repeater::make('proposalItems')
                                                    ->relationship('proposalItems')
                                                    ->schema([
                                                        Forms\Components\Select::make('item_id')
                                                            ->label('Select Item/Service')
                                                            ->relationship('item', 'name')
                                                            ->preload()
                                                            ->live()
                                                            ->columnSpan(2)
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
                                                            ->columnSpan(2)
                                                            ->required(),

                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Description')
                                                            ->rows(2)
                                                            ->columnSpanFull(),

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
                                                                    // Assuming single tax for simplicity
                                                                    $taxRate = 0.1; // 10% - you might want to fetch actual tax rate
                                                                    $taxAmount = $subtotal * $taxRate;
                                                                }

                                                                $set('amount', $subtotal + $taxAmount);
                                                            }),

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
                                                                    $taxRate = 0.1;
                                                                    $taxAmount = $subtotal * $taxRate;
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
                                                                $rate = (float) $get('rate') ?: 0;
                                                                $qty = (float) $get('qty') ?: 1;

                                                                $subtotal = $rate * $qty;
                                                                $taxAmount = 0;

                                                                if (!empty($state)) {
                                                                    $taxRate = 0.1;
                                                                    $taxAmount = $subtotal * $taxRate;
                                                                }

                                                                $set('amount', $subtotal + $taxAmount);
                                                            }),

                                                        Forms\Components\TextInput::make('amount')
                                                            ->label('Total Amount')
                                                            ->prefix('Rp.')
                                                            ->mask(RawJs::make('$money($input, \',\')'))
                                                            ->stripCharacters('.')
                                                            ->numeric()
                                                            ->readOnly()
                                                            ->dehydrated(),
                                                    ])
                                                    ->columns(4)
                                                    ->defaultItems(1)
                                                    ->addActionLabel('Add Another Item')
                                                    ->reorderable()
                                                    ->collapsible()
                                                    ->cloneable()
                                                    ->columnSpanFull(),
                                            ]),

                                        Forms\Components\Section::make('Pricing Summary')
                                            ->description('Discounts, adjustments, and total calculations')
                                            ->schema([
                                                Forms\Components\Grid::make(4)
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('sub_total')
                                                            ->label('Subtotal')
                                                            ->columnStart(3)
                                                            ->columnSpan(2)
                                                            ->content(function ($get) {
                                                                $amount = collect($get('proposalItems'))->sum(function ($item) {
                                                                    return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                                });
                                                                return "Rp. " . number_format($amount, 0, ',', '.');
                                                            }),

                                                        Forms\Components\Hidden::make('subtotal')
                                                            ->dehydrateStateUsing(function ($get) {
                                                                $amount = collect($get('invoiceItem'))->sum(function ($item) {
                                                                    return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                                });
                                                                return $amount;
                                                            }),

                                                        Forms\Components\TextInput::make('discount_fixed')
                                                            ->label('Fixed Discount')
                                                            ->prefix('Rp.')
                                                            ->mask(RawJs::make('$money($input, \',\')'))
                                                            ->stripCharacters('.')
                                                            ->numeric()
                                                            ->columnSpan(2)
                                                            ->live()
                                                            ->disabled(fn($get) => !empty($get('discount_percent'))),

                                                        Forms\Components\Placeholder::make('nom_discount_fixed')
                                                            ->hiddenLabel()
                                                            ->columnSpan(2)
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
                                                            ->maxValue(100),

                                                        Forms\Components\Placeholder::make('nom_discount_percent')
                                                            ->hiddenLabel()
                                                            ->columnSpan(2)
                                                            ->content(function ($get) {
                                                                $amount = collect($get('proposalItems'))->sum(function ($item) {
                                                                    return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                                });
                                                                $discount_percent = (float) $get('discount_percent') ?: 0;
                                                                $total_discount = $amount * ($discount_percent / 100);
                                                                return $discount_percent > 0 ? "- Rp. " . number_format($total_discount, 0, ',', '.') : "";
                                                            }),

                                                        Forms\Components\TextInput::make('adjustment')
                                                            ->label('Adjustment')
                                                            ->helperText('Additional charges or credits')
                                                            ->prefix('Rp.')
                                                            ->columnSpan(2)
                                                            ->live()
                                                            ->stripCharacters('.')
                                                            ->mask(RawJs::make('$money($input, \',\')'))
                                                            ->numeric(),

                                                        Forms\Components\Placeholder::make('nom_adjustment')
                                                            ->hiddenLabel()
                                                            ->columnSpan(2)
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
                                                                $amount = collect($get('proposalItems'))->sum(function ($item) {
                                                                    return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                                });

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
                                                                    $amount = collect($get('proposalItems'))->sum(function ($item) {
                                                                        return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                                                    });

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
                                            ]),

                                        Forms\Components\Section::make('Service Details')
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

                                        Forms\Components\Section::make('Additional Information')
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
                                            ]),
                                    ])
                                    ->hiddenLabel()
                                    ->addable(false)
                                    ->deletable(false)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
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
                Tables\Columns\TextColumn::make('customer.name'),
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
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposalCustomers::route('/'),
            'create' => Pages\CreateProposalCustomer::route('/create'),
            'view' => Pages\ViewProposalCustomer::route('/{record}'),
            'edit' => Pages\EditProposalCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('customer_id', '!=', NULL)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
