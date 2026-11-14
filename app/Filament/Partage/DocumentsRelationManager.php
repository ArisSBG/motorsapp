<?php

namespace App\Filament\Partage;

use App\Enums\TypeDocument;
use App\Models\Client;
use App\Models\FactureFournisseur;
use App\Models\Immatriculation;
use App\Models\Moto;
use App\Models\Vente;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Onglet « Documents » sur les fiches moto, client, facture fournisseur, vente et dossier d'immatriculation.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'documents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('viewAny', \App\Models\Document::class);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    /** Type proposé selon la fiche d'où l'on ajoute le document. */
    private function typeParDefaut(): ?TypeDocument
    {
        return match (true) {
            $this->getOwnerRecord() instanceof Client => TypeDocument::PieceIdentite,
            $this->getOwnerRecord() instanceof FactureFournisseur => TypeDocument::FactureFournisseur,
            $this->getOwnerRecord() instanceof Vente => TypeDocument::FactureVente,
            $this->getOwnerRecord() instanceof Immatriculation => TypeDocument::Provisoire,
            default => null,
        };
    }

    public function form(Schema $schema): Schema
    {
        $owner = $this->getOwnerRecord();

        return $schema
            ->columns(2)
            ->components(DocumentForm::champs(
                avecMotos: ! ($owner instanceof Moto || $owner instanceof Immatriculation),
                avecClient: ! ($owner instanceof Client),
                typeParDefaut: $this->typeParDefaut(),
            ));
    }

    public function table(Table $table): Table
    {
        $owner = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class])->with(['motos', 'client']))
            ->defaultSort('created_at', 'desc')
            ->columns(DocumentsTable::colonnes(avecMotos: ! ($owner instanceof Moto || $owner instanceof Immatriculation)))
            ->filters(DocumentsTable::filtres())
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un document')
                    ->mutateDataUsing(function (array $data) use ($owner) {
                        // Rattache aussi le client de la vente, pour retrouver le document par son nom
                        if ($owner instanceof Vente || $owner instanceof Immatriculation) {
                            $data['client_id'] ??= $owner instanceof Vente ? $owner->client_id : $owner->vente?->client_id;
                        }

                        return $data;
                    }),
            ])
            ->recordActions(DocumentsTable::actions());
    }
}
