<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Pages;

use App\Filament\Gestion\Resources\FactureFournisseurs\FactureFournisseurResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactureFournisseur extends CreateRecord
{
    protected static string $resource = FactureFournisseurResource::class;

    // Après l'enregistrement : aller sur la fiche
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
