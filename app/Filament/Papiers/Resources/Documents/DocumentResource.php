<?php

namespace App\Filament\Papiers\Resources\Documents;

use App\Filament\Papiers\Resources\Documents\Pages\CreateDocument;
use App\Filament\Papiers\Resources\Documents\Pages\EditDocument;
use App\Filament\Papiers\Resources\Documents\Pages\ListDocuments;
use App\Filament\Partage\DocumentForm;
use App\Filament\Partage\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Archives : tous les documents scannés, retrouvables par châssis, client ou type.
 */
class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $slug = 'archives';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'archives';

    protected static ?string $navigationLabel = 'Archives';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(DocumentForm::champs());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(DocumentsTable::colonnes())
            ->filters(DocumentsTable::filtres())
            ->recordActions(DocumentsTable::actions())
            ->recordUrl(fn (Document $record) => $record->url())
            ->openRecordUrlInNewTab();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['motos', 'client', 'ajoutePar']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['titre', 'motos.chassis', 'client.nom'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
