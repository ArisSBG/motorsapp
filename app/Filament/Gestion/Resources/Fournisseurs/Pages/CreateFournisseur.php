<?php

namespace App\Filament\Gestion\Resources\Fournisseurs\Pages;

use App\Filament\Gestion\Resources\Fournisseurs\FournisseurResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFournisseur extends CreateRecord
{
    protected static string $resource = FournisseurResource::class;

    // Après l'enregistrement : aller sur la fiche
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
