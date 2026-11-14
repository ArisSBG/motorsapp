<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Pages;

use App\Filament\Gestion\Resources\FactureFournisseurs\FactureFournisseurResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFactureFournisseur extends EditRecord
{
    protected static string $resource = FactureFournisseurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    // Après l'enregistrement : aller sur la fiche
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
