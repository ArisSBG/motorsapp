<?php

namespace App\Filament\Gestion\Resources\Journal;

use App\Filament\Gestion\Resources\Journal\Pages\ListJournal;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Journal « qui a fait quoi et quand » (cahier des charges, 3 et 6.1).
 * Lecture seule : personne ne peut modifier ni effacer le journal depuis l'application.
 */
class JournalResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'journal';

    protected static ?string $modelLabel = 'action';

    protected static ?string $pluralModelLabel = 'journal';

    protected static ?string $navigationLabel = 'Journal';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    private const EVENEMENTS = [
        'created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration',
        'connexion' => 'Connexion', 'echec_connexion' => 'Échec de connexion', 'deconnexion' => 'Déconnexion',
        'deconnexion_forcee' => 'Déconnexion forcée',
    ];

    private const ELEMENTS = [
        'moto' => 'Moto', 'fournisseur' => 'Fournisseur', 'facture_fournisseur' => 'Facture fournisseur',
        'versement_fournisseur' => 'Versement fournisseur', 'frais_moto' => 'Frais moto', 'comptes' => 'Compte utilisateur',
        'client' => 'Client', 'vente' => 'Vente', 'vente_ligne' => 'Moto vendue', 'paiement_client' => 'Paiement client',
        'echeance' => 'Échéance', 'immatriculation' => 'Immatriculation', 'retrait' => 'Remise de papiers',
        'document' => 'Document', 'objectif_vente' => 'Objectif de ventes', 'connexions' => 'Connexions',
    ];

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->estAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')->label('Date')->dateTime('d/m/Y à H:i'),
                TextEntry::make('causer.name')->label('Par')->placeholder('Système'),
                TextEntry::make('event')->label('Action')->formatStateUsing(fn (?string $state) => self::EVENEMENTS[$state] ?? $state),
                TextEntry::make('log_name')->label('Élément')->formatStateUsing(fn (?string $state) => self::ELEMENTS[$state] ?? $state),
                KeyValueEntry::make('attribute_changes.old')->label('Avant')->columnSpanFull()
                    ->visible(fn (Activity $record) => filled($record->attribute_changes?->get('old'))),
                KeyValueEntry::make('attribute_changes.attributes')->label('Après')->columnSpanFull()
                    ->visible(fn (Activity $record) => filled($record->attribute_changes?->get('attributes'))),
                KeyValueEntry::make('properties')->label('Détails (adresse IP, appareil…)')->columnSpanFull()
                    ->visible(fn (Activity $record) => filled($record->properties?->all())),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('causer.name')->label('Par')->placeholder('Système'),
                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::EVENEMENTS[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'created', 'connexion' => 'success', 'updated' => 'warning',
                        'deleted', 'echec_connexion', 'deconnexion_forcee' => 'danger', default => 'gray',
                    }),
                TextColumn::make('log_name')->label('Élément')->formatStateUsing(fn (?string $state) => self::ELEMENTS[$state] ?? $state),
                TextColumn::make('subject_id')->label('N°'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Élément')->options(self::ELEMENTS),
                SelectFilter::make('event')->label('Action')->options(self::EVENEMENTS),
                SelectFilter::make('causer_id')
                    ->label('Utilisateur')
                    ->options(fn () => \App\Models\User::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournal::route('/'),
        ];
    }
}
