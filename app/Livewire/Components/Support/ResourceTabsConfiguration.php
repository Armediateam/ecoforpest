<?php

namespace App\Livewire\Components\Support;

use Illuminate\Database\Eloquent\Model;

class ResourceTabsConfiguration
{
    protected array $tabs = [];
    protected array $tabContents = [];

    public static function make(): self
    {
        return new static();
    }

    /**
     * Add a tab to the configuration
     */
    public function addTab(string $key, array $config): self
    {
        $this->tabs[$key] = array_merge([
            'label' => ucfirst($key),
            'icon' => null,
            'description' => null,
            'badge' => null,
            'actions' => []
        ], $config);

        return $this;
    }

    /**
     * Add tab content configuration
     */
    public function addTabContent(string $key, array $config): self
    {
        $this->tabContents[$key] = $config;
        return $this;
    }

    /**
     * Add a view-based tab content
     */
    public function addViewTab(string $key, string $viewPath, array $tabConfig = [], array $viewData = []): self
    {
        $this->addTab($key, $tabConfig);
        $this->addTabContent($key, [
            'view' => $viewPath,
            'data' => $viewData
        ]);

        return $this;
    }

    /**
     * Add a provider-based tab content
     */
    public function addProviderTab(string $key, string $providerClass, array $tabConfig = [], array $providerConfig = []): self
    {
        $this->addTab($key, $tabConfig);
        $this->addTabContent($key, [
            'provider' => $providerClass,
            'config' => $providerConfig
        ]);

        return $this;
    }

    /**
     * Add a callback-based tab content
     */
    public function addCallbackTab(string $key, callable $callback, array $tabConfig = []): self
    {
        $this->addTab($key, $tabConfig);
        $this->addTabContent($key, [
            'callback' => $callback
        ]);

        return $this;
    }

    /**
     * Add a table tab with Filament table configuration
     */
    public function addTableTab(string $key, array $tableConfig, array $tabConfig = []): self
    {
        $this->addTab($key, $tabConfig);
        $this->addTabContent($key, [
            'type' => 'table',
            'table' => $tableConfig
        ]);

        return $this;
    }

    /**
     * Add a form tab with Filament form configuration
     */
    public function addFormTab(string $key, array $formConfig, array $tabConfig = []): self
    {
        $this->addTab($key, $tabConfig);
        $this->addTabContent($key, [
            'type' => 'form',
            'form' => $formConfig
        ]);

        return $this;
    }

    /**
     * Add an infolist tab with Filament infolist configuration
     */
    public function addInfolistTab(string $key, array $infolistConfig, array $tabConfig = []): self
    {
        $this->addTab($key, $tabConfig);
        $this->addTabContent($key, [
            'type' => 'infolist',
            'infolist' => $infolistConfig
        ]);

        return $this;
    }

    /**
     * Get the complete configuration array
     */
    public function toArray(): array
    {
        return [
            'tabs' => $this->tabs,
            'tabContents' => $this->tabContents
        ];
    }
    /**
     * Pre-built configuration for Lead resource
     */
    public static function forLead(): self
    {
        return static::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Lead information and details'
            ])
            ->addTableTab('proposals', [
                'relationship' => 'proposal',
                'columns' => [
                    'subject',
                    'status',
                    'date',
                    'actions'
                ]
            ], [
                'label' => 'Proposals',
                'icon' => 'heroicon-o-document-text',
                'description' => 'Proposals for this lead',
                'actions' => [
                    [
                        'label' => 'View all',
                        'url' => '/secret/proposals',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'gray'
                    ],
                    [
                        'label' => 'Create Proposal',
                        'url_template' => '/secret/proposals/create?lead_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('invoices', [
                'relationship' => 'invoices',
                'columns' => [
                    'invoice_number',
                    'status',
                    'invoice_date',
                    'invoice_due_date',
                    'total',
                    'actions'
                ]
            ], [
                'label' => 'Invoices',
                'icon' => 'heroicon-o-banknotes',
                'description' => 'Invoices for this customer',
                'actions' => [
                    [
                        'label' => 'View all invoices',
                        'url' => '/secret/invoices',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Invoice',
                        'url_template' => '/secret/invoices/create?lead_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('tasks', [
                'relationship' => 'tasks',
                'columns' => [
                    'title',
                    'status',
                    'prioritas',
                    'start_date',
                    'end_date',
                    'actions'
                ]
            ], [
                'label' => 'Tasks',
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => 'Tasks for this lead',
                'actions' => [
                    [
                        'label' => 'View all tasks',
                        'url' => '/secret/tasks',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Task',
                        'url_template' => '/secret/tasks/create?lead_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('work_orders', [
                'relationship' => 'workOrders',
                'columns' => [
                    'assigned.name',
                    'work_date',
                    'work_time',
                    'status',
                    'total',
                    'actions'
                ]
            ], [
                'label' => 'Work Orders',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description' => 'Work orders for this lead',
                'actions' => [
                    [
                        'label' => 'View all work orders',
                        'url' => '/secret/work-orders',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Work Order',
                        'url_template' => '/secret/work-orders/work-orders/create?lead_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Riwayat Aktivitas',
                'icon' => 'heroicon-o-clock',
                'description' => 'Riwayat aktivitas dan perubahan',
                'limit' => 50
            ]);
    }
    /**
     * Pre-built configuration for Customer resource
     */
    public static function forCustomer(): self
    {
        return static::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Customer information and details'
            ])
            ->addTableTab('leads', [
                'relationship' => 'lead',
                'columns' => [
                    'name',
                    'status.name',
                    'source.name',
                    'assigned.name',
                    'date_contacted',
                    'lead_value',
                ]
            ], [
                'label' => 'Leads',
                'icon' => 'heroicon-o-user-group',
                'description' => 'Lead information for this customer'
            ])
            ->addTableTab('proposals', [
                'relationship' => 'allProposals',
                'columns' => [
                    'subject',
                    'status',
                    'date',
                    'related',
                    'actions'
                ]
            ], [
                'label' => 'Proposals',
                'icon' => 'heroicon-o-document-text',
                'description' => 'Proposals for this customer',
                'actions' => [
                    [
                        'label' => 'View all proposals',
                        'url' => '/secret/proposals',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'primary'
                    ],
                    [
                        'label' => 'Create Proposal',
                        'url_template' => '/secret/proposals/create?customer_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('invoices', [
                'relationship' => 'allInvoices',
                'columns' => [
                    'invoice_number',
                    'status',
                    'is_quotation',
                    'invoice_date',
                    'invoice_due_date',
                    'total',
                    'actions'
                ]
            ], [
                'label' => 'Invoices',
                'icon' => 'heroicon-o-banknotes',
                'description' => 'Invoices for this customer',
                'actions' => [
                    [
                        'label' => 'View all invoices',
                        'url' => '/secret/invoices',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Invoice',
                        'url_template' => '/secret/invoices/create?customer_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('contracts', [
                'relationship' => 'contracts',
                'columns' => [
                    'subject',
                    'contractType.name',
                    'contract_value',
                    'total_workmanship',
                    'start_date',
                    'end_date',
                    'actions'
                ]
            ], [
                'label' => 'Contracts',
                'icon' => 'heroicon-o-document-text',
                'description' => 'Contract history',
                'actions' => [
                    [
                        'label' => 'Add Contract',
                        'url_template' => '/secret/contracts/create?customer_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('tasks', [
                'relationship' => 'tasks',
                'columns' => [
                    'title',
                    'status',
                    'prioritas',
                    'start_date',
                    'end_date',
                    'actions'
                ]
            ], [
                'label' => 'Tasks',
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => 'Tasks for this customer',
                'actions' => [
                    [
                        'label' => 'View all tasks',
                        'url' => '/secret/tasks',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Task',
                        'url_template' => '/secret/tasks/create?customer_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addTableTab('work_orders', [
                'relationship' => 'workOrders',
                'columns' => [
                    'assigned.name',
                    'work_date',
                    'work_time',
                    'status',
                    'total',
                    'actions'
                ]
            ], [
                'label' => 'Work Orders',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description' => 'Work orders for this customer',
                'actions' => [
                    [
                        'label' => 'View all work orders',
                        'url' => '/secret/work-orders',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Work Order',
                        'url_template' => '/secret/work-orders/work-orders/create?customer_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Logs',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this customer'
            ]);
    }
    public static function forProposal(): self
    {
        return static::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Customer information and details'
            ])
            ->addTableTab('tasks', [
                'relationship' => 'tasks',
                'columns' => [
                    'title',
                    'status',
                    'prioritas',
                    'start_date',
                    'end_date',
                    'actions'
                ]
            ], [
                'label' => 'Tasks',
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => 'Tasks for this lead',
                'actions' => [
                    [
                        'label' => 'View all tasks',
                        'url' => '/secret/tasks',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Task',
                        'url_template' => '/secret/tasks/create?lead_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this proposal'
            ])
            ->addProviderTab('comments', \App\Livewire\Components\Providers\CommentsProvider::class, [
                'label' => 'Comments',
                'icon' => 'heroicon-o-chat-bubble-left-ellipsis',
                'description' => 'Discussion and notes for this proposal',
                'visible' => fn($record) => $record && (property_exists($record, 'allow_comments') ? $record->allow_comments : (method_exists($record, 'getAttribute') ? $record->getAttribute('allow_comments') : false)),
            ]);
    }
    public static function forProposalCustomer(): self
    {
        return static::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Customer information and details'
            ])
            ->addTableTab('tasks', [
                'relationship' => 'tasks',
                'columns' => [
                    'title',
                    'status',
                    'prioritas',
                    'start_date',
                    'end_date',
                    'actions'
                ]
            ], [
                'label' => 'Tasks',
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => 'Tasks for this customer',
                'actions' => [
                    [
                        'label' => 'View all tasks',
                        'url' => '/secret/tasks',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                    [
                        'label' => 'Create Task',
                        'url_template' => '/secret/tasks/create?customer_id={record.id}',
                        'icon' => 'heroicon-o-plus',
                        'color' => 'primary'
                    ]
                ]
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this proposal'
            ])
            ->addProviderTab('comments', \App\Livewire\Components\Providers\CommentsProvider::class, [
                'label' => 'Comments',
                'icon' => 'heroicon-o-chat-bubble-left-ellipsis',
                'description' => 'Discussion and notes for this proposal',
                'visible' => fn($record) => $record && (property_exists($record, 'allow_comments') ? $record->allow_comments : (method_exists($record, 'getAttribute') ? $record->getAttribute('allow_comments') : false)),
            ]);
    }
    public static function forWO(): self
    {
        return static::make()
            ->addInfolistTab('detail', [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Work Order information and details'
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this Work Order'
            ]);
    }

    public static function forServiceReport(): self
    {
        return static::make()
            ->addInfolistTab('detail', [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Work Order information and details'
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this Work Order'
            ]);
    }

    public static function forTask(): self
    {
        return static::make()
            ->addInfolistTab('detail', [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Task information and details'
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this Task'
            ]);
    }

    public static function forEmployee(): self
    {
        return static::make()
            ->addInfolistTab('detail', [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Employee information and details'
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this Employee'
            ]);
    }

    public static function forInvoice(): self
    {
        return static::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Invoice information and details'
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this invoice'
            ]);
    }

    public static function forItem(): self
    {
        return static::make()
            ->addInfolistTab('detail', [], [
                'label' => 'Detail',
                'icon' => 'heroicon-o-information-circle',
                'description' => 'Item information and details'
            ])
            ->addTableTab('stockMovement', [
                'relationship' => 'stockMovement',
                'columns' => [
                    'quantity',
                    'transaction_type',
                    'reference',
                    'created_at',
                ]
            ], [
                'label' => 'Stock Movement',
                'icon' => 'heroicon-o-clipboard-document-list',
                'description' => 'Stock Movement for this Item',
                'actions' => [
                    [
                        'label' => 'View all stock movements',
                        'url' => '/secret/stock-movements',
                        'icon' => 'heroicon-o-eye',
                        'color' => 'slate'
                    ],
                ]
            ])
            ->addProviderTab('activity_logs', \App\Livewire\Components\Providers\ActivityLogProvider::class, [
                'label' => 'Activity Log',
                'icon' => 'heroicon-o-clock',
                'description' => 'Activity history for this invoice'
            ]);
    }
}
