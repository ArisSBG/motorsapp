<?php

namespace App\Filament\Gestion\Resources\Clients;

use App\Filament\Gestion\Resources\Clients\Pages\CreateClient;
use App\Filament\Gestion\Resources\Clients\Pages\EditClient;
use App\Filament\Gestion\Resources\Clients\Pages\ListClients;
use App\Filament\Gestion\Resources\Clients\Pages\ViewClient;
use App\Filament\Gestion\Resources\Clients\RelationManagers\VentesRelationManager;
use App\Filament\Gestion\Resources\Clients\Schemas\ClientForm;
use App\Filament\Gestion\Resources\Clients\Schemas\ClientInfolist;
use App\Filament\Gestion\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use App\Filament\Partage\DocumentsRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $modelLabel = 'client';

    protected static ?string $pluralModelLabel = 'clients';

    protected static ?string $recordTitleAttribute = 'nom';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->select('clients.*')->avecResteDu();
    }

    public static function getRelations(): array
    {
        return [
            VentesRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nom', 'telephone', 'numero_piece'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
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
