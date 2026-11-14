<?php

namespace App\Filament\Partage;

use App\Enums\TypeDocument;
use App\Models\Document;
use App\Models\Moto;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Ajout d'un document scanné (PDF ou photo). Sur téléphone, le bouton propose
 * directement l'appareil photo. Le fichier est chiffré dès l'enregistrement.
 */
class DocumentForm
{
    /**
     * @param  bool  $avecMotos  afficher le choix des motos (pas utile depuis la fiche d'une moto)
     */
    public static function champs(bool $avecMotos = true, bool $avecClient = true, ?TypeDocument $typeParDefaut = null): array
    {
        return array_values(array_filter([
            FileUpload::make('chemin')
                ->label('Fichier (PDF ou photo)')
                ->disk(Document::DISQUE)
                ->directory(fn () => 'scans/'.now()->format('Y/m'))
                ->visibility('private')
                ->storeFileNamesIn('nom_original')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/heic'])
                ->maxSize(20 * 1024)
                ->required()
                ->visibleOn('create')
                ->extraInputAttributes(['capture' => 'environment'])
                ->helperText('20 Mo maximum. Sur téléphone : prendre la photo directement.')
                ->columnSpanFull(),
            Select::make('type')
                ->options(TypeDocument::class)
                ->default($typeParDefaut)
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, $state, $get) {
                    if (blank($get('titre')) && $state) {
                        $set('titre', TypeDocument::from($state instanceof TypeDocument ? $state->value : $state)->getLabel());
                    }
                }),
            TextInput::make('titre')
                ->label('Titre')
                ->required()
                ->default($typeParDefaut?->getLabel())
                ->maxLength(255),
            $avecMotos ? Select::make('motos')
                ->label('Motos concernées')
                ->helperText('Une facture de lot peut concerner plusieurs motos.')
                ->relationship('motos', 'chassis')
                ->getOptionLabelFromRecordUsing(fn (Moto $m) => "{$m->chassis} — {$m->marque} {$m->modele}")
                ->searchable(['chassis', 'cmc', 'plaque'])
                ->multiple()
                ->columnSpanFull() : null,
            $avecClient ? Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'nom')
                ->searchable(['nom', 'telephone']) : null,
            Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),
        ]));
    }
}
