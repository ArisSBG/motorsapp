<?php

namespace App\Filament\Gestion\Resources\Fournisseurs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FournisseurForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema(self::champs()),
            ]);
    }

    /** Champs réutilisés aussi dans la création rapide depuis une facture. */
    public static function champs(): array
    {
        return [
            TextInput::make('nom')
                ->label('Nom ou raison sociale')
                ->required()
                ->maxLength(255),
            TextInput::make('telephone')
                ->label('Téléphone')
                ->tel()
                ->maxLength(30),
            TextInput::make('adresse')
                ->label('Adresse ou ville')
                ->maxLength(255),
            TextInput::make('piece_identite')
                ->label('Pièce d’identité ou registre de commerce')
                ->helperText('CNIB, RCCM ou IFU')
                ->maxLength(255),
            Textarea::make('notes')
                ->columnSpanFull()
                ->rows(3),
        ];
    }
}
