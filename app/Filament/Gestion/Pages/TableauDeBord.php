<?php

namespace App\Filament\Gestion\Pages;

use App\Models\ObjectifVente;
use App\Services\Statistiques;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Tableau de bord de l'espace Gestion : la situation du mois choisi en un coup d'œil.
 */
class TableauDeBord extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Tableau de bord';

    public function filtersForm(Schema $schema): Schema
    {
        // Filtres réservés à l'administrateur (les autres profils ne voient que la situation du stock)
        if (! auth()->user()->can('voir_tableau_de_bord')) {
            return $schema->components([]);
        }

        $annees = range((int) now()->year, 2025);

        return $schema->components([
            Select::make('mois')
                ->options(collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => ucfirst(Statistiques::nomMois($m))])->all())
                ->default((int) now()->month)
                ->selectablePlaceholder(false),
            Select::make('annee')
                ->label('Année')
                ->options(array_combine($annees, $annees))
                ->default((int) now()->year)
                ->selectablePlaceholder(false),
        ]);
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('objectif')
                ->label('Objectif du mois')
                ->icon(Heroicon::OutlinedFlag)
                ->visible(fn () => auth()->user()->can('voir_tableau_de_bord'))
                ->modalHeading(fn () => 'Objectif de ventes — '.Statistiques::nomMois($this->moisChoisi()).' '.$this->anneeChoisie())
                ->fillForm(fn () => ['nombre_motos' => ObjectifVente::pour($this->anneeChoisie(), $this->moisChoisi())])
                ->schema([
                    TextInput::make('nombre_motos')
                        ->label('Nombre de motos à vendre')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                ])
                ->action(function (array $data) {
                    ObjectifVente::updateOrCreate(
                        ['annee' => $this->anneeChoisie(), 'mois' => $this->moisChoisi()],
                        ['nombre_motos' => $data['nombre_motos']],
                    );
                })
                ->successNotificationTitle('Objectif enregistré'),
        ];
    }

    public function moisChoisi(): int
    {
        return (int) ($this->filters['mois'] ?? now()->month);
    }

    public function anneeChoisie(): int
    {
        return (int) ($this->filters['annee'] ?? now()->year);
    }
}
