<?php

namespace App\Filament\Papiers\Resources\Immatriculations;

use App\Filament\Papiers\Resources\Immatriculations\Pages\EditImmatriculation;
use App\Filament\Papiers\Resources\Immatriculations\Pages\ListImmatriculations;
use App\Filament\Papiers\Resources\Immatriculations\Pages\ViewImmatriculation;
use App\Filament\Papiers\Resources\Immatriculations\RelationManagers\RetraitsRelationManager;
use App\Filament\Papiers\Resources\Immatriculations\Schemas\ImmatriculationForm;
use App\Filament\Papiers\Resources\Immatriculations\Schemas\ImmatriculationInfolist;
use App\Filament\Papiers\Resources\Immatriculations\Tables\ImmatriculationsTable;
use App\Filament\Partage\DocumentsRelationManager;
use App\Models\Immatriculation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Dossiers d'immatriculation : ouverts automatiquement à chaque vente.
 */
class ImmatriculationResource extends Resource
{
    protected static ?string $model = Immatriculation::class;

    protected static ?string $slug = 'immatriculations';

    protected static ?string $modelLabel = 'dossier d’immatriculation';

    protected static ?string $pluralModelLabel = 'immatriculations';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function form(Schema $schema): Schema
    {
        return ImmatriculationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImmatriculationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmatriculationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['moto', 'vente.client', 'retraits']);
    }

    public static function getRelations(): array
    {
        return [
            RetraitsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Immatriculation::aRemettre()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Cartes grises arrivées, pas encore récupérées';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['moto.chassis', 'moto.plaque', 'moto.cmc', 'vente.client.nom', 'vente.client.telephone', 'numero_carte_grise'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->moto->chassis} — {$record->vente?->client?->nom}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Statut' => $record->statut->getLabel()];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmatriculations::route('/'),
            'view' => ViewImmatriculation::route('/{record}'),
            'edit' => EditImmatriculation::route('/{record}/edit'),
        ];
    }
}
