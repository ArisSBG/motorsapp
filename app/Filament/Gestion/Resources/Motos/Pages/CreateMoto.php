<?php

namespace App\Filament\Gestion\Resources\Motos\Pages;

use App\Filament\Gestion\Resources\Motos\MotoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMoto extends CreateRecord
{
    protected static string $resource = MotoResource::class;

    // Après l'enregistrement : aller sur la fiche
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
