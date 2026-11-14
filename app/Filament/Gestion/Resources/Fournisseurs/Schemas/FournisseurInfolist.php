<?php

namespace App\Filament\Gestion\Resources\Fournisseurs\Schemas;

use App\Models\Fournisseur;
use App\Support\Montant;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FournisseurInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('nom')->label('Nom ou raison sociale'),
                        TextEntry::make('telephone')->label('Téléphone')->placeholder('—'),
                        TextEntry::make('adresse')->label('Adresse ou ville')->placeholder('—'),
                        TextEntry::make('piece_identite')->label('Pièce d’identité / RCCM')->placeholder('—'),
                        TextEntry::make('reste_a_payer')
                            ->label('Reste à payer')
                            ->state(fn (Fournisseur $record) => $record->resteAPayer())
                            ->formatStateUsing(fn ($state) => Montant::format($state))
                            ->weight('bold')
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
