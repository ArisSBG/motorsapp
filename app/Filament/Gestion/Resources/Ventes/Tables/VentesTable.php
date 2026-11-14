<?php

namespace App\Filament\Gestion\Resources\Ventes\Tables;

use App\Filament\Gestion\Resources\Ventes\VenteResource;
use App\Models\User;
use App\Models\Vente;
use App\Support\Montant;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VentesTable
{
    public static function configure(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->defaultSort('date_vente', 'desc')
            ->columns([
                TextColumn::make('numero')->label('N° facture')->searchable()->weight('bold'),
                TextColumn::make('date_vente')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('client.nom')->label('Client')->searchable(),
                TextColumn::make('motos')
                    ->label('Motos')
                    ->state(fn (Vente $record) => $record->lignes->map(fn ($l) => "{$l->moto->marque} {$l->moto->modele}")->join(', '))
                    ->description(fn (Vente $record) => $record->lignes->pluck('moto.chassis')->join(', '))
                    ->wrap(),
                TextColumn::make('total')->formatStateUsing($fcfa)->alignEnd(),
                TextColumn::make('reste')
                    ->label('Reste à payer')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('benefice')
                    ->label('Bénéfice')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->visible(fn () => VenteResource::peutVoirBenefice())
                    ->toggleable(),
                TextColumn::make('statut')->badge(),
                TextColumn::make('vendeur.name')->label('Vendeur')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('periode')
                    ->schema([
                        DatePicker::make('du')->label('Du')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('au')->label('Au')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['du'] ?? null, fn ($q, $d) => $q->whereDate('date_vente', '>=', $d))
                        ->when($data['au'] ?? null, fn ($q, $d) => $q->whereDate('date_vente', '<=', $d))),
                SelectFilter::make('user_id')
                    ->label('Vendeur')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all()),
                TrashedFilter::make()->label('Corbeille'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('facture')
                    ->label('Facture')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Vente $record) => route('documents.facture-vente', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
