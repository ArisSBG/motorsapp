<?php

namespace App\Filament\Papiers\Resources\Immatriculations\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Historique des papiers remis : date, personne, document (ajout via « Remettre les papiers »). */
class RetraitsRelationManager extends RelationManager
{
    protected static string $relationship = 'retraits';

    protected static ?string $title = 'Papiers remis';

    protected static ?string $modelLabel = 'remise';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_retrait')->label('Date')->date('d/m/Y'),
                TextColumn::make('document')->badge(),
                TextColumn::make('retire_par')->label('Récupéré par'),
                IconColumn::make('est_le_client')->label('Le client')->boolean(),
                TextColumn::make('telephone')->label('Téléphone')->placeholder('—'),
                TextColumn::make('remisPar.name')->label('Remis par')->placeholder('—'),
                TextColumn::make('notes')->placeholder('—')->wrap(),
            ])
            ->emptyStateHeading('Aucun papier remis pour le moment')
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
