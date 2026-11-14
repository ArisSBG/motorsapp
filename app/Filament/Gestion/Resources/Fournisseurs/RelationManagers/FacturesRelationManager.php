<?php

namespace App\Filament\Gestion\Resources\Fournisseurs\RelationManagers;

use App\Filament\Gestion\Resources\FactureFournisseurs\FactureFournisseurResource;
use App\Models\FactureFournisseur;
use App\Support\Montant;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Factures du fournisseur, avec le reste à payer sur chacune.
 */
class FacturesRelationManager extends RelationManager
{
    protected static string $relationship = 'factures';

    protected static ?string $title = 'Factures';

    public function table(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('motos')->avecTotaux())
            ->defaultSort('date_facture', 'desc')
            ->columns([
                TextColumn::make('numero')->label('N° facture')->weight('bold'),
                TextColumn::make('date_facture')->label('Date')->date('d/m/Y'),
                TextColumn::make('motos_count')->label('Motos')->alignCenter(),
                TextColumn::make('total')->formatStateUsing($fcfa)->alignEnd(),
                TextColumn::make('verse')->label('Versé')->formatStateUsing($fcfa)->alignEnd(),
                TextColumn::make('solde')
                    ->label('Reste à payer')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
            ])
            ->headerActions([
                Action::make('nouvelle')
                    ->label('Nouvelle facture')
                    ->url(fn () => FactureFournisseurResource::getUrl('create')),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (FactureFournisseur $record) => FactureFournisseurResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
