<?php

namespace App\Observers;

use App\Models\Moto;
use App\Models\VenteLigne;
use App\Services\SuiviPapiers;
use App\Services\SuiviVente;

class VenteLigneObserver
{
    public function __construct(private SuiviVente $suivi, private SuiviPapiers $papiers) {}

    public function created(VenteLigne $ligne): void
    {
        $vente = $ligne->vente;
        $this->suivi->changerStatut($ligne->moto, $vente->statut->statutMoto());
        $this->suivi->recalculer($vente);

        if (SuiviPapiers::venteOuvreDesDossiers($vente)) {
            $this->papiers->ouvrirDossier($ligne->moto_id, $vente);
        }
    }

    public function updated(VenteLigne $ligne): void
    {
        if ($ligne->wasChanged('moto_id')) {
            $ancienne = $ligne->getPrevious()['moto_id'] ?? null;
            $this->suivi->liberer(Moto::find($ancienne));
            if ($ancienne) {
                $this->papiers->fermerDossierDeLaMoto($ancienne);
            }
            $this->suivi->changerStatut($ligne->moto, $ligne->vente->statut->statutMoto());
            if (SuiviPapiers::venteOuvreDesDossiers($ligne->vente)) {
                $this->papiers->ouvrirDossier($ligne->moto_id, $ligne->vente);
            }
        }
        $this->suivi->recalculer($ligne->vente);
    }

    public function deleted(VenteLigne $ligne): void
    {
        $this->suivi->liberer($ligne->moto);
        $this->papiers->fermerDossierDeLaMoto($ligne->moto_id);
        if ($ligne->vente) {
            $this->suivi->recalculer($ligne->vente);
        }
    }
}
