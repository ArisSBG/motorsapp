<?php

namespace App\Filament\Papiers\Resources\Immatriculations\Pages;

use App\Filament\Papiers\Resources\Immatriculations\ActionsPapiers;
use App\Filament\Papiers\Resources\Immatriculations\ImmatriculationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewImmatriculation extends ViewRecord
{
    protected static string $resource = ImmatriculationResource::class;

    public function getTitle(): string
    {
        return 'Papiers — '.$this->record->moto->chassis;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionsPapiers::provisoire(),
            ActionsPapiers::recepisse(),
            ActionsPapiers::carteGrise(),
            ActionsPapiers::prevenir(),
            ActionsPapiers::retrait()->after(fn () => $this->dispatch('$refresh')),
            EditAction::make(),
        ];
    }
}
