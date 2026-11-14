<?php

namespace App\Filament\Gestion\Resources\Motos\Tables;

use App\Enums\StatutMoto;
use App\Models\Moto;
use App\Support\Montant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MotosTable
{
    public static function peutVoirPrix(): bool
    {
        return (bool) auth()->user()?->can('voir_prix_achat');
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date_reception', 'desc')
            ->columns([
                TextColumn::make('chassis')
                    ->label('Châssis')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),
                TextColumn::make('marque')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('modele')
                    ->label('Modèle')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('couleur')
                    ->toggleable(),
                TextColumn::make('fournisseur.nom')
                    ->label('Fournisseur')
                    ->toggleable(),
                TextColumn::make('facture.numero')
                    ->label('Facture')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('prix_achat')
                    ->label('Prix d’achat')
                    ->formatStateUsing(fn ($state) => Montant::format($state))
                    ->alignEnd()
                    ->sortable()
                    ->visible(fn () => self::peutVoirPrix())
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Montant::format($state))),
                TextColumn::make('cout_revient')
                    ->label('Coût de revient')
                    ->state(fn (Moto $record) => $record->coutDeRevient())
                    ->formatStateUsing(fn ($state) => Montant::format($state))
                    ->alignEnd()
                    ->tooltip('Prix d’achat + transport + réparations')
                    ->visible(fn () => self::peutVoirPrix())
                    ->toggleable(),
                TextColumn::make('date_reception')
                    ->label('Reçue le')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('cmc')
                    ->label('CMC')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plaque')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->options(StatutMoto::class),
                SelectFilter::make('marque')
                    ->options(fn () => Moto::query()->distinct()->orderBy('marque')->pluck('marque', 'marque')->all()),
                SelectFilter::make('fournisseur')
                    ->relationship('fournisseur', 'nom')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()->label('Corbeille'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
