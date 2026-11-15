<?php

namespace App\Filament\Gestion\Widgets;

use App\Services\Statistiques;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Bénéfice par mois sur l'année choisie (ventes soldées), comparé à l'année précédente. */
class GraphiqueBeneficeParMois extends ChartWidget
{
    use InteractsWithPageFilters;

    // Chargé tout de suite (pas d’affichage « Chargement… »)
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

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
        return 'Bénéfice par mois — '.$this->annee();
    }

    protected function getData(): array
    {
        $stats = app(Statistiques::class);
        $courante = $stats->parMois($this->annee());
        $precedente = $stats->parMois($this->annee() - 1);

        return [
            'datasets' => [
                [
                    'label' => (string) $this->annee(),
                    'data' => $courante->pluck('benefice')->all(),
                    'backgroundColor' => '#2563eb',
                    'borderRadius' => 4,
                ],
                [
                    'label' => (string) ($this->annee() - 1),
                    'data' => $precedente->pluck('benefice')->all(),
                    'backgroundColor' => '#cbd5e1',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => GraphiqueVentesParMois::libellesMois(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|RawJs|null
    {
        return GraphiqueVentesParMois::optionsFcfa();
    }
}
