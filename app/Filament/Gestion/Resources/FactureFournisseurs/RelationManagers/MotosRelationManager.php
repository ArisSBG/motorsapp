<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\RelationManagers;

use App\Filament\Gestion\Resources\Motos\MotoResource;
use App\Filament\Gestion\Resources\Motos\Schemas\MotoForm;
use App\Models\Moto;
use App\Support\Montant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Motos de la facture. « Créer et ajouter une autre » garde la marque, le modèle,
 * le prix et la couleur : seule la saisie du châssis change d'une moto à l'autre.
 */
class MotosRelationManager extends RelationManager
{
    protected static string $relationship = 'motos';

    protected static ?string $title = 'Motos de la facture';

    protected static ?string $modelLabel = 'moto';

    protected static ?string $pluralModelLabel = 'motos';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        ...MotoForm::identification(),
                        ...MotoForm::achat(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->recordTitleAttribute('chassis')
            ->defaultSort('id')
            ->columns([
                TextColumn::make('chassis')->label('Châssis')->fontFamily('mono')->weight('bold')->searchable(),
                TextColumn::make('marque'),
                TextColumn::make('modele')->label('Modèle'),
                TextColumn::make('couleur'),
                TextColumn::make('prix_achat')
                    ->label('Prix d’achat')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing($fcfa)),
                TextColumn::make('statut')->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une moto')
                    ->mutateDataUsing(fn (array $data) => [
                        ...$data,
                        'fournisseur_id' => $this->getOwnerRecord()->fournisseur_id,
                    ])
                    ->preserveFormDataWhenCreatingAnother(['marque', 'modele', 'couleur', 'cylindree', 'prix_achat', 'date_reception'])
                    ->after(fn () => $this->dispatch('totaux-modifies')),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Moto $record) => MotoResource::getUrl('view', ['record' => $record])),
                EditAction::make()->after(fn () => $this->dispatch('totaux-modifies')),
                DeleteAction::make()->after(fn () => $this->dispatch('totaux-modifies')),
            ]);
    }
}
