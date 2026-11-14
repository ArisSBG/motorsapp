<?php

namespace App\Filament\Papiers\Resources\Documents\Pages;

use App\Filament\Papiers\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/** Modifier les informations d'un document (le fichier lui-même ne change pas). */
class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ouvrir')->label('Ouvrir le fichier')->icon(Heroicon::OutlinedEye)
                ->url(fn () => $this->record->url())->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
