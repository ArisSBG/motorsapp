<?php

namespace App\Filament\Papiers\Resources\Immatriculations\Pages;

use App\Filament\Papiers\Resources\Immatriculations\ImmatriculationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditImmatriculation extends EditRecord
{
    protected static string $resource = ImmatriculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
