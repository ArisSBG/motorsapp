<?php

namespace App\Filament\Gestion\Widgets;

use App\Services\Statistiques;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Motos vendues par mois sur l'année choisie, avec l'objectif mensuel s'il est fixé. */
class GraphiqueVentesParMois extends ChartWidget
{
    use InteractsWithPageFilters;

    // Chargé tout de suite (pas d’affichage « Chargement… »)
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('voir_tableau_de_bord');
    }

    private function annee(): int
    {
        return (int) ($this->pageFilters['annee'] ?? now()->year);
    }

    public function getHeading(): ?string
    {
        return 'Motos vendues par mois — '.$this->annee();
    }

    public static function libellesMois(): array
    {
        return ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];
    }

    /** Axe des montants au format 975.000 FCFA. */
    public static function optionsFcfa(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                scales: { y: { ticks: { callback: (v) => new Intl.NumberFormat('de-DE').format(v) + ' F' } } },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ' : ' + new Intl.NumberFormat('de-DE').format(c.parsed.y) + ' FCFA' } } },
            }
        JS);
    }

    protected function getData(): array
    {
        $annee = $this->annee();
        $donnees = app(Statistiques::class)->parMois($annee);
        $objectifs = \App\Models\ObjectifVente::where('annee', $annee)->pluck('nombre_motos', 'mois');

        $datasets = [[
            'type' => 'bar',
            'label' => 'Motos vendues',
            'data' => $donnees->pluck('motos_vendues')->all(),
            'backgroundColor' => '#16a34a',
            'borderRadius' => 4,
        ]];

        if ($objectifs->isNotEmpty()) {
            $datasets[] = [
                'type' => 'line',
                'label' => 'Objectif',
                'data' => collect(range(1, 12))->map(fn ($m) => $objectifs->get($m))->all(),
                'borderColor' => '#f59e0b',
                'borderDash' => [6, 4],
                'pointRadius' => 3,
                'spanGaps' => true,
            ];
        }

        return ['datasets' => $datasets, 'labels' => self::libellesMois()];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|RawJs|null
    {
        return ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
