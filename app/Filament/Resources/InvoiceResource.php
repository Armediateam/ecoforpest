<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Service;
use App\Models\Lead;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 1;

    // Helper method for calculating item amounts with proper tax calculation
    private static function calculateItemAmount($get, $set, $qtyState = null): void
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Invoice Information Section
                Forms\Components\Section::make('Invoice Information')
                    ->description('Basic invoice details and status')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->placeholder('Auto-generated invoice number')
                                    ->default(fn() => Invoice::generateInvoiceNumber())
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(255)
                                    ->suffixIcon('heroicon-m-hashtag'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->required()
                                    ->default('Unpaid')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Unpaid' => 'Unpaid',
                                        'Paid' => 'Paid',
                                        'Overdue' => 'Overdue',
                                    ])
                                    ->native(false)
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('created_by')
                                    ->label('Created By')
                                    ->default(fn() => auth()->id())
                                    ->disabled()
                                    ->dehydrated()
                                    ->hidden(),
                            ]),
                    ]),

                // Customer Information Section
                Forms\Components\Section::make('Client Information')
                    ->description('Select customer or lead and auto-populate billing information')
                    ->icon('heroicon-o-user-circle')
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
                            ->label('Lead')
                            ->visible(fn($get) => $get('related') === 'lead')
                            ->relationship('lead', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Lead $record) => "{$record->name} - {$record->company}")
                            ->searchable(['name', 'company'])
                            ->preload()
                            ->required(fn($get) => $get('related') === 'lead')
                            ->live()
                            ->placeholder('Search and select a lead...')
                            ->suffixIcon('heroicon-m-magnifying-glass')
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('billing_address', null);
                                    $set('billing_city', null);
                                    $set('billing_state', null);
                                    $set('billing_zip_code', null);
                                    $set('billing_country', null);

                                    $set('shipping_address', null);
                                    $set('shipping_city', null);
                                    $set('shipping_state', null);
                                    $set('shipping_zip_code', null);
                                    $set('shipping_country', null);
                                    return;
                                }

                                // Auto-populate billing information from customer
                                $lead = Lead::find($state);
                                if ($lead) {
                                    $set('billing_address', $lead->address);
                                    $set('billing_city', $lead->city);
                                    $set('billing_state', $lead->state);
                                    $set('billing_zip_code', $lead->zip_code);
                                    $set('billing_country', $lead->country->name ?? null);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->visible(fn($get) => $get('related') === 'customer')
                            ->relationship('customer', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->name} - {$record->company}")
                            ->searchable(['name', 'company'])
                            ->preload()
                            ->required(fn($get) => $get('related') === 'customer')
                            ->live()
                            ->placeholder('Search and select a customer...')
                            ->suffixIcon('heroicon-m-magnifying-glass')
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('billing_address', null);
                                    $set('billing_city', null);
                                    $set('billing_state', null);
                                    $set('billing_zip_code', null);
                                    $set('billing_country', null);

                                    $set('shipping_address', null);
                                    $set('shipping_city', null);
                                    $set('shipping_state', null);
                                    $set('shipping_zip_code', null);
                                    $set('shipping_country', null);
                                    return;
                                }

                                // Auto-populate billing information from customer
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('billing_address', $customer->address);
                                    $set('billing_city', $customer->city);
                                    $set('billing_state', $customer->state);
                                    $set('billing_zip_code', $customer->zip_code);
                                    $set('billing_country', $customer->country->name ?? null);
                                }
                            })
                            ->columnSpanFull(),
                    ]),
                // Billing & Shipping Information
                Forms\Components\Section::make('Billing & Shipping Information')
                    ->description('Customer billing and shipping addresses')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Billing Address Section
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Bill To')
                                            ->icon('heroicon-m-building-office-2')
                                            ->schema([
                                                Forms\Components\TextInput::make('billing_address')
                                                    ->label('Street Address')
                                                    ->placeholder('Enter billing address...')
                                                    ->columnSpanFull(),
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('billing_city')
                                                            ->label('City')
                                                            ->placeholder('City'),
                                                        Forms\Components\TextInput::make('billing_state')
                                                            ->label('State/Province')
                                                            ->placeholder('State'),
                                                    ]),
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('billing_zip_code')
                                                            ->label('ZIP Code')
                                                            ->placeholder('ZIP'),
                                                        Forms\Components\TextInput::make('billing_country')
                                                            ->label('Country')
                                                            ->placeholder('Country'),
                                                    ]),
                                            ]),
                                    ]),

                                // Shipping Address Section
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Ship To')
                                            ->icon('heroicon-m-truck')
                                            ->schema([
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('copy_billing')
                                                        ->label('Copy from Billing')
                                                        ->icon('heroicon-m-document-duplicate')
                                                        ->size('sm')
                                                        ->color('gray')
                                                        ->action(function (Set $set, $get) {
                                                            $set('shipping_address', $get('billing_address'));
                                                            $set('shipping_city', $get('billing_city'));
                                                            $set('shipping_state', $get('billing_state'));
                                                            $set('shipping_zip_code', $get('billing_zip_code'));
                                                            $set('shipping_country', $get('billing_country'));
                                                        }),
                                                ])
                                                    ->columnSpanFull(),
                                                Forms\Components\TextInput::make('shipping_address')
                                                    ->label('Street Address')
                                                    ->placeholder('Enter shipping address...')
                                                    ->columnSpanFull(),
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('shipping_city')
                                                            ->label('City')
                                                            ->placeholder('City'),
                                                        Forms\Components\TextInput::make('shipping_state')
                                                            ->label('State/Province')
                                                            ->placeholder('State'),
                                                    ]),
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('shipping_zip_code')
                                                            ->label('ZIP Code')
                                                            ->placeholder('ZIP'),
                                                        Forms\Components\TextInput::make('shipping_country')
                                                            ->label('Country')
                                                            ->placeholder('Country'),
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                    ]),
                // Invoice Terms & Details
                Forms\Components\Section::make('Invoice Terms & Details')
                    ->description('Payment terms, dates, and invoice settings')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('invoice_date')
                                    ->label('Invoice Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->suffixIcon('heroicon-m-calendar'),

                                Forms\Components\DatePicker::make('invoice_due_date')
                                    ->label('Due Date')
                                    ->required()
                                    ->native(false)
                                    ->suffixIcon('heroicon-m-calendar')
                                    ->after('invoice_date')
                                    ->helperText('Due date must be after invoice date')
                                    ->rules([
                                        'after:invoice_date'
                                    ]),

                                Forms\Components\Select::make('payment_term')
                                    ->label('Payment Terms')
                                    ->relationship('paymentTerm', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select payment terms...')
                                    ->suffixIcon('heroicon-m-credit-card'),

                                Forms\Components\Select::make('payment_type')
                                    ->label('Tipe Pembayaran')
                                    ->options([
                                        'xendit' => 'Xendit',
                                        'manual' => 'Manual',
                                    ])
                                    ->default('manual')
                                    ->searchable()
                                    ->live()
                                    ->placeholder('Pilih tipe pembayaran...')
                                    ->required()
                                    ->suffixIcon('heroicon-m-credit-card'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('allowed_payment_method')
                                    ->label('Allowed Payment Methods')
                                    ->markAsRequired(fn($get) => $get('payment_type') === 'manual')
                                    ->multiple()
                                    ->visible(fn($get) => $get('payment_type') === 'manual')
                                    ->options(function () {
                                        $banksSetting = \App\Models\Setting::where('key', 'banks')->first();

                                        if ($banksSetting && $banksSetting->value) {
                                            $banksData = is_array($banksSetting->value) ? $banksSetting->value : json_decode($banksSetting->value, true);

                                            if (is_array($banksData)) {
                                                foreach ($banksData as $key => $value) {
                                                    // Use bank name as both key and value for selection options
                                                    $banksData[$key] = $key;
                                                }
                                                return $banksData;
                                            }
                                        }

                                        // Fallback to default values if setting not found
                                        return [
                                            'Tunai' => 'Tunai'
                                        ];
                                    })
                                    //get all existing banks from settings table
                                    ->default(function () {
                                        $banksSetting = \App\Models\Setting::where('key', 'banks')->first();
                                        if ($banksSetting && $banksSetting->value) {
                                            $banksData = is_array($banksSetting->value) ? $banksSetting->value : json_decode($banksSetting->value, true);
                                            if (is_array($banksData)) {
                                                return array_keys($banksData);
                                            }
                                        }
                                        return ['Tunai'];
                                    })
                                    ->placeholder('Select payment methods...')
                                    ->helperText('Select multiple payment methods allowed for this invoice'),

                                Forms\Components\Select::make('sale_agent')
                                    ->label('Sales Agent')
                                    ->relationship('saleAgent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select sales agent...')
                                    ->suffixIcon('heroicon-m-user'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('discount_type')
                                    ->label('Discount Application')
                                    ->required()
                                    ->options([
                                        'after_tax' => 'Apply Discount After Tax',
                                        'before_tax' => 'Apply Discount Before Tax',
                                    ])
                                    ->default('before_tax')
                                    ->native(false)
                                    ->helperText('When to apply discounts in calculation'),

                                Forms\Components\Select::make('recuring_invoices')
                                    ->label('Recurring Schedule')
                                    ->required()
                                    ->options([
                                        'No' => 'No Recurring',
                                        'Every 1 Week' => 'Every Week',
                                        'Every 2 Week' => 'Every 2 Weeks',
                                        'Every 3 Week' => 'Every 3 Weeks',
                                        'Every 1 Month' => 'Monthly',
                                        'Every 2 Month' => 'Every 2 Months',
                                        'Every 3 Month' => 'Quarterly',
                                        'Every 6 Month' => 'Semi-Annually',
                                        'Every 12 Month' => 'Annually',
                                        'Custom Interval' => 'Custom Interval',
                                    ])
                                    ->live()
                                    ->selectablePlaceholder(false)
                                    ->default('No')
                                    ->native(false),
                                Forms\Components\TextInput::make('custom_recuring')
                                    ->label('Custom Interval')
                                    ->placeholder('e.g., "45 days", "3 months"')
                                    ->visible(fn($get) => $get('recuring_invoices') === 'Custom Interval')
                                    ->required(fn($get) => $get('recuring_invoices') === 'Custom Interval')
                                    ->helperText('Specify custom recurring interval (e.g., "45 days", "3 months")')
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('work_order_id')
                                    ->label('Related Work Order')
                                    ->options(function () {
                                        return WorkOrder::with(['customer', 'lead'])->get()
                                            ->mapWithKeys(function ($workOrder) {
                                                if ($workOrder->customer) {
                                                    $visitCount = WorkOrder::where('customer_id', $workOrder->customer->id)
                                                        ->where('id', '<=', $workOrder->id)
                                                        ->count();
                                                    $label = $workOrder->customer->name .
                                                        ' Kunjungan - Ke ' . $visitCount . ' (Customer)';
                                                    return [$workOrder->id => $label];
                                                }
                                                if ($workOrder->lead) {
                                                    $visitCount = WorkOrder::where('lead_id', $workOrder->lead->id)
                                                        ->where('id', '<=', $workOrder->id)
                                                        ->count();
                                                    $label = $workOrder->lead->name .
                                                        ' Kunjungan - Ke ' . $visitCount . ' (Lead)';

                                                    return [$workOrder->id => $label];
                                                }
                                                return [];
                                            })
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Link to work order (optional)...')
                                    ->suffixIcon('heroicon-m-wrench-screwdriver'),

                                Forms\Components\Textarea::make('admin_note')
                                    ->label('Admin Notes')
                                    ->placeholder('Internal notes for admin use...')
                                    ->rows(3)
                                    ->helperText('Internal notes (not visible to client)'),
                            ]),
                    ]),
                // Invoice Items Section
                Forms\Components\Section::make('Invoice Items')
                    ->description('Add items to this invoice')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Forms\Components\Repeater::make('invoiceItem')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Select::make('item')
                                            ->label('Item')
                                            ->relationship('item', 'name')
                                            ->preload()
                                            ->live()
                                            ->searchable()
                                            ->columnSpanFull()
                                            ->placeholder('Search items...')
                                            ->suffixIcon('heroicon-m-magnifying-glass')
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (!$state) return;

                                                $item = Item::find($state);
                                                if (!$item) return;

                                                $qty = $item->min_inventory_qty ?? 1;
                                                $rate = $item->rate ?? 0;

                                                // Calculate tax properly
                                                $taxRate = 0;
                                                $taxAmount = 0;
                                                if ($item->tax) {
                                                    $taxRate = $item->tax->value / 100;
                                                }

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
                                            ->columnSpan(3)
                                            ->placeholder('Item name...'),

                                        Forms\Components\TextInput::make('qty')
                                            ->label('Qty')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(9999)
                                            ->columnSpan(1)
                                            ->live(debounce: 800)
                                            ->rules(['min:1', 'max:9999'])
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateItemAmount($get, $set, $state);
                                            }),

                                        Forms\Components\TextInput::make('rate')
                                            ->label('Unit Price')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->columnSpan(3)
                                            ->live(debounce: '5s')
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateItemAmount($get, $set);
                                            }),

                                        Forms\Components\Select::make('taxes')
                                            ->label('Tax')
                                            ->relationship('taxes', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->multiple()
                                            ->columnSpan(2)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateItemAmount($get, $set);
                                            }),

                                        Forms\Components\TextInput::make('amount')
                                            ->label('Total Inc. Tax')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->readOnly()
                                            ->dehydrated()
                                            ->columnSpan(3)
                                            ->extraInputAttributes(['class' => 'bg-gray-50 font-semibold']),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpan(12)
                                            ->placeholder('Optional item description...'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Another Item')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'New Item')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Invoice Services')
                    ->description('Add services to this invoice')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Forms\Components\Repeater::make('invoiceService')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(12)
                                    ->schema([
                                        Forms\Components\Select::make('service_id')
                                            ->label('Service')
                                            ->relationship('service', 'name')
                                            ->preload()
                                            ->live()
                                            ->searchable()
                                            ->columnSpanFull()
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
                                            ->columnSpan(3)
                                            ->placeholder('Service name...'),

                                        Forms\Components\TextInput::make('qty')
                                            ->label('Qty')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(9999)
                                            ->columnSpan(1)
                                            ->live(debounce: 800)
                                            ->rules(['min:1', 'max:9999'])
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateServiceAmount($get, $set, $state);
                                            }),

                                        Forms\Components\TextInput::make('rate')
                                            ->label('Service Price')
                                            ->prefix('Rp.')
                                            ->mask(RawJs::make('$money($input, \',\')'))
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->columnSpan(3)
                                            ->live(debounce: '5s')
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                self::calculateServiceAmount($get, $set);
                                            }),

                                        Forms\Components\Select::make('taxes')
                                            ->label('Tax')
                                            ->relationship('taxes', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->multiple()
                                            ->columnSpan(2)
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
                                            ->columnSpan(3)
                                            ->extraInputAttributes(['class' => 'bg-gray-50 font-semibold']),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpan(12)
                                            ->placeholder('Optional item description...'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Another Service')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'New Service')
                            ->columnSpanFull(),
                    ]),
                // Pricing Summary Section
                Forms\Components\Section::make('Pricing Summary')
                    ->description('Discounts, adjustments, and total calculations')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\Grid::make(6)
                            ->schema([
                                // Subtotal Display
                                Forms\Components\Placeholder::make('sub_total')
                                    ->label('Subtotal')
                                    ->columnStart(4)
                                    ->columnSpan(3)
                                    ->extraAttributes(['class' => 'text-right'])
                                    ->content(function ($get) {
                                        $amount_item = collect($get('invoiceItem'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });
                                        $amount_service = collect($get('invoiceService'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });

                                        $amount = $amount_item + $amount_service;
                                        return "Rp. " . number_format($amount, 0, ',', '.');
                                    }),

                                Forms\Components\Hidden::make('subtotal')
                                    ->dehydrateStateUsing(function ($get) {
                                        $amount_item = collect($get('invoiceItem'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });
                                        $amount_service = collect($get('invoiceService'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });

                                        $amount = $amount_item + $amount_service;
                                        return $amount;
                                    }),

                                // Fixed Discount
                                Forms\Components\TextInput::make('discount_fixed')
                                    ->label('Fixed Discount')
                                    ->prefix('Rp.')
                                    ->mask(RawJs::make('$money($input, \',\')'))
                                    ->stripCharacters('.')
                                    ->numeric()
                                    ->columnSpan(3)
                                    ->live(debounce: 500)
                                    ->disabled(fn($get) => !empty($get('discount_percent')))
                                    ->placeholder('Enter fixed discount amount...')
                                    ->helperText('Enter a fixed discount amount'),

                                Forms\Components\Placeholder::make('nom_discount_fixed')
                                    ->hiddenLabel()
                                    ->columnSpan(3)
                                    ->extraAttributes(['class' => 'text-right text-red-600'])
                                    ->content(function ($get) {
                                        $discount_fixed = (int) preg_replace('/[^0-9]/', '', $get('discount_fixed') ?? '0');
                                        return $discount_fixed > 0 ? "- Rp. " . number_format($discount_fixed, 0, ',', '.') : "";
                                    }),

                                // Percentage Discount
                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Percentage Discount')
                                    ->suffix('%')
                                    ->inputMode('decimal')
                                    ->columnSpan(3)
                                    ->disabled(fn($get) => !empty($get('discount_fixed')))
                                    ->live(debounce: 500)
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('0-100%')
                                    ->helperText('Or enter a percentage discount')
                                    ->rules(['min:0', 'max:100']),

                                Forms\Components\Placeholder::make('nom_discount_percent')
                                    ->hiddenLabel()
                                    ->columnSpan(3)
                                    ->extraAttributes(['class' => 'text-right text-red-600'])
                                    ->content(function ($get) {
                                        $amount_item = collect($get('invoiceItem'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });
                                        $amount_service = collect($get('invoiceService'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });

                                        $amount = $amount_item + $amount_service;
                                        $discount_percent = (float) $get('discount_percent') ?: 0;
                                        $total_discount = $amount * ($discount_percent / 100);
                                        return $discount_percent > 0 ? "- Rp. " . number_format($total_discount, 0, ',', '.') : "";
                                    }),

                                // Adjustment
                                Forms\Components\TextInput::make('adjustment')
                                    ->label('Adjustment')
                                    ->helperText('Additional charges (+) or credits (-)')
                                    ->prefix('Rp.')
                                    ->columnSpan(3)
                                    ->numeric()
                                    ->live(debounce: 500)
                                    ->placeholder('Enter adjustment amount...')
                                    ->suffixIcon('heroicon-m-adjustments-horizontal'),

                                Forms\Components\Placeholder::make('nom_adjustment')
                                    ->hiddenLabel()
                                    ->columnSpan(3)
                                    ->extraAttributes(['class' => 'text-right'])
                                    ->content(function ($get) {
                                        $adjustment = (int) preg_replace('/[^0-9]/', '', $get('adjustment') ?? '0');
                                        if ($adjustment > 0) {
                                            return "+ Rp. " . number_format($adjustment, 0, ',', '.');
                                        } elseif ($adjustment < 0) {
                                            return "- Rp. " . number_format(abs($adjustment), 0, ',', '.');
                                        }
                                        return "";
                                    }),

                                // Final Total
                                Forms\Components\Placeholder::make('total')
                                    ->label('Final Total')
                                    ->columnStart(4)
                                    ->columnSpan(3)
                                    ->extraAttributes(['class' => 'text-right font-bold text-xl text-green-600'])
                                    ->content(function ($get) {
                                        $amount_item = collect($get('invoiceItem'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });
                                        $amount_service = collect($get('invoiceService'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });

                                        $amount = $amount_item + $amount_service;

                                        $discount_fixed = (int) preg_replace('/[^0-9]/', '', $get('discount_fixed') ?? '0');
                                        $discount_percent = (float) $get('discount_percent') ?? 0;
                                        $adjustment = (int) preg_replace('/[^0-9]/', '', $get('adjustment') ?? '0');

                                        $total_discount_percent = $amount * ($discount_percent / 100);
                                        $discount = $discount_fixed ?: $total_discount_percent;
                                        $total = $amount - $discount + $adjustment;

                                        return "Rp. " . number_format(max(0, $total), 0, ',', '.');
                                    }),

                                Forms\Components\Hidden::make('total')
                                    ->dehydrateStateUsing(function ($get) {
                                        $amount_item = collect($get('invoiceItem'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });
                                        $amount_service = collect($get('invoiceService'))->sum(function ($item) {
                                            return (int) preg_replace('/[^0-9]/', '', $item['amount'] ?? '0');
                                        });

                                        $amount = $amount_item + $amount_service;

                                        $discount_fixed = (int) preg_replace('/[^0-9]/', '', $get('discount_fixed') ?? '0');
                                        $discount_percent = (float) $get('discount_percent') ?? 0;
                                        $adjustment = (int) preg_replace('/[^0-9]/', '', $get('adjustment') ?? '0');

                                        $total_discount_percent = $amount * ($discount_percent / 100);
                                        $discount = $discount_fixed ?: $total_discount_percent;
                                        $total = $amount - $discount + $adjustment;

                                        return $total;
                                    }),
                            ]),
                    ]),

                // Service Details Section
                Forms\Components\Section::make('Service Details')
                    ->description('Pest control targets and treatment information')
                    ->icon('heroicon-o-bug-ant')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('target_detail')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('target')
                                            ->label('Target Pest')
                                            ->placeholder('e.g., Cockroaches, Ants, Termites')
                                            ->maxLength(255)
                                            ->suffixIcon('heroicon-m-bug-ant'),

                                        Forms\Components\TextInput::make('treatment_area')
                                            ->label('Treatment Area')
                                            ->placeholder('e.g., Kitchen, Office, Warehouse')
                                            ->maxLength(255)
                                            ->suffixIcon('heroicon-m-building-office'),

                                        Forms\Components\TextInput::make('method_use')
                                            ->label('Treatment Method')
                                            ->placeholder('e.g., Spray, Bait, Gel Application')
                                            ->maxLength(255)
                                            ->suffixIcon('heroicon-m-beaker'),

                                        Forms\Components\TextInput::make('unit_amount')
                                            ->label('Unit/Amount')
                                            ->placeholder('e.g., per room, per m², 500ml')
                                            ->maxLength(255)
                                            ->suffixIcon('heroicon-m-calculator'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('Add Target Detail')
                            ->itemLabel(fn(array $state): ?string => $state['target'] ?? 'New Target')
                            ->columnSpanFull(),
                    ]),

                // Additional Information Section
                Forms\Components\Section::make('Additional Information')
                    ->description('Notes and terms for the client')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\RichEditor::make('client_note')
                                    ->label('Client Notes')
                                    ->helperText('Special notes or instructions visible to the client')
                                    ->default(
                                        '<ul>' .
                                            '<li>Overtime fees applies if there is job outside working hours.</li>' .
                                            '<li>Complaints will be handled 1 time in 24 hours on work days.</li>' .
                                            '<li>If the complaint outside of the contract period, normal fees will be charged as the initial fee.</li>' .
                                            '</ul>'
                                    )
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'undo',
                                        'redo',
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\RichEditor::make('terms_condition')
                                    ->label('Terms & Conditions')
                                    ->helperText('Specific terms and conditions for this invoice')
                                    ->default(
                                        '<ul>' .
                                            '<li>Prices include income tax.</li>' .
                                            '<li>If a tax invoice is requested, an additional 11% VAT will be applied.</li>' .
                                            '<li>Payment is due in advance or at the final meeting.</li>' .
                                            '<li>If payment is not made by the specified time, the second party has the right to collect directly from the first party, and the first party is required to pay immediately.</li>' .
                                            '<li>If treatment is requested outside the contract period, an additional fee will be charged or a new contract will be added.</li>' .
                                            '<li>If the customer cancels the treatment within the contract period, the treatment will be considered forfeited, and changes to the treatment schedule may not be made outside the contract period.</li>' .
                                            '<li>This treatment also excludes pests other than those listed.</li>' .
                                            '</ul>'
                                    )
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'undo',
                                        'redo',
                                    ])
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\RichEditor::make('email_text')
                            ->label('Email Text')
                            ->helperText('Custom email message when sending this invoice')
                            ->placeholder('Enter custom email message (optional)...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Invoice number copied')
                    ->copyMessageDuration(1500)
                    ->weight('medium')
                    ->icon('heroicon-m-hashtag'),

                Tables\Columns\TextColumn::make('related')
                    ->formatStateUsing(fn(string $state) => $state === 'customer' ? 'Customer' : ($state === 'lead' ? 'Lead' : 'N/A'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->placeholder('No data')
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('lead.name')
                    ->label('Lead')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->placeholder('No data')
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'paid' => 'success',
                        'down payment' => 'success',
                        'unpaid' => 'warning',
                        'overdue' => 'danger',
                        'draft' => 'gray',
                        default => 'primary',
                    })
                    ->icon(fn(string $state): string => match (strtolower($state)) {
                        'paid' => 'heroicon-m-check-circle',
                        'down payment' => 'heroicon-m-check-circle',
                        'unpaid' => 'heroicon-m-clock',
                        'overdue' => 'heroicon-m-exclamation-triangle',
                        'draft' => 'heroicon-m-document',
                        default => 'heroicon-m-information-circle',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'PAID' => 'success',
                        'DOWN PAYMENT' => 'success',
                        'PENDING' => 'warning',
                        'EXPIRED' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_url')
                    ->label('Payment URL')
                    ->formatStateUsing(fn($state) => $state ? 'View Payment' : null)
                    ->badge()
                    ->url(fn($record) => $record->payment_url, true)
                    ->openUrlInNewTab()
                    ->toggleable()
                    ->color('primary')
                    ->icon('heroicon-m-link'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->weight('medium')
                    ->color('success')
                    ->icon('heroicon-m-banknotes'),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('invoice_due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function ($record): string {
                        $dueDate = \Carbon\Carbon::parse($record->invoice_due_date);
                        $today = \Carbon\Carbon::today();

                        if ($dueDate->isPast() && strtolower($record->status) !== 'paid') {
                            return 'danger';
                        } elseif ($dueDate->diffInDays($today) <= 3 && strtolower($record->status) !== 'paid') {
                            return 'warning';
                        }
                        return 'gray';
                    })
                    ->icon(function ($record): string {
                        $dueDate = \Carbon\Carbon::parse($record->invoice_due_date);
                        $today = \Carbon\Carbon::today();

                        if ($dueDate->isPast() && strtolower($record->status) !== 'paid') {
                            return 'heroicon-m-exclamation-triangle';
                        } elseif ($dueDate->diffInDays($today) <= 3 && strtolower($record->status) !== 'paid') {
                            return 'heroicon-m-clock';
                        }
                        return 'heroicon-m-calendar';
                    }),

                Tables\Columns\TextColumn::make('saleAgent.name')
                    ->label('Sales Agent')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('paymentTerm.name')
                    ->label('Payment Terms')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('workOrder.id')
                    ->label('Work Order')
                    ->prefix('#')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->icon('heroicon-m-wrench-screwdriver'),

                Tables\Columns\TextColumn::make('recuring_invoices')
                    ->label('Recurring')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'No' ? 'gray' : 'info')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn(string $state): string => $state === 'No' ? 'One-time' : $state),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-user-plus'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->icon('heroicon-m-clock'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Draft' => 'Draft',
                        'Unpaid' => 'Unpaid',
                        'Paid' => 'Paid',
                        'Overdue' => 'Overdue',
                    ])
                    ->indicator('Status')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Customer')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('sale_agent')
                    ->label('Sales Agent')
                    ->relationship('saleAgent', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Sales Agent')
                    ->multiple(),

                Tables\Filters\Filter::make('due_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')
                            ->label('Due From')
                            ->placeholder('Select start date'),
                        Forms\Components\DatePicker::make('due_until')
                            ->label('Due Until')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('invoice_due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('invoice_due_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['due_from'] ?? null) {
                            $indicators['due_from'] = 'Due from ' . \Carbon\Carbon::parse($data['due_from'])->toFormattedDateString();
                        }
                        if ($data['due_until'] ?? null) {
                            $indicators['due_until'] = 'Due until ' . \Carbon\Carbon::parse($data['due_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Amount From')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Minimum amount'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Amount To')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Maximum amount'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn(Builder $query, $amount): Builder => $query->where('total', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn(Builder $query, $amount): Builder => $query->where('total', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['amount_from'] ?? null) {
                            $indicators['amount_from'] = 'Amount from Rp ' . number_format($data['amount_from']);
                        }
                        if ($data['amount_to'] ?? null) {
                            $indicators['amount_to'] = 'Amount to Rp ' . number_format($data['amount_to']);
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Invoices')
                    ->query(fn(Builder $query): Builder => $query->where('invoice_due_date', '<', now())->where('status', '!=', 'Paid'))
                    ->toggle(),

                Tables\Filters\Filter::make('due_soon')
                    ->label('Due Within 7 Days')
                    ->query(fn(Builder $query): Builder => $query->whereBetween('invoice_due_date', [now(), now()->addDays(7)])->where('status', '!=', 'Paid'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('recuring_invoices')
                    ->label('Recurring Type')
                    ->options([
                        'No' => 'One-time',
                        'Every 1 Month' => 'Monthly',
                        'Every 2 Month' => 'Every 2 Months',
                        'Every 3 Month' => 'Quarterly',
                        'Every 6 Month' => 'Semi-Annually',
                        'Every 12 Month' => 'Annually',
                    ])
                    ->indicator('Recurring'),

                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('success')
                        ->action(function (Invoice $record) {
                            $newInvoice = $record->replicate();
                            $newInvoice->invoice_number = Invoice::generateInvoiceNumber();
                            $newInvoice->status = 'Draft';
                            $newInvoice->invoice_date = now();
                            $newInvoice->created_by = auth()->id();
                            $newInvoice->save();

                            // Duplicate invoice items
                            foreach ($record->invoiceItem as $item) {
                                $newItem = $item->replicate();
                                $newItem->invoice_id = $newInvoice->id;
                                $newItem->save();

                                // Duplicate item taxes
                                $newItem->taxes()->sync($item->taxes->pluck('id'));
                            }

                            // Duplicate invoice services
                            foreach ($record->invoiceService as $service) {
                                $newService = $service->replicate();
                                $newService->invoice_id = $newInvoice->id;
                                $newService->save();

                                // Duplicate service taxes
                                $newService->taxes()->sync($service->taxes->pluck('id'));
                            }

                            return redirect()->route('filament.secret.resources.invoices.edit', $newInvoice);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Invoice')
                        ->modalDescription('Are you sure you want to duplicate this invoice? A new draft invoice will be created.'),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Actions')
                    ->color('gray')
                    ->button()
                    ->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'Paid']);
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Mark Invoices as Paid')
                        ->modalDescription('Are you sure you want to mark the selected invoices as paid?'),
                    Tables\Actions\BulkAction::make('mark_as_unpaid')
                        ->label('Mark as Unpaid')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'Unpaid']);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchOnBlur()
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Invoice Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('invoice_number')
                                ->label('Invoice Number'),
                            Infolists\Components\TextEntry::make('status')
                                ->badge()
                                ->color(fn(string $state): string => match (strtolower($state)) {
                                    'paid' => 'success',
                                    'down payment' => 'success',
                                    'unpaid' => 'warning',
                                    'overdue' => 'danger',
                                    default => 'gray',
                                }),

                            Infolists\Components\TextEntry::make('payment_status')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'PAID' => 'success',
                                    'DOWN PAYMENT' => 'success',
                                    'PENDING' => 'warning',
                                    'EXPIRED' => 'danger',
                                    default => 'gray',
                                }),

                            // Payment URL with copy button and link
                            Infolists\Components\TextEntry::make('payment_type')
                                ->label('Payment Type'),
                            Infolists\Components\TextEntry::make('payment_url')
                                ->label('Payment URL')
                                ->formatStateUsing(fn($state) => $state ? 'View Payment' : null)
                                ->badge()
                                ->icon('heroicon-m-link')
                                ->url(fn($state) => $state)
                                ->openUrlInNewTab()
                                ->copyable()
                                ->visible(fn($record) => !empty($record->payment_url)),
                            Infolists\Components\TextEntry::make('lead.name')->label('Lead Name')
                                ->visible(fn(Invoice $record) => $record->related === 'lead'),
                            Infolists\Components\TextEntry::make('customer.name')->label('Customer Name')
                                ->visible(fn(Invoice $record) => $record->related === 'customer'),
                            Infolists\Components\TextEntry::make('invoice_date')
                                ->label('Invoice Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('invoice_due_date')
                                ->label('Due Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('paymentTerm.name')
                                ->label('Payment Term'),
                            Infolists\Components\TextEntry::make('saleAgent.name')
                                ->label('Sales Agent'),
                            Infolists\Components\TextEntry::make('createdBy.name')
                                ->label('Created By'),
                            Infolists\Components\TextEntry::make('workOrder.id')
                                ->label('Work Order #')
                                ->placeholder('N/A'),
                        ]),
                    ]),

                // --- Billing and Shipping Section ---
                Infolists\Components\Section::make('Billing & Shipping Information')
                    ->icon('heroicon-o-home-modern')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            // Billing Address
                            Infolists\Components\Group::make()->schema([
                                Infolists\Components\TextEntry::make('lead.phone')->label('Lead Phone')
                                    ->visible(fn(Invoice $record) => $record->related === 'lead')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('customer.phone')->label('Customer Phone')
                                    ->visible(fn(Invoice $record) => $record->related === 'customer')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('billing_address')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('billing_city')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('billing_state')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('billing_zip_code')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('billing_country')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                            ]),
                            // Shipping Address
                            Infolists\Components\Group::make()->schema([
                                Infolists\Components\TextEntry::make('lead.email')->label('Lead Email')
                                    ->visible(fn(Invoice $record) => $record->related === 'lead')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('customer.email')->label('Customer Email')
                                    ->visible(fn(Invoice $record) => $record->related === 'customer')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('shipping_address')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('shipping_city')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('shipping_state')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('shipping_zip_code')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                                Infolists\Components\TextEntry::make('shipping_country')
                                    ->formatStateUsing(fn($state) => empty($state) ? '-' : $state),
                            ]),
                        ]),
                    ]),

                // --- Invoice Items Section ---
                Infolists\Components\Section::make('Invoice Items & Services')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('invoiceItem')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Item')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('qty')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('rate')
                                    ->label('Rate')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('taxes.name')
                                    ->label('Taxes')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Total')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull()
                                    ->hidden(fn($state) => blank($state)),
                            ]),
                        Infolists\Components\RepeatableEntry::make('invoiceService')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Service')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('qty')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('rate')
                                    ->label('Rate')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Total')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull()
                                    ->hidden(fn($state) => blank($state)),
                            ]),
                        Infolists\Components\TextEntry::make('subtotal')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('discount_fixed')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('discount_percent')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('adjusment')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total')
                            ->money('IDR'),
                    ]),

                // --- Service Details Repeater ---
                Infolists\Components\Section::make('Service Details')
                    ->icon('heroicon-o-bug-ant')
                    ->description('Pest control targets and treatment information')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('target_detail')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('target')
                                    ->label('Target Pest'),
                                Infolists\Components\TextEntry::make('treatment_area')
                                    ->label('Area'),
                                Infolists\Components\TextEntry::make('method_use')
                                    ->label('Method'),
                                Infolists\Components\TextEntry::make('unit_amount')
                                    ->label('Unit/Amount'),
                            ])->grid(4)
                    ])->hidden(fn(Invoice $record) => $record->target_detail == NULL),

                // --- Pricing Summary Section ---
                Infolists\Components\Section::make('Summary')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            // Left column for notes and payment methods
                            Infolists\Components\Group::make()->schema([
                                Infolists\Components\TextEntry::make('admin_note')
                                    ->label('Admin Note')
                                    ->hidden(fn($state) => blank($state)),
                                Infolists\Components\TextEntry::make('allowed_payment_method')
                                    ->label('Allowed Payment Methods')
                                    ->badge()
                                    ->listWithLineBreaks(),
                            ]),
                            // Right column for pricing summary                        
                        ]),
                    ]),

                // --- Additional Information Section ---
                Infolists\Components\Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('client_note')
                            ->label('Notes for Client')
                            ->html()
                            ->columnSpanFull()
                            ->hidden(fn($state) => blank($state)),
                        Infolists\Components\TextEntry::make('terms_condition')
                            ->label('Terms & Conditions')
                            ->html()
                            ->columnSpanFull()
                            ->hidden(fn($state) => blank($state)),
                    ]),
            ]);
    }
}
