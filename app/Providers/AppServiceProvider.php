<?php

namespace App\Providers;

use App\Models\FactureFournisseur;
use App\Models\Moto;
use App\Observers\FactureFournisseurObserver;
use App\Http\Responses\LoginResponse;
use App\Models\Document;
use App\Models\Immatriculation;
use App\Models\PaiementClient;
use App\Models\Retrait;
use App\Observers\DocumentObserver;
use App\Observers\ImmatriculationObserver;
use App\Observers\RetraitObserver;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Observers\MotoObserver;
use App\Observers\PaiementClientObserver;
use App\Observers\VenteLigneObserver;
use App\Observers\VenteObserver;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContrat;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use App\Models\User;
use App\Observers\UserObserver;
use App\Services\SessionsUtilisateur;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Après connexion : chacun vers son espace
        $this->app->singleton(LoginResponseContrat::class, LoginResponse::class);
    }

    public function boot(): void
    {
        // Règles métier automatiques (répartition du transport)
        FactureFournisseur::observe(FactureFournisseurObserver::class);
        Moto::observe(MotoObserver::class);

        // Statuts des ventes et des motos (lot 2)
        Vente::observe(VenteObserver::class);
        VenteLigne::observe(VenteLigneObserver::class);
        PaiementClient::observe(PaiementClientObserver::class);

        // Papiers et documents (lot 3)
        Immatriculation::observe(ImmatriculationObserver::class);
        Retrait::observe(RetraitObserver::class);
        Document::observe(DocumentObserver::class);

        // Installation sur téléphone (PWA) dans les deux espaces
        FilamentView::registerRenderHook(PanelsRenderHook::HEAD_END, fn (): string => view('pwa.head')->render());

        // Mots de passe : 8 caractères minimum, lettres et chiffres
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Compte désactivé ou mot de passe changé par l'admin : déconnexion de tous les appareils
        User::observe(UserObserver::class);

        // Journal des connexions : réussies, échouées (identifiant tenté, jamais le mot de passe), déconnexions
        Event::listen(Login::class, fn (Login $e) => activity('connexions')
            ->causedBy($e->user)->event('connexion')
            ->withProperties(['ip' => request()->ip(), 'appareil' => SessionsUtilisateur::decrireAppareil((string) request()->userAgent())])
            ->log('Connexion'));
        Event::listen(Failed::class, fn (Failed $e) => activity('connexions')
            ->causedBy($e->user)->event('echec_connexion')
            ->withProperties(['identifiant' => $e->credentials['email'] ?? null, 'ip' => request()->ip()])
            ->log('Échec de connexion'));
        Event::listen(Logout::class, fn (Logout $e) => activity('connexions')
            ->causedBy($e->user)->event('deconnexion')
            ->log('Déconnexion'));

        // Derrière Cloudflare : adresse IP réelle du visiteur et détection du HTTPS (en-têtes X-Forwarded-*)
        if (filled($proxies = config('app.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Garde-fou : jamais de mode debug en production (il afficherait le code et la configuration)
        if ($this->app->isProduction() && config('app.debug')) {
            config(['app.debug' => false]);
            logger()->warning('APP_DEBUG=true en production : forcé à false. Corriger le fichier .env.');
        }

        // En développement : erreur si un champ envoyé n'est pas enregistrable (évite les pertes silencieuses)
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // En production : toutes les adresses en HTTPS
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
