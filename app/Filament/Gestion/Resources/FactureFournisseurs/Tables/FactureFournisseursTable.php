<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Tables;

use App\Support\Montant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FactureFournisseursTable
{
    public static function configure(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->defaultSort('date_facture', 'desc')
            ->columns([
                TextColumn::make('numero')
                    ->label('N° facture')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('fournisseur.nom')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date_facture')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('motos_count')
                    ->label('Motos')
                    ->alignCenter(),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing($fcfa)
                    ->alignEnd(),
                TextColumn::make('verse')
                    ->label('Versé')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('solde')
                    ->label('Reste à payer')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
            ])
            ->filters([
                Filter::make('non_soldees')
                    ->label('Non soldées')
                    ->query(fn (Builder $query) => $query->having('solde', '>', 0)),
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
