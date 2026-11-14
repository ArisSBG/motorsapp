<?php

namespace App\Filament\Gestion\Resources\Clients\Schemas;

use App\Enums\TypeClient;
use App\Enums\TypePiece;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
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

    /** Champs réutilisés dans la création rapide d'un client depuis une vente. */
    public static function champs(): array
    {
        return [
            TextInput::make('nom')
                ->label('Nom et prénom (ou raison sociale)')
                ->required()
                ->maxLength(255),
            TextInput::make('telephone')
                ->label('Téléphone')
                ->tel()
                ->required()
                ->maxLength(30),
            TextInput::make('adresse')
                ->label('Quartier ou adresse')
                ->maxLength(255),
            Select::make('type')
                ->options(TypeClient::class)
                ->default(TypeClient::Particulier)
                ->required(),
            Select::make('type_piece')
                ->label('Type de pièce d’identité')
                ->options(TypePiece::class),
            TextInput::make('numero_piece')
                ->label('Numéro de la pièce')
                ->maxLength(50),
            Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }
}
