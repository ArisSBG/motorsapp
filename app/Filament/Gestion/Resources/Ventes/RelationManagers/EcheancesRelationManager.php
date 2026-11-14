<?php

namespace App\Filament\Gestion\Resources\Ventes\RelationManagers;

use App\Models\Echeance;
use App\Support\Montant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Échéancier : dates et montants des prochains versements prévus.
 */
class EcheancesRelationManager extends RelationManager
{
    protected static string $relationship = 'echeances';

    protected static ?string $title = 'Échéancier';

    protected static ?string $modelLabel = 'échéance';

    protected static ?string $pluralModelLabel = 'échéances';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('date_prevue')
                    ->label('Date prévue')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('montant')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->suffix('FCFA'),
                TextInput::make('notes')->maxLength(255)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $fcfa = fn ($state) => Montant::format($state);

        return $table
            ->defaultSort('date_prevue')
            ->columns([
                TextColumn::make('date_prevue')->label('Date prévue')->date('d/m/Y'),
                TextColumn::make('montant')
                    ->formatStateUsing($fcfa)
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total prévu')->formatStateUsing($fcfa)),
                TextColumn::make('etat')
                    ->label('État')
                    ->badge()
                    ->state(fn (Echeance $record) => $record->estEnRetard() ? 'En retard' : ($record->date_prevue->isPast() ? 'Couverte' : 'À venir'))
                    ->color(fn (string $state) => match ($state) {
                        'En retard' => 'danger', 'Couverte' => 'success', default => 'gray',
                    }),
                TextColumn::make('notes')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une échéance'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
