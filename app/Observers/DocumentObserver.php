<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\StockageDocuments;

class DocumentObserver
{
    public function __construct(private StockageDocuments $stockage) {}

    /** Le fichier déposé est chiffré aussitôt. */
    public function created(Document $document): void
    {
        $this->stockage->chiffrer($document);
    }

    /** Suppression définitive (corbeille vidée) : le fichier est effacé du disque. */
    public function forceDeleted(Document $document): void
    {
        $this->stockage->effacer($document);
    }
}
