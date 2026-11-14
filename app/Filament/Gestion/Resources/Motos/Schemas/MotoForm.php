<?php

namespace App\Filament\Gestion\Resources\Motos\Schemas;

use App\Enums\StatutMoto;
use App\Models\FactureFournisseur;
use App\Models\Moto;
use App\Support\Chassis;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class MotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identification')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema(self::identification()),
                Section::make('Achat')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('fournisseur_id')
                            ->label('Fournisseur')
                            ->relationship('fournisseur', 'nom')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('facture_fournisseur_id', null)),
                        Select::make('facture_fournisseur_id')
                            ->label('Facture fournisseur')
                            ->options(fn (Get $get) => FactureFournisseur::query()
                                ->where('fournisseur_id', $get('fournisseur_id'))
                                ->latest('date_facture')
                                ->pluck('numero', 'id'))
                            ->searchable()
                            ->helperText('Laisser vide si la moto n’est pas sur une facture.'),
                        ...self::achat(),
                    ]),
                Section::make('Suivi')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema(self::suivi()),
            ]);
    }

    /** Châssis (saisi deux fois à la création), moteur, marque, modèle… */
    public static function identification(): array
    {
        return [
            TextInput::make('chassis')
                ->label('Numéro de châssis')
                ->required()
                ->maxLength(30)
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->dehydrateStateUsing(fn (?string $state) => Chassis::normaliser($state))
                ->rule(fn () => function (string $attribute, $value, Closure $fail) {
                    if (! preg_match(Chassis::REGLE, Chassis::normaliser($value))) {
                        $fail('Le châssis doit contenir 5 à 30 lettres ou chiffres.');
                    }
                })
                ->rule(fn (?Moto $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                    $existe = Moto::withTrashed()
                        ->where('chassis', Chassis::normaliser($value))
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->first();
                    if ($existe) {
                        $fail($existe->trashed()
                            ? 'Ce châssis existe déjà (moto dans la corbeille : la restaurer plutôt).'
                            : 'Ce châssis est déjà enregistré.');
                    }
                }),
            TextInput::make('chassis_confirmation')
                ->label('Confirmer le châssis')
                ->helperText('Saisir le châssis une deuxième fois pour éviter les erreurs.')
                ->required()
                ->dehydrated(false)
                ->visibleOn('create')
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->rule(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                    if (Chassis::normaliser($value) !== Chassis::normaliser($get('chassis'))) {
                        $fail('Les deux saisies du châssis ne correspondent pas.');
                    }
                }),
            TextInput::make('marque')
                ->required()
                ->maxLength(40)
                ->datalist(Moto::MARQUES),
            TextInput::make('modele')
                ->label('Modèle')
                ->required()
                ->maxLength(60)
                ->datalist(fn () => Moto::query()->distinct()->orderBy('modele')->limit(100)->pluck('modele')->all()),
            TextInput::make('couleur')
                ->maxLength(40),
            TextInput::make('cylindree')
                ->label('Cylindrée')
                ->numeric()
                ->minValue(0)
                ->suffix('cm³'),
            TextInput::make('numero_moteur')
                ->label('Numéro de moteur')
                ->maxLength(40),
        ];
    }

    public static function achat(): array
    {
        return [
            TextInput::make('prix_achat')
                ->label('Prix d’achat')
                ->required()
                ->integer()
                ->minValue(0)
                ->suffix('FCFA'),
            DatePicker::make('date_reception')
                ->label('Date de réception')
                ->required()
                ->default(now())
                ->native(false)
                ->displayFormat('d/m/Y'),
        ];
    }

    public static function suivi(): array
    {
        return [
            Select::make('statut')
                ->options(StatutMoto::class)
                ->default(StatutMoto::EnStock)
                ->required(),
            TextInput::make('cmc')
                ->label('CMC')
                ->maxLength(40),
            TextInput::make('plaque')
                ->label('Numéro de plaque')
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }
}
