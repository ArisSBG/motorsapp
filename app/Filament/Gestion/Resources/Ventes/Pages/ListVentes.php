<?php

namespace App\Filament\Gestion\Resources\Ventes\Pages;

use App\Enums\StatutVente;
use App\Filament\Gestion\Resources\Ventes\VenteResource;
use App\Models\Vente;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVentes extends ListRecords
{
    protected static string $resource = VenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle vente'),
        ];
    }

    public function getTabs(): array
    {
        $onglet = fn (string $libelle, StatutVente $statut) => Tab::make($libelle)
            ->badge(fn () => Vente::where('statut', $statut)->count() ?: null)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('ventes.statut', $statut));

        return [
            'toutes' => Tab::make('Toutes')
                ->modifyQueryUsing(fn (Builder $query) => $query->actives()),
            'reste' => $onglet('Reste à payer', StatutVente::EnCours),
            'reservations' => $onglet('Réservations', StatutVente::Reservation),
            'soldees' => Tab::make('Soldées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ventes.statut', StatutVente::Soldee)),
            'annulees' => Tab::make('Annulées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ventes.statut', StatutVente::Annulee)),
        ];
    }
}
