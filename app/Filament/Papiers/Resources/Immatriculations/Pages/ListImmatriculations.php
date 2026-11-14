<?php

namespace App\Filament\Papiers\Resources\Immatriculations\Pages;

use App\Enums\StatutImmatriculation;
use App\Filament\Papiers\Resources\Immatriculations\ImmatriculationResource;
use App\Models\Immatriculation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListImmatriculations extends ListRecords
{
    protected static string $resource = ImmatriculationResource::class;

    public function getTabs(): array
    {
        $badge = fn (Builder $q) => fn () => $q->count() ?: null;

        return [
            'a_remettre' => Tab::make('Papiers à remettre')
                ->badge($badge(Immatriculation::aRemettre()))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->aRemettre()),
            'en_cours' => Tab::make('En cours')
                ->badge($badge(Immatriculation::where('statut', '!=', StatutImmatriculation::CarteGriseRecue)))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', '!=', StatutImmatriculation::CarteGriseRecue)),
            'retard' => Tab::make('En retard')
                ->badge($badge(Immatriculation::enRetard()))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->enRetard()),
            'remis' => Tab::make('Récupérés')
                ->modifyQueryUsing(fn (Builder $query) => $query->remis()),
            'tous' => Tab::make('Tous'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'en_cours';
    }
}
