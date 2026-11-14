<?php

namespace App\Observers;

use App\Models\PaiementClient;
use App\Services\SuiviVente;

class PaiementClientObserver
{
    public function __construct(private SuiviVente $suivi) {}

    public function saved(PaiementClient $paiement): void
    {
        $this->suivi->recalculer($paiement->vente);
    }

    public function deleted(PaiementClient $paiement): void
    {
        $this->suivi->recalculer($paiement->vente);
    }

    public function restored(PaiementClient $paiement): void
    {
        $this->suivi->recalculer($paiement->vente);
    }
}
