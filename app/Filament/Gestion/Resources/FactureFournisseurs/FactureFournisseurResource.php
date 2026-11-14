<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs;

use App\Filament\Gestion\Resources\FactureFournisseurs\Pages\CreateFactureFournisseur;
use App\Filament\Gestion\Resources\FactureFournisseurs\Pages\EditFactureFournisseur;
use App\Filament\Gestion\Resources\FactureFournisseurs\Pages\ListFactureFournisseurs;
use App\Filament\Gestion\Resources\FactureFournisseurs\Pages\ViewFactureFournisseur;
use App\Filament\Gestion\Resources\FactureFournisseurs\RelationManagers\MotosRelationManager;
use App\Filament\Gestion\Resources\FactureFournisseurs\RelationManagers\VersementsRelationManager;
use App\Filament\Gestion\Resources\FactureFournisseurs\Schemas\FactureFournisseurForm;
use App\Filament\Gestion\Resources\FactureFournisseurs\Schemas\FactureFournisseurInfolist;
use App\Filament\Gestion\Resources\FactureFournisseurs\Tables\FactureFournisseursTable;
use App\Models\FactureFournisseur;
use App\Filament\Partage\DocumentsRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FactureFournisseurResource extends Resource
{
    protected static ?string $model = FactureFournisseur::class;

    protected static ?string $slug = 'factures-fournisseur';

    protected static ?string $modelLabel = 'facture fournisseur';

    protected static ?string $pluralModelLabel = 'factures fournisseur';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static string|UnitEnum|null $navigationGroup = 'Achats';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return FactureFournisseurForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FactureFournisseurInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FactureFournisseursTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('fournisseur')
            ->withCount('motos')
            ->avecTotaux();
    }

    public static function getRelations(): array
    {
        return [
            MotosRelationManager::class,
            VersementsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'fournisseur.nom'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFactureFournisseurs::route('/'),
            'create' => CreateFactureFournisseur::route('/create'),
            'view' => ViewFactureFournisseur::route('/{record}'),
            'edit' => EditFactureFournisseur::route('/{record}/edit'),
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
