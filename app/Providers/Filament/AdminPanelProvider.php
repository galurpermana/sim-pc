<?php

namespace App\Providers\Filament;

use Filament\Contracts\Plugin;
use Filament\Http\Middleware\Authenticate;
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
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Filament\Resources\UserResource;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            
            // ->brandLogo(asset('images/logo.png'))
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => '#ff5722',
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
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
            ->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        NavigationItem::make('Dashboard') // Standalone item
                            ->url(route('filament.admin.pages.dashboard'))
                            ->icon('heroicon-o-home')
                            ->isActiveWhen(fn () => request()->routeIs('filament.admin.pages.dashboard')),
                        NavigationItem::make('Products')
                            ->url(route('filament.admin.resources.products.index'))
                            ->icon('heroicon-o-archive-box')
                            ->hidden(fn () => !auth()->user()->can('can view products'))
                            ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.products.index', 'filament.admin.resources.products.edit', 'filament.admin.resources.products.create', 'filament.admin.resources.products.view'))
                    ])
                    ->groups([
                        NavigationGroup::make('Transactions')
                            ->items([
                                NavigationItem::make('Transaction Logs')
                                    ->url(route('filament.admin.resources.transactions.index'))
                                    ->icon('heroicon-o-shopping-cart')
                                    ->isActiveWhen(fn()=> request()->routeIs('filament.admin.resources.transactions.index', 'filament.admin.resources.transactions.edit', 'filament.admin.resources.transactions.view')),
                                NavigationItem::make('Cashiers')
                                    ->url(route('filament.admin.resources.transactions.create'))
                                    ->icon('heroicon-o-banknotes')
                                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.transactions.create')),
                            ])
                    ])
                    ->groups([ // Grouped items
                        NavigationGroup::make('Users Settings')
                            ->items([
                                NavigationItem::make('User')
                                    ->url(route('filament.admin.resources.users.index')) // Update with actual route
                                    ->icon('heroicon-o-shield-check')
                                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.users.index', 'filament.admin.resources.users.edit', 'filament.admin.resources.users.create', 'filament.admin.resources.users.view'))
                                    ->hidden(fn () => !auth()->user()->can('view users settings')),

                                NavigationItem::make('Roles')
                                    ->url(route('filament.admin.resources.roles.index')) // Update with actual route
                                    ->icon('heroicon-o-shield-check')
                                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.roles.index', 'filament.admin.resources.roles.edit', 'filament.admin.resources.roles.create', 'filament.admin.resources.roles.view'))
                                    ->hidden(fn () => !auth()->user()->can('view users settings')),
                            
                                NavigationItem::make('Permissions')
                                    ->url(route('filament.admin.resources.permissions.index')) // Update with actual route
                                    ->icon('heroicon-o-lock-closed')
                                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.permissions.index', 'filament.admin.resources.permissions.edit', 'filament.admin.resources.permissions.create', 'filament.admin.resources.permissions.view'))
                                    ->hidden(fn () => !auth()->user()->can('view users settings')),
                                        ])
                    ]);     
            });     
    }
}
