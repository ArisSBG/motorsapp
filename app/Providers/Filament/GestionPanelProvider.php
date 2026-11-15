<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\Profil;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Espace « Gestion » : stock, achats, ventes, paiements, bénéfice.
 */
class GestionPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('gestion')
            ->path('gestion')
            ->brandName('BURKINA MOTORS — Gestion')
            ->login(Login::class)
            ->profile(Profil::class, isSimple: false)
            // Double vérification par application (Google Authenticator…), activable depuis le profil.
            // Obligatoire pour l'administrateur en production (cahier des charges, 6.1).
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName('BURKINA MOTORS')],
                isRequired: fn (): bool => app()->isProduction() && (bool) auth()->user()?->estAdmin(),
            )
            ->colors([
                'primary' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationItems([
                NavigationItem::make('Espace Papiers')
                    ->url('/papiers')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->group('Autres espaces')
                    ->sort(99)
                    ->visible(fn (): bool => (bool) auth()->user()?->panneaux()->contains('papiers')),
            ])
            ->discoverResources(in: app_path('Filament/Gestion/Resources'), for: 'App\Filament\Gestion\Resources')
            // Le tableau de bord (App\Filament\Gestion\Pages\TableauDeBord) est trouvé ici
            ->discoverPages(in: app_path('Filament/Gestion/Pages'), for: 'App\Filament\Gestion\Pages')
            ->discoverWidgets(in: app_path('Filament/Gestion/Widgets'), for: 'App\Filament\Gestion\Widgets')
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
