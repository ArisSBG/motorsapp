<?php

namespace App\Filament\Gestion\Resources\Clients\RelationManagers;

use App\Filament\Gestion\Resources\Ventes\VenteResource;
use App\Models\Vente;
use App\Support\Montant;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VentesRelationManager extends RelationManager
{
    protected static string $relationship = 'ventes';

    protected static ?string $title = 'Achats du client';

    public function table(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->avecTotaux()->with('lignes.moto'))
            ->defaultSort('date_vente', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Facture')->weight('bold'),
                TextColumn::make('date_vente')->label('Date')->date('d/m/Y'),
                TextColumn::make('motos')
                    ->label('Motos')
                    ->state(fn (Vente $record) => $record->lignes->map(fn ($l) => "{$l->moto->marque} {$l->moto->modele}")->join(', ')),
                TextColumn::make('total')->formatStateUsing($fcfa)->alignEnd(),
                TextColumn::make('reste')
                    ->label('Reste à payer')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('statut')->badge(),
            ])
            ->headerActions([
                Action::make('nouvelle')
                    ->label('Nouvelle vente')
                    ->url(fn () => VenteResource::getUrl('create', ['client' => $this->getOwnerRecord()->getKey()])),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Vente $record) => VenteResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
