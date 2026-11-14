<?php

namespace App\Filament\Gestion\Resources\FactureFournisseurs\Pages;

use App\Filament\Gestion\Resources\FactureFournisseurs\FactureFournisseurResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewFactureFournisseur extends ViewRecord
{
    protected static string $resource = FactureFournisseurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /** Les onglets Motos / Versements préviennent la page pour mettre à jour le solde. */
    #[On('totaux-modifies')]
    public function rafraichirTotaux(): void
    {
        $this->record->refresh();
    }
}
