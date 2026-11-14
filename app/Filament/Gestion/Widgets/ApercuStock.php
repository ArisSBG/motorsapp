<?php

namespace App\Filament\Gestion\Widgets;

use App\Filament\Gestion\Resources\Fournisseurs\FournisseurResource;
use App\Filament\Gestion\Resources\Motos\MotoResource;
use App\Models\Fournisseur;
use App\Models\Moto;
use App\Support\Montant;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * En un coup d'œil : motos en stock, valeur du stock et total dû aux fournisseurs.
 * Les montants ne sont visibles que par l'administrateur.
 */
class ApercuStock extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $stats = [
            Stat::make('Motos en stock', Moto::disponibles()->count())
                ->icon(Heroicon::OutlinedCube)
                ->url(MotoResource::getUrl('index')),
        ];

        if (auth()->user()?->can('voir_prix_achat')) {
            $stats[] = Stat::make('Valeur du stock (prix d’achat)', Montant::format(Moto::disponibles()->sum('prix_achat')))
                ->icon(Heroicon::OutlinedBanknotes);
            $stats[] = Stat::make('Total dû aux fournisseurs', Montant::format(Fournisseur::totalDu()))
                ->icon(Heroicon::OutlinedTruck)
                ->color('danger')
                ->url(FournisseurResource::getUrl('index'));
        }

        return $stats;
    }
}
