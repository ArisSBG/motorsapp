<?php

namespace App\Filament\Gestion\Resources\Clients\Tables;

use App\Enums\TypeClient;
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

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nom')
            ->columns([
                TextColumn::make('nom')->searchable()->sortable()->weight('bold'),
                TextColumn::make('telephone')->label('Téléphone')->searchable(),
                TextColumn::make('adresse')->label('Quartier')->toggleable(),
                TextColumn::make('type')->badge()->toggleable(),
                TextColumn::make('numero_piece')->label('N° de pièce')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reste_du')
                    ->label('Doit encore')
                    ->formatStateUsing(fn ($state) => Montant::format($state))
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
            ])
            ->filters([
                Filter::make('debiteurs')
                    ->label('Clients qui doivent encore de l’argent')
                    ->query(fn (Builder $query) => $query->having('reste_du', '>', 0)),
                SelectFilter::make('type')->options(TypeClient::class),
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
