<?php

namespace App\Observers;

use App\Models\FactureFournisseur;
use App\Services\RepartitionTransport;

class FactureFournisseurObserver
{
    public function __construct(private RepartitionTransport $repartition) {}

    public function saved(FactureFournisseur $facture): void
    {
        if ($facture->wasRecentlyCreated || $facture->wasChanged(['montant_transport', 'date_facture', 'numero'])) {
            $this->repartition->repartir($facture);
        }
    }

    public function deleted(FactureFournisseur $facture): void
    {
        $this->repartition->repartir($facture);
    }

    public function restored(FactureFournisseur $facture): void
    {
        $this->repartition->repartir($facture);
    }
}
