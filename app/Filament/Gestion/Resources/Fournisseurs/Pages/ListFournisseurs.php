<?php

namespace App\Filament\Gestion\Resources\Fournisseurs\Pages;

use App\Filament\Gestion\Resources\Fournisseurs\FournisseurResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFournisseurs extends ListRecords
{
    protected static string $resource = FournisseurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
