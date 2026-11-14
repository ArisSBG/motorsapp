<?php

namespace App\Filament\Papiers\Resources\Immatriculations\Tables;

use App\Filament\Papiers\Resources\Immatriculations\ActionsPapiers;
use App\Models\Immatriculation;
use App\Support\Montant;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImmatriculationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('moto.chassis')
                    ->label('Châssis')
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->description(fn (Immatriculation $record) => "{$record->moto->marque} {$record->moto->modele}"),
                TextColumn::make('vente.client.nom')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Immatriculation $record) => $record->vente?->client?->telephone),
                TextColumn::make('vente.date_vente')->label('Vendue le')->date('d/m/Y')->sortable(),
                TextColumn::make('statut')->badge(),
                TextColumn::make('date_provisoire')
                    ->label('Provisoire')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->description(fn (Immatriculation $record) => $record->provisoireExpireLe()
                        ? ($record->provisoireExpireLe()->isPast() ? 'expiré' : 'expire le '.$record->provisoireExpireLe()->format('d/m'))
                        : null)
                    ->toggleable(),
                TextColumn::make('moto.plaque')->label('Plaque')->searchable()->placeholder('—'),
                IconColumn::make('client_prevenu_le')
                    ->label('Prévenu')
                    ->boolean()
                    ->state(fn (Immatriculation $record) => $record->client_prevenu_le !== null)
                    ->tooltip(fn (Immatriculation $record) => $record->client_prevenu_le?->format('d/m/Y H:i')),
                IconColumn::make('remis')
                    ->label('Récupéré')
                    ->boolean()
                    ->state(fn (Immatriculation $record) => $record->carteGriseRemise()),
                TextColumn::make('reste_du')
                    ->label('Doit encore')
                    ->state(fn (Immatriculation $record) => $record->vente?->reste() ?? 0)
                    ->formatStateUsing(fn ($state) => $state > 0 ? Montant::format($state) : '—')
                    ->color(fn (Immatriculation $record) => ($record->vente && ! $record->vente->peutRemettrePapiers()) ? 'danger' : 'gray')
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionsPapiers::provisoire(),
                    ActionsPapiers::recepisse(),
                    ActionsPapiers::carteGrise(),
                    ActionsPapiers::prevenir(),
                    ActionsPapiers::retrait(),
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ]);
    }
}
