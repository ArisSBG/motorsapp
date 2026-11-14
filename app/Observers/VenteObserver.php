<?php

namespace App\Observers;

use App\Models\Vente;
use App\Services\SuiviPapiers;
use App\Services\SuiviVente;

/**
 * Chaque changement de statut d'une vente met à jour les motos (SuiviVente)
 * et les dossiers d'immatriculation (SuiviPapiers).
 */
class VenteObserver
{
    public function __construct(private SuiviVente $suivi, private SuiviPapiers $papiers) {}

    public function updated(Vente $vente): void
    {
        if (! $vente->wasChanged('statut')) {
            return;
        }

        $this->suivi->synchroniserMotos($vente);

        SuiviPapiers::venteOuvreDesDossiers($vente)
            ? $this->papiers->ouvrirDossiers($vente)
            : $this->papiers->fermerDossiers($vente);
    }

    public function deleted(Vente $vente): void
    {
        $this->suivi->synchroniserMotos($vente);
        $this->papiers->fermerDossiers($vente);
    }

    public function restored(Vente $vente): void
    {
        $this->suivi->synchroniserMotos($vente);
        $this->papiers->ouvrirDossiers($vente);
    }
}
