<?php

namespace App\Filament\Gestion\Resources\Ventes\Pages;

use App\Enums\StatutVente;
use App\Filament\Gestion\Resources\Ventes\VenteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVente extends CreateRecord
{
    protected static string $resource = VenteResource::class;

    protected static ?string $title = 'Nouvelle vente';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['statut'] = ($this->data['reservation'] ?? false) ? StatutVente::Reservation : StatutVente::EnCours;

        return $data;
    }

    /** Après la vente et ses motos : enregistrer le paiement du jour (avec son reçu). */
    protected function afterCreate(): void
    {
        $acompte = (int) ($this->data['acompte'] ?? 0);

        if ($acompte > 0) {
            $this->record->paiements()->create([
                'date_paiement' => $this->record->date_vente,
                'montant' => $acompte,
                'mode' => $this->data['acompte_mode'] ?? 'especes',
                'reference' => $this->data['acompte_reference'] ?? null,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
