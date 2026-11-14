<?php

namespace App\Filament\Papiers\Resources\Immatriculations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImmatriculationForm
{
    public static function configure(Schema $schema): Schema
    {
        $date = fn (string $nom, string $libelle) => DatePicker::make($nom)
            ->label($libelle)
            ->native(false)
            ->displayFormat('d/m/Y')
            ->maxDate(now());

        return $schema
            ->components([
                Section::make('Suivi du dossier')
                    ->description('Le statut se met à jour tout seul selon les dates remplies.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('prestataire')->label('Prestataire (si l’immatriculation est confiée à quelqu’un)')->maxLength(255),
                        $date('date_depot', 'Date de dépôt du dossier'),
                        $date('date_provisoire', 'Document provisoire reçu le'),
                        $date('date_recepisse', 'Récépissé reçu le'),
                        $date('date_carte_grise', 'Carte grise reçue le'),
                        TextInput::make('numero_carte_grise')->label('N° de carte grise')->maxLength(50),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
