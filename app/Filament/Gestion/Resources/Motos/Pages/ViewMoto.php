<?php

namespace App\Filament\Gestion\Resources\Motos\Pages;

use App\Filament\Gestion\Resources\Motos\MotoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMoto extends ViewRecord
{
    protected static string $resource = MotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
