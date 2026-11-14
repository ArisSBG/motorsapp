<?php

namespace App\Filament\Partage;

use App\Enums\TypeDocument;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Number;

/** Colonnes et actions communes aux listes de documents. */
class DocumentsTable
{
    public static function colonnes(bool $avecMotos = true): array
    {
        return array_values(array_filter([
            TextColumn::make('type')->badge()->icon(fn (Document $record) => $record->type->getIcon()),
            TextColumn::make('titre')->searchable()->weight('bold')->wrap(),
            $avecMotos ? TextColumn::make('motos.chassis')
                ->label('Motos')
                ->badge()
                ->color('gray')
                ->searchable()
                ->limitList(3) : null,
            TextColumn::make('client.nom')->label('Client')->searchable()->placeholder('—'),
            TextColumn::make('created_at')->label('Ajouté le')->date('d/m/Y')->sortable(),
            TextColumn::make('taille')
                ->formatStateUsing(fn ($state) => Number::fileSize((int) $state, precision: 1))
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('ajoutePar.name')->label('Ajouté par')->toggleable(isToggledHiddenByDefault: true),
        ]));
    }

    public static function filtres(): array
    {
        return [
            SelectFilter::make('type')->options(TypeDocument::class)->multiple(),
            TrashedFilter::make()->label('Corbeille'),
        ];
    }

    public static function actions(): array
    {
        return [
            Action::make('ouvrir')
                ->label('Ouvrir')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (Document $record) => $record->url())
                ->openUrlInNewTab(),
            Action::make('telecharger')
                ->label('Télécharger')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn (Document $record) => route('documents.fichier', ['document' => $record, 'telecharger' => 1])),
            EditAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
