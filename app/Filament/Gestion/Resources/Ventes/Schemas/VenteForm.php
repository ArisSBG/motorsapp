<?php

namespace App\Filament\Gestion\Resources\Ventes\Schemas;

use App\Enums\ModePaiement;
use App\Enums\StatutMoto;
use App\Filament\Gestion\Resources\Clients\Schemas\ClientForm;
use App\Filament\Gestion\Resources\Motos\Tables\MotosTable;
use App\Models\Moto;
use App\Models\Vente;
use App\Support\Montant;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'nom')
                            ->searchable(['nom', 'telephone', 'numero_piece'])
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nom} — {$record->telephone}")
                            ->preload()
                            ->required()
                            ->default(fn () => request()->integer('client') ?: null)
                            ->createOptionForm(ClientForm::champs())
                            ->createOptionModalHeading('Nouveau client')
                            ->columnSpan(2),
                        DatePicker::make('date_vente')
                            ->label('Date de la vente')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Toggle::make('reservation')
                            ->label('Réservation seulement (le client verse un acompte, la moto reste réservée)')
                            ->dehydrated(false)
                            ->visibleOn('create')
                            ->columnSpanFull(),
                    ]),

                Section::make('Motos vendues')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('lignes')
                            ->hiddenLabel()
                            ->relationship()
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Ajouter une autre moto')
                            ->itemLabel(fn (array $state) => self::libelleMoto($state['moto_id'] ?? null))
                            ->schema(self::ligne()),
                    ]),

                Section::make('Apporteur d’affaires')
                    ->description('Personne qui a envoyé ce client et reçoit une commission (5.000 à 50.000 FCFA).')
                    ->columns(3)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextInput::make('apporteur_nom')->label('Nom')->maxLength(255),
                        TextInput::make('apporteur_telephone')->label('Téléphone')->tel()->maxLength(30),
                        TextInput::make('commission')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('FCFA')
                            ->requiredWith('apporteur_nom'),
                    ]),

                Section::make('Paiement d’aujourd’hui')
                    ->description('Montant versé par le client maintenant. Les autres versements s’ajoutent depuis la fiche de la vente.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('acompte')
                            ->label('Montant versé')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('FCFA')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->helperText(fn (Get $get) => 'Total de la vente : '.Montant::format(self::totalSaisi($get)))
                            ->rule(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                if ((int) $value > self::totalSaisi($get)) {
                                    $fail('Le montant versé dépasse le total de la vente.');
                                }
                            }),
                        Select::make('acompte_mode')
                            ->label('Mode de paiement')
                            ->options(ModePaiement::class)
                            ->default(ModePaiement::Especes)
                            ->dehydrated(false)
                            ->requiredWith('acompte'),
                        TextInput::make('acompte_reference')
                            ->label('Référence (Mobile Money…)')
                            ->dehydrated(false)
                            ->maxLength(80),
                    ]),

                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    /** Une ligne : la moto et son prix, avec les coûts payés par le vendeur. */
    private static function ligne(): array
    {
        return [
            Select::make('moto_id')
                ->label('Moto (châssis)')
                ->required()
                ->searchable()
                ->distinct()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->getSearchResultsUsing(fn (string $search, $livewire) => self::motosChoisissables(self::vente($livewire))
                    ->where(fn ($q) => $q->where('chassis', 'like', "%{$search}%")
                        ->orWhere('modele', 'like', "%{$search}%")
                        ->orWhere('marque', 'like', "%{$search}%")
                        ->orWhere('cmc', 'like', "%{$search}%"))
                    ->limit(30)->get()
                    ->mapWithKeys(fn (Moto $m) => [$m->id => self::libelle($m)])->all())
                ->options(fn ($livewire) => self::motosChoisissables(self::vente($livewire))->latest('date_reception')->limit(50)->get()
                    ->mapWithKeys(fn (Moto $m) => [$m->id => self::libelle($m)])->all())
                ->getOptionLabelUsing(fn ($value) => self::libelleMoto($value))
                ->live()
                ->helperText(fn (Get $get) => self::aideCout($get('moto_id')))
                ->rule(fn ($livewire) => function (string $attribute, $value, Closure $fail) use ($livewire) {
                    if (! self::motosChoisissables(self::vente($livewire))->whereKey($value)->exists()) {
                        $fail('Cette moto n’est plus disponible (déjà vendue ou réservée).');
                    }
                })
                ->columnSpan(4),
            TextInput::make('prix_vente')
                ->label('Prix de vente')
                ->required()
                ->integer()
                ->minValue(1)
                ->suffix('FCFA')
                ->live(onBlur: true),
            TextInput::make('frais_immatriculation')
                ->label('Frais d’immatriculation')
                ->helperText('Payés par vous (ex. 65.000 pour un tricycle)')
                ->integer()
                ->minValue(0)
                ->default(fn () => config('entreprise.defaut_immatriculation'))
                ->suffix('FCFA'),
            TextInput::make('cout_casque')
                ->label('Coût du casque')
                ->helperText('0 si le casque n’est pas donné')
                ->integer()
                ->minValue(0)
                ->default(fn () => config('entreprise.defaut_casque'))
                ->suffix('FCFA'),
        ];
    }

    /** La vente en cours de modification (null à la création). */
    private static function vente($livewire): ?Vente
    {
        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

        return $record instanceof Vente ? $record : null;
    }

    /** Motos en stock, plus celles déjà dans cette vente (en modification). */
    private static function motosChoisissables(?Vente $vente)
    {
        return Moto::query()->where(fn ($q) => $q
            ->where('statut', StatutMoto::EnStock)
            ->when($vente?->exists, fn ($q) => $q->orWhereIn('id', $vente->lignes()->pluck('moto_id'))));
    }

    private static function libelle(Moto $m): string
    {
        return "{$m->chassis} — {$m->marque} {$m->modele}".($m->couleur ? " ({$m->couleur})" : '');
    }

    private static function libelleMoto($id): ?string
    {
        $moto = $id ? Moto::withTrashed()->find($id) : null;

        return $moto ? self::libelle($moto) : null;
    }

    private static function aideCout($motoId): ?string
    {
        if (! $motoId || ! MotosTable::peutVoirPrix()) {
            return null;
        }
        $moto = Moto::find($motoId);

        return $moto ? 'Coût de revient : '.Montant::format($moto->coutDeRevient()) : null;
    }

    private static function totalSaisi(Get $get): int
    {
        return (int) collect($get('lignes') ?? [])->sum(fn ($l) => (int) ($l['prix_vente'] ?? 0));
    }
}
