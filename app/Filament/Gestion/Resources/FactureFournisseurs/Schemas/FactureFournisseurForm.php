<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Schemas;

use App\Filament\Gestion\Resources\Fournisseurs\Schemas\FournisseurForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class FactureFournisseurForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->description('Après l’enregistrement, ajoutez les motos et les versements depuis la fiche de la facture.')
                    ->schema([
                        Select::make('fournisseur_id')
                            ->label('Fournisseur')
                            ->relationship('fournisseur', 'nom')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm(FournisseurForm::champs())
                            ->disabledOn('edit'),
                        TextInput::make('numero')
                            ->label('Numéro de facture')
                            ->required()
                            ->maxLength(60)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('fournisseur_id', $get('fournisseur_id')),
                            )
                            ->validationMessages(['unique' => 'Ce fournisseur a déjà une facture avec ce numéro.']),
                        DatePicker::make('date_facture')
                            ->label('Date de la facture')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        TextInput::make('montant_transport')
                            ->label('Frais de transport du lot')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('FCFA')
                            ->helperText('Réparti automatiquement à parts égales sur les motos de la facture.'),
                        TextInput::make('remise')
                            ->label('Remise du fournisseur')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('FCFA'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
