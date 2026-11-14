<?php

namespace App\Filament\Gestion\Resources\Clients\Schemas;

use App\Models\Client;
use App\Support\Montant;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('nom'),
                        TextEntry::make('telephone')->label('Téléphone')->placeholder('—'),
                        TextEntry::make('adresse')->label('Quartier / adresse')->placeholder('—'),
                        TextEntry::make('type'),
                        TextEntry::make('type_piece')->label('Pièce')->placeholder('—'),
                        TextEntry::make('numero_piece')->label('N° de pièce')->placeholder('—'),
                        TextEntry::make('reste_du')
                            ->label('Doit encore')
                            ->state(fn (Client $record) => $record->resteDu())
                            ->formatStateUsing(fn ($state) => Montant::format($state))
                            ->weight('bold')
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
