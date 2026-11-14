<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Schemas;

use App\Models\FactureFournisseur;
use App\Support\Montant;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FactureFournisseurInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $schema
            ->components([
                Section::make('Facture')
                    ->columns(3)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('fournisseur.nom')->label('Fournisseur'),
                        TextEntry::make('numero')->label('Numéro'),
                        TextEntry::make('date_facture')->label('Date')->date('d/m/Y'),
                        TextEntry::make('nombre_motos')
                            ->label('Motos')
                            ->state(fn (FactureFournisseur $record) => $record->motos()->count()),
                        TextEntry::make('montant_transport')->label('Transport (réparti)')->formatStateUsing($fcfa),
                        TextEntry::make('remise')->formatStateUsing($fcfa),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Paiement')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('total')
                            ->label('Total dû au fournisseur')
                            ->state(fn (FactureFournisseur $record) => $record->total())
                            ->formatStateUsing($fcfa)
                            ->helperText('Prix des motos − remise'),
                        TextEntry::make('verse')
                            ->label('Déjà versé')
                            ->state(fn (FactureFournisseur $record) => $record->verse())
                            ->formatStateUsing($fcfa),
                        TextEntry::make('solde')
                            ->label('Reste à payer')
                            ->state(fn (FactureFournisseur $record) => $record->solde())
                            ->formatStateUsing($fcfa)
                            ->weight('bold')
                            ->size('lg')
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                    ]),
            ])
            ->columns(3);
    }
}
