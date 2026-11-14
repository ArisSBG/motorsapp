<?php

namespace App\Filament\Gestion\Resources\Motos\RelationManagers;

use App\Enums\TypeFrais;
use App\Models\FraisMoto;
use App\Support\Montant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Frais d'une moto avant la vente : réparation, capots…
 * La part de transport est calculée depuis la facture et ne se modifie pas ici.
 */
class FraisRelationManager extends RelationManager
{
    protected static string $relationship = 'frais';

    protected static ?string $title = 'Frais';

    protected static ?string $modelLabel = 'frais';

    protected static ?string $pluralModelLabel = 'frais';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('voir_prix_achat');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(TypeFrais::saisissables())
                    ->required(),
                TextInput::make('montant')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->suffix('FCFA'),
                DatePicker::make('date_frais')
                    ->label('Date')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('description')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_frais', 'desc')
            ->columns([
                TextColumn::make('date_frais')->label('Date')->date('d/m/Y'),
                TextColumn::make('type')->badge(),
                TextColumn::make('description')->wrap(),
                TextColumn::make('montant')
                    ->formatStateUsing(fn ($state) => Montant::format($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Montant::format($state))),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un frais'),
            ])
            ->recordActions([
                EditAction::make()->hidden(fn (FraisMoto $record) => $record->estAutomatique()),
                DeleteAction::make()->hidden(fn (FraisMoto $record) => $record->estAutomatique()),
            ]);
    }
}
