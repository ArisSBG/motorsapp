<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\RelationManagers;

use App\Enums\ModePaiement;
use App\Models\FactureFournisseur;
use App\Models\VersementFournisseur;
use App\Support\Montant;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historique des versements au fournisseur (date, montant, mode).
 */
class VersementsRelationManager extends RelationManager
{
    protected static string $relationship = 'versements';

    protected static ?string $title = 'Versements';

    protected static ?string $modelLabel = 'versement';

    protected static ?string $pluralModelLabel = 'versements';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var FactureFournisseur $facture */
        $facture = $this->getOwnerRecord();

        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('date_versement')
                    ->label('Date')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('montant')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->suffix('FCFA')
                    ->helperText(fn () => 'Reste à payer : '.Montant::format($facture->rafraichirTotaux()->solde()))
                    ->rule(fn (?VersementFournisseur $record) => function (string $attribute, $value, Closure $fail) use ($facture, $record) {
                        $max = $facture->rafraichirTotaux()->solde() + ($record?->montant ?? 0);
                        if ((int) $value > $max) {
                            $fail('Le versement dépasse le reste à payer ('.Montant::format($max).').');
                        }
                    }),
                Select::make('mode')
                    ->options(ModePaiement::class)
                    ->required()
                    ->default(ModePaiement::Virement),
                TextInput::make('reference')
                    ->label('Référence')
                    ->helperText('N° de bordereau, de transaction…')
                    ->maxLength(80),
                Select::make('motos')
                    ->label('Motos concernées')
                    ->helperText('Laisser vide si le versement concerne toute la facture.')
                    ->relationship('motos', 'chassis', fn ($query) => $query->where('facture_fournisseur_id', $facture->id))
                    ->multiple()
                    ->preload()
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->defaultSort('date_versement', 'desc')
            ->columns([
                TextColumn::make('date_versement')->label('Date')->date('d/m/Y'),
                TextColumn::make('montant')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total versé')->formatStateUsing($fcfa)),
                TextColumn::make('mode')->badge(),
                TextColumn::make('reference')->label('Référence')->placeholder('—'),
                TextColumn::make('motos.chassis')->label('Motos')->badge()->placeholder('Toute la facture'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Enregistrer un versement')
                    ->after(fn () => $this->dispatch('totaux-modifies')),
            ])
            ->recordActions([
                EditAction::make()->after(fn () => $this->dispatch('totaux-modifies')),
                DeleteAction::make()->after(fn () => $this->dispatch('totaux-modifies')),
            ]);
    }
}
