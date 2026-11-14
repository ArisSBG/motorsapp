<?php

namespace App\Filament\Gestion\Resources\Fournisseurs\Pages;

use App\Filament\Gestion\Resources\Fournisseurs\FournisseurResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFournisseur extends ViewRecord
{
    protected static string $resource = FournisseurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
