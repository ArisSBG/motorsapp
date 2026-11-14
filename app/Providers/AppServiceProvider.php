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
use Illuminate\Database\Eloquent\Model;
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

        // En développement : erreur si un champ envoyé n'est pas enregistrable (évite les pertes silencieuses)
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // En production : toutes les adresses en HTTPS
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
