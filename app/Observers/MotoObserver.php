<?php

namespace App\Observers;

use App\Models\FactureFournisseur;
use App\Models\Moto;
use App\Services\RepartitionTransport;

/**
 * Quand une moto entre dans une facture, en sort ou est supprimée,
 * le transport de la facture est réparti à nouveau.
 */
class MotoObserver
{
    public function __construct(private RepartitionTransport $repartition) {}

    public function created(Moto $moto): void
    {
        $this->repartition->repartir($this->facture($moto->facture_fournisseur_id));
    }

    public function updated(Moto $moto): void
    {
        if (! $moto->wasChanged('facture_fournisseur_id')) {
            return;
        }

        $this->repartition->repartir($this->facture($moto->facture_fournisseur_id));
        $this->repartition->repartir($this->facture($moto->getPrevious()['facture_fournisseur_id'] ?? null));
    }

    public function deleted(Moto $moto): void
    {
        $this->repartition->repartir($this->facture($moto->facture_fournisseur_id));
    }

    public function restored(Moto $moto): void
    {
        $this->repartition->repartir($this->facture($moto->facture_fournisseur_id));
    }

    private function facture(?int $id): ?FactureFournisseur
    {
        return $id ? FactureFournisseur::withTrashed()->find($id) : null;
    }
}
