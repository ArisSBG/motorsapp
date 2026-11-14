<?php

namespace App\Observers;

use App\Models\Retrait;
use App\Services\SuiviPapiers;

/** Carte grise remise → moto « Livrée » ; retrait annulé → retour en arrière. */
class RetraitObserver
{
    public function __construct(private SuiviPapiers $papiers) {}

    public function created(Retrait $retrait): void
    {
        $this->papiers->synchroniserMoto($retrait->immatriculation);
    }

    public function deleted(Retrait $retrait): void
    {
        $this->papiers->synchroniserMoto($retrait->immatriculation);
    }
}
