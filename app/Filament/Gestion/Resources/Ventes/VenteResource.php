<?php

namespace App\Filament\Gestion\Resources\Ventes;

use App\Filament\Gestion\Resources\Ventes\Pages\CreateVente;
use App\Filament\Gestion\Resources\Ventes\Pages\EditVente;
use App\Filament\Gestion\Resources\Ventes\Pages\ListVentes;
use App\Filament\Gestion\Resources\Ventes\Pages\ViewVente;
use App\Filament\Gestion\Resources\Ventes\RelationManagers\EcheancesRelationManager;
use App\Filament\Gestion\Resources\Ventes\RelationManagers\PaiementsRelationManager;
use App\Filament\Gestion\Resources\Ventes\Schemas\VenteForm;
use App\Filament\Gestion\Resources\Ventes\Schemas\VenteInfolist;
use App\Filament\Gestion\Resources\Ventes\Tables\VentesTable;
use App\Models\Vente;
use App\Filament\Partage\DocumentsRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VenteResource extends Resource
{
    protected static ?string $model = Vente::class;

    protected static ?string $modelLabel = 'vente';

    protected static ?string $pluralModelLabel = 'ventes';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function peutVoirBenefice(): bool
    {
        return (bool) auth()->user()?->can('voir_benefice');
    }

    public static function form(Schema $schema): Schema
    {
        return VenteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VenteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VentesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['client', 'lignes.moto'])
            ->avecTotaux();
    }

    public static function getRelations(): array
    {
        return [
            PaiementsRelationManager::class,
            EcheancesRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'client.nom', 'client.telephone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Client' => $record->client?->nom, 'Statut' => $record->statut->getLabel()];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVentes::route('/'),
            'create' => CreateVente::route('/create'),
            'view' => ViewVente::route('/{record}'),
            'edit' => EditVente::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
