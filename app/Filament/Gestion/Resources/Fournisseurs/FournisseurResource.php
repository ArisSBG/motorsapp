<?php

namespace App\Filament\Gestion\Resources\Fournisseurs;

use App\Filament\Gestion\Resources\Fournisseurs\Pages\CreateFournisseur;
use App\Filament\Gestion\Resources\Fournisseurs\Pages\EditFournisseur;
use App\Filament\Gestion\Resources\Fournisseurs\Pages\ListFournisseurs;
use App\Filament\Gestion\Resources\Fournisseurs\Pages\ViewFournisseur;
use App\Filament\Gestion\Resources\Fournisseurs\RelationManagers\FacturesRelationManager;
use App\Filament\Gestion\Resources\Fournisseurs\Schemas\FournisseurForm;
use App\Filament\Gestion\Resources\Fournisseurs\Schemas\FournisseurInfolist;
use App\Filament\Gestion\Resources\Fournisseurs\Tables\FournisseursTable;
use App\Models\Fournisseur;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FournisseurResource extends Resource
{
    protected static ?string $model = Fournisseur::class;

    protected static ?string $modelLabel = 'fournisseur';

    protected static ?string $pluralModelLabel = 'fournisseurs';

    protected static ?string $recordTitleAttribute = 'nom';

    protected static string|UnitEnum|null $navigationGroup = 'Achats';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return FournisseurForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FournisseurInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FournisseursTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->select('fournisseurs.*')->avecResteAPayer();
    }

    public static function getRelations(): array
    {
        return [
            FacturesRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nom', 'telephone'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFournisseurs::route('/'),
            'create' => CreateFournisseur::route('/create'),
            'view' => ViewFournisseur::route('/{record}'),
            'edit' => EditFournisseur::route('/{record}/edit'),
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
