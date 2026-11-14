<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Pages;

use App\Filament\Gestion\Resources\FactureFournisseurs\FactureFournisseurResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFactureFournisseurs extends ListRecords
{
    protected static string $resource = FactureFournisseurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
