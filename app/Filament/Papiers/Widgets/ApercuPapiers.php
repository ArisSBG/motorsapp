<?php

namespace App\Filament\Papiers\Widgets;

use App\Enums\StatutImmatriculation;
use App\Filament\Papiers\Resources\Immatriculations\ImmatriculationResource;
use App\Models\Immatriculation;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Ce qu'il y a à faire aujourd'hui côté papiers. */
class ApercuPapiers extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $liste = fn (string $onglet) => ImmatriculationResource::getUrl('index', ['tab' => $onglet]);

        $aRemettre = Immatriculation::aRemettre()->count();
        $aPrevenir = Immatriculation::aRemettre()->whereNull('client_prevenu_le')->count();
        $enRetard = Immatriculation::enRetard()->count();
        $provisoiresExpirant = Immatriculation::query()
            ->where('statut', StatutImmatriculation::ProvisoireRecu)
            ->whereBetween('date_provisoire', [now()->subDays(Immatriculation::JOURS_VALIDITE_PROVISOIRE), now()->subDays(Immatriculation::JOURS_VALIDITE_PROVISOIRE - 7)])
            ->count();

        return [
            Stat::make('Papiers à remettre', $aRemettre)
                ->description('Cartes grises arrivées, pas encore récupérées')
                ->icon(Heroicon::OutlinedHandRaised)
                ->color($aRemettre ? 'success' : 'gray')
                ->url($liste('a_remettre')),
            Stat::make('Clients à prévenir', $aPrevenir)
                ->description('Carte grise arrivée, client pas encore prévenu')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color($aPrevenir ? 'warning' : 'gray')
                ->url($liste('a_remettre')),
            Stat::make('Dossiers en retard', $enRetard)
                ->description('Vendues depuis plus de '.config('entreprise.jours_alerte_papiers').' jours sans carte grise')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($enRetard ? 'danger' : 'gray')
                ->url($liste('retard')),
            Stat::make('Provisoires qui expirent', $provisoiresExpirant)
                ->description('Dans les 7 prochains jours')
                ->icon(Heroicon::OutlinedClock)
                ->color($provisoiresExpirant ? 'warning' : 'gray'),
        ];
    }
}
