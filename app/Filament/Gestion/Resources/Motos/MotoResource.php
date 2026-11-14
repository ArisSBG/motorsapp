<?php

namespace App\Filament\Gestion\Resources\Motos;

use App\Filament\Gestion\Resources\Motos\Pages\CreateMoto;
use App\Filament\Gestion\Resources\Motos\Pages\EditMoto;
use App\Filament\Gestion\Resources\Motos\Pages\ListMotos;
use App\Filament\Gestion\Resources\Motos\Pages\ViewMoto;
use App\Filament\Gestion\Resources\Motos\RelationManagers\FraisRelationManager;
use App\Filament\Gestion\Resources\Motos\Schemas\MotoForm;
use App\Filament\Gestion\Resources\Motos\Schemas\MotoInfolist;
use App\Filament\Gestion\Resources\Motos\Tables\MotosTable;
use App\Models\Moto;
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

class MotoResource extends Resource
{
    protected static ?string $model = Moto::class;

    protected static ?string $modelLabel = 'moto';

    protected static ?string $pluralModelLabel = 'motos';

    protected static ?string $recordTitleAttribute = 'chassis';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function form(Schema $schema): Schema
    {
        return MotoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MotoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MotosTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['fournisseur', 'facture'])->avecFrais();
    }

    public static function getRelations(): array
    {
        return [
            FraisRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Moto::disponibles()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Motos en stock';
    }

    // Recherche globale : par châssis, CMC ou plaque
    public static function getGloballySearchableAttributes(): array
    {
        return ['chassis', 'cmc', 'plaque'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->chassis;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Moto' => "{$record->marque} {$record->modele}",
            'Statut' => $record->statut->getLabel(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMotos::route('/'),
            'create' => CreateMoto::route('/create'),
            'view' => ViewMoto::route('/{record}'),
            'edit' => EditMoto::route('/{record}/edit'),
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
