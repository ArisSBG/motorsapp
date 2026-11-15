<?php

namespace App\Filament\Gestion\Pages;

use App\Http\Controllers\RapportController;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Rapports à imprimer ou enregistrer en PDF (questionnaire 12.4 et 12.5).
 */
class Rapports extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Rapports';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $title = 'Rapports';

    protected string $view = 'filament.gestion.pages.rapports';

    public string $du = '';

    public string $au = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('exporter') || $user?->can('voir_stock'));
    }

    public function mount(): void
    {
        $this->du = now()->startOfMonth()->toDateString();
        $this->au = now()->toDateString();
    }

    /** @return list<array{cle: string, titre: string, description: string, periode: bool, url: string}> */
    public function rapports(): array
    {
        $descriptions = [
            'benefices' => ['Ventes soldées sur la période, avec le bénéfice de chacune.', true],
            'ventes' => ['Toutes les ventes de la période, payées ou non.', true],
            'clients' => ['Clients qui doivent encore de l’argent, facture par facture.', false],
            'fournisseurs' => ['Ce qui reste à payer à chaque fournisseur.', false],
            'stock' => ['Motos disponibles aujourd’hui, regroupées par modèle.', false],
            'sans-papiers' => ['Motos vendues qui n’ont pas encore leur carte grise.', false],
        ];

        return collect(RapportController::RAPPORTS)
            ->filter(fn ($titre, string $cle) => auth()->user()->can($cle === 'stock' ? 'voir_stock' : 'exporter'))
            ->map(fn (string $titre, string $cle) => [
                'cle' => $cle,
                'titre' => $titre,
                'description' => $descriptions[$cle][0],
                'periode' => $descriptions[$cle][1],
                'url' => route('documents.rapport', array_filter([
                    'rapport' => $cle,
                    'du' => $descriptions[$cle][1] ? $this->du : null,
                    'au' => $descriptions[$cle][1] ? $this->au : null,
                ])),
            ])
            ->values()
            ->all();
    }
}
