<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Espace « Papiers » : immatriculations, retraits, archivage des documents.
 */
class PapiersPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('papiers')
            ->path('papiers')
            ->brandName('BURKINA MOTORS — Papiers')
            ->login(Login::class)
            ->profile(isSimple: false)
            // Double vérification par application (Google Authenticator…), activable depuis le profil.
            // Obligatoire pour l'administrateur en production (cahier des charges, 6.1).
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName('BURKINA MOTORS')],
                isRequired: fn (): bool => app()->isProduction() && (bool) auth()->user()?->estAdmin(),
            )
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationItems([
                NavigationItem::make('Espace Gestion')
                    ->url('/gestion')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->group('Autres espaces')
                    ->sort(99)
                    ->visible(fn (): bool => (bool) auth()->user()?->panneaux()->contains('gestion')),
            ])
            ->discoverResources(in: app_path('Filament/Papiers/Resources'), for: 'App\Filament\Papiers\Resources')
            ->discoverPages(in: app_path('Filament/Papiers/Pages'), for: 'App\Filament\Papiers\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Papiers/Widgets'), for: 'App\Filament\Papiers\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
