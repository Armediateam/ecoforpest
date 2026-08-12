<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\WorkOrders;
use App\Filament\Pages\LiveLocationTrackingPage;
use App\Filament\Resources\AccessRequestResource;
use App\Filament\Resources\ContractResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\LeaveResource;
use App\Filament\Resources\OvertimeResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PayrollResource;
use App\Filament\Resources\PermitResource;
use App\Filament\Resources\ProposalCustomerResource;
use App\Filament\Resources\ProposalResource;
use App\Filament\Resources\ServiceReportResource;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\StockMovementResource;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Resources\SurveyFormResource;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\FinanceTransactionResource;
use App\Filament\Resources\FinanceCategoryResource;
use App\Filament\Resources\CashAdvanceResource;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\LeaderReportResource;
use App\Filament\Resources\SchedulePlanningResource;
use App\Filament\Resources\SettingResource;

class SecretPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('secret')
            ->path('secret')
            ->brandLogo(asset('logo_horizontal.png'))
            ->brandLogoHeight('60px')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
                'blue' => Color::Blue,
                'violet' => Color::Violet,
                'teal' => Color::Teal,
                'sky' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->databaseNotifications(true)
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::make(),
                \AchyutN\FilamentLogViewer\FilamentLogViewer::make()
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        NavigationItem::make('Dashboard')
                            ->icon('heroicon-o-home')
                            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.pages.dashboard'))
                            ->url(fn(): string => Dashboard::getUrl()),
                        NavigationItem::make('Activity Log')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->url(route('filament.secret.resources.activity-logs.index'))
                            ->visible(fn() => auth()->user()->hasRole('super_admin')),
                        NavigationItem::make('System Log')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->url(route('filament.secret.pages..logs'))
                            ->visible(fn() => auth()->user()->hasRole('super_admin')),
                        ...array_map(fn($item) => $item->visible(fn() => AccessRequestResource::canViewAny()), AccessRequestResource::getNavigationItems()),
                        ...array_map(fn($item) => $item->visible(fn() => SettingResource::canViewAny()), Settings::getNavigationItems()),
                        ...array_map(fn($item) => $item->visible(fn() => WorkOrderResource::canViewAny()), WorkOrders::getNavigationItems()),
                        ...array_map(fn($item) => $item->visible(fn() => LeadResource::canViewAny()), LeadResource::getNavigationItems()),
                        ...array_map(fn($item) => $item->visible(fn() => CustomerResource::canViewAny()), CustomerResource::getNavigationItems()),
                    ])
                    ->groups([
                        NavigationGroup::make('Finance Management')
                            ->icon('heroicon-o-banknotes')
                            ->items([
                                ...array_map(fn($item) => $item->visible(fn() => InvoiceResource::canViewAny()), InvoiceResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => PaymentResource::canViewAny()), PaymentResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => PayrollResource::canViewAny()), PayrollResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => FinanceTransactionResource::canViewAny()), FinanceTransactionResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => FinanceCategoryResource::canViewAny()), FinanceCategoryResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => CashAdvanceResource::canViewAny()), CashAdvanceResource::getNavigationItems())
                            ])
                            ->collapsed(),
                        NavigationGroup::make('Human Resources')
                            ->icon('heroicon-o-user-group')
                            ->items([
                                ...array_map(fn($item) => $item->visible(fn() => LiveLocationTrackingPage::canAccess()), LiveLocationTrackingPage::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => EmployeeResource::canViewAny()), EmployeeResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => AttendanceResource::canViewAny()), AttendanceResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => LeaveResource::canViewAny()), LeaveResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => OvertimeResource::canViewAny()), OvertimeResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => PermitResource::canViewAny()), PermitResource::getNavigationItems()),
                            ])
                            ->collapsed(),
                        NavigationGroup::make('Inventory Management')
                            ->icon('heroicon-o-bookmark-square')
                            ->items([
                                ...array_map(fn($item) => $item->visible(fn() => StockMovementResource::canViewAny()), StockMovementResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => ItemResource::canViewAny()), ItemResource::getNavigationItems())
                            ])
                            ->collapsed(),
                        NavigationGroup::make('Task Management')
                            ->icon('heroicon-o-list-bullet')
                            ->items([
                                ...array_map(fn($item) => $item->visible(fn() => TaskResource::canViewAny()), TaskResource::getNavigationItems()),
                            ])
                            ->collapsed(),
                        NavigationGroup::make('Leader Reports')
                            ->icon('heroicon-o-user-plus')
                            ->items([
                                ...array_map(fn($item) => $item->visible(fn() => LeaderReportResource::canViewAny()), LeaderReportResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => SchedulePlanningResource::canViewAny()), SchedulePlanningResource::getNavigationItems()),
                            ])
                            ->collapsed(),
                        NavigationGroup::make('User Managements')
                            ->icon('heroicon-o-user-plus')
                            ->items([
                                ...array_map(fn($item) => $item->visible(fn() => UserResource::canViewAny()), UserResource::getNavigationItems()),
                                ...array_map(fn($item) => $item->visible(fn() => RoleResource::canViewAny()), RoleResource::getNavigationItems()),
                            ])
                            ->collapsed(),
                    ]);
            })->sidebarCollapsibleOnDesktop()->viteTheme('resources/css/filament/secret/theme.css', 'build/filament');
    }
}
