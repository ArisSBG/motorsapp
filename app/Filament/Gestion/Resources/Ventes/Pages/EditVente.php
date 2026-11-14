<?php

namespace App\Filament\Gestion\Resources\Ventes\Pages;

use App\Filament\Gestion\Resources\Ventes\VenteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/** Modification d'une vente : réservée à l'administrateur (droit « modifier_vente »). */
class EditVente extends EditRecord
{
    protected static string $resource = VenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
