<?php

namespace App\Filament\Gestion\Resources\Motos\Schemas;

use App\Filament\Gestion\Resources\Motos\Tables\MotosTable;
use App\Models\Moto;
use App\Support\Montant;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MotoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identification')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('chassis')->label('Châssis')->copyable()->fontFamily('mono')->weight('bold'),
                        TextEntry::make('marque'),
                        TextEntry::make('modele')->label('Modèle'),
                        TextEntry::make('couleur')->placeholder('—'),
                        TextEntry::make('cylindree')->label('Cylindrée')->suffix(' cm³')->placeholder('—'),
                        TextEntry::make('numero_moteur')->label('N° de moteur')->placeholder('—'),
                        TextEntry::make('statut')->badge(),
                        TextEntry::make('cmc')->label('CMC')->placeholder('—'),
                        TextEntry::make('plaque')->placeholder('—'),
                    ]),
                Section::make('Achat')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('fournisseur.nom')->label('Fournisseur'),
                        TextEntry::make('facture.numero')->label('Facture')->placeholder('—'),
                        TextEntry::make('date_reception')->label('Reçue le')->date('d/m/Y'),
                        TextEntry::make('prix_achat')
                            ->label('Prix d’achat')
                            ->formatStateUsing(fn ($state) => Montant::format($state))
                            ->visible(fn () => MotosTable::peutVoirPrix()),
                        TextEntry::make('total_frais')
                            ->label('Frais (transport, réparations…)')
                            ->state(fn (Moto $record) => $record->totalFrais())
                            ->formatStateUsing(fn ($state) => Montant::format($state))
                            ->visible(fn () => MotosTable::peutVoirPrix()),
                        TextEntry::make('cout_revient')
                            ->label('Coût de revient')
                            ->state(fn (Moto $record) => $record->coutDeRevient())
                            ->formatStateUsing(fn ($state) => Montant::format($state))
                            ->weight('bold')
                            ->visible(fn () => MotosTable::peutVoirPrix()),
                    ]),
                TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
            ]);
    }
}
