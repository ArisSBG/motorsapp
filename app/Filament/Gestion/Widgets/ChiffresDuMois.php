<?php

namespace App\Filament\Gestion\Widgets;

use App\Services\Statistiques;
use App\Support\Montant;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Chiffres du mois choisi, comparés au mois précédent et au même mois de l'année passée.
 * Réservé à l'administrateur.
 */
class ChiffresDuMois extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    // Chargé tout de suite (pas d’affichage « Chargement… »)
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('voir_tableau_de_bord');
    }

    protected function getHeading(): ?string
    {
        return 'Chiffres de '.Statistiques::nomMois($this->mois()).' '.$this->annee();
    }

    private function mois(): int
    {
        return (int) ($this->pageFilters['mois'] ?? now()->month);
    }

    private function annee(): int
    {
        return (int) ($this->pageFilters['annee'] ?? now()->year);
    }

    protected function getStats(): array
    {
        $stats = app(Statistiques::class);
        $courant = $stats->mois($this->annee(), $this->mois());
        $precedentDate = now()->setDate($this->annee(), $this->mois(), 1)->subMonth();
        $precedent = $stats->mois($precedentDate->year, $precedentDate->month);
        $anDernier = $stats->mois($this->annee() - 1, $this->mois());

        $comparer = function (string $cle) use ($courant, $precedent, $anDernier): ?string {
            $parties = [];
            if (($e = Statistiques::evolution($courant[$cle], $precedent[$cle])) !== null) {
                $parties[] = ($e >= 0 ? '+' : '').$e.' % vs mois précédent';
            }
            if (($e = Statistiques::evolution($courant[$cle], $anDernier[$cle])) !== null) {
                $parties[] = ($e >= 0 ? '+' : '').$e.' % vs l’an dernier';
            }

            return $parties ? implode(' · ', $parties) : 'Pas de comparaison possible';
        };
        $couleur = fn (string $cle) => $courant[$cle] >= $precedent[$cle] ? 'success' : 'danger';
        $icone = fn (string $cle) => $courant[$cle] >= $precedent[$cle] ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown;

        $resultat = [
            Stat::make('Chiffre d’affaires', Montant::format($courant['chiffre_affaires']))
                ->description($comparer('chiffre_affaires'))
                ->descriptionIcon($icone('chiffre_affaires'))
                ->color($couleur('chiffre_affaires')),
            Stat::make('Bénéfice (ventes soldées)', Montant::format($courant['benefice']))
                ->description($comparer('benefice'))
                ->descriptionIcon($icone('benefice'))
                ->color($couleur('benefice')),
            Stat::make('Motos vendues', $courant['motos_vendues'])
                ->description($comparer('motos_vendues'))
                ->descriptionIcon($icone('motos_vendues'))
                ->color($couleur('motos_vendues')),
            Stat::make('Argent encaissé', Montant::format($courant['encaisse']))
                ->description('Paiements des clients reçus ce mois'),
        ];

        if ($objectif = $stats->objectif($this->annee(), $this->mois())) {
            $resultat[] = Stat::make('Objectif du mois', "{$objectif['vendues']} / {$objectif['cible']} motos")
                ->description($objectif['pourcentage'].' % atteint')
                ->icon(Heroicon::OutlinedFlag)
                ->color($objectif['pourcentage'] >= 100 ? 'success' : ($objectif['pourcentage'] >= 50 ? 'warning' : 'danger'));
        }

        return $resultat;
    }
}
