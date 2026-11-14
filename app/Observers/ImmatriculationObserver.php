<?php

namespace App\Observers;

use App\Models\Immatriculation;
use App\Services\SuiviPapiers;

class ImmatriculationObserver
{
    public function __construct(private SuiviPapiers $papiers) {}

    public function saving(Immatriculation $dossier): void
    {
        $dossier->statut = SuiviPapiers::statutSelonDates($dossier);
    }

    public function saved(Immatriculation $dossier): void
    {
        // La moto avance avec ses papiers (immatriculation en cours, papiers reçus)
        $this->papiers->synchroniserMoto($dossier);
    }
}
