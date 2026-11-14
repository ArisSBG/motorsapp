<?php

namespace App\Filament\Papiers\Resources\Documents\Pages;

use App\Filament\Papiers\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected static ?string $title = 'Ajouter un document';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
