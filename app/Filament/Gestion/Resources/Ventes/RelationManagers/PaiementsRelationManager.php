<?php

namespace App\Filament\Gestion\Resources\Ventes\RelationManagers;

use App\Enums\ModePaiement;
use App\Models\PaiementClient;
use App\Models\Vente;
use App\Support\Montant;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Versements du client, chacun avec son reçu imprimable (questionnaire 6.5).
 */
class PaiementsRelationManager extends RelationManager
{
    protected static string $relationship = 'paiements';

    protected static ?string $title = 'Paiements';

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Vente $vente */
        $vente = $this->getOwnerRecord();

        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('date_paiement')
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
                    ->helperText(fn () => 'Reste à payer : '.Montant::format($vente->rafraichirTotaux()->reste()))
                    ->rule(fn (?PaiementClient $record) => function (string $attribute, $value, Closure $fail) use ($vente, $record) {
                        $max = $vente->rafraichirTotaux()->reste() + ($record?->montant ?? 0);
                        if ((int) $value > $max) {
                            $fail('Le paiement dépasse le reste à payer ('.Montant::format($max).').');
                        }
                    }),
                Select::make('mode')
                    ->options(ModePaiement::class)
                    ->required()
                    ->default(ModePaiement::Especes),
                TextInput::make('reference')
                    ->label('Référence')
                    ->helperText('N° de transaction Mobile Money…')
                    ->maxLength(80),
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->defaultSort('date_paiement', 'desc')
            ->columns([
                TextColumn::make('numero_recu')->label('Reçu')->fontFamily('mono'),
                TextColumn::make('date_paiement')->label('Date')->date('d/m/Y'),
                TextColumn::make('montant')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total payé')->formatStateUsing($fcfa)),
                TextColumn::make('mode')->badge(),
                TextColumn::make('reference')->label('Référence')->placeholder('—'),
                TextColumn::make('encaissePar.name')->label('Encaissé par')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Enregistrer un paiement')
                    ->visible(fn () => $this->getOwnerRecord()->estModifiable())
                    ->after(fn () => $this->dispatch('totaux-modifies')),
            ])
            ->recordActions([
                Action::make('recu')
                    ->label('Reçu')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (PaiementClient $record) => route('documents.recu', $record))
                    ->openUrlInNewTab(),
                EditAction::make()->after(fn () => $this->dispatch('totaux-modifies')),
                DeleteAction::make()->after(fn () => $this->dispatch('totaux-modifies')),
            ]);
    }
}
