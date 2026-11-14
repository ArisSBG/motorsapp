<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Chiffre les documents scannés sur le disque (pièces d'identité, cartes grises…).
 * Même si quelqu'un copie le dossier du serveur, les fichiers sont illisibles
 * sans la clé de l'application (APP_KEY), à garder avec les sauvegardes.
 */
class StockageDocuments
{
    private function disque()
    {
        return Storage::disk(Document::DISQUE);
    }

    /** Complète taille et type, puis chiffre le fichier déposé par le formulaire. */
    public function chiffrer(Document $document): void
    {
        if ($document->chiffre || ! $this->disque()->exists($document->chemin)) {
            return;
        }

        $contenu = $this->disque()->get($document->chemin);

        $document->forceFill([
            'taille' => strlen($contenu),
            'mime' => $document->mime ?: ($this->disque()->mimeType($document->chemin) ?: 'application/octet-stream'),
        ]);

        $this->disque()->put($document->chemin, Crypt::encryptString(base64_encode($contenu)));

        $document->forceFill(['chiffre' => true])->saveQuietly();
    }

    /** Contenu lisible du fichier (déchiffré). */
    public function lire(Document $document): string
    {
        $contenu = $this->disque()->get($document->chemin);

        return $document->chiffre ? base64_decode(Crypt::decryptString($contenu)) : $contenu;
    }

    /** Efface le fichier quand le document est supprimé définitivement. */
    public function effacer(Document $document): void
    {
        $this->disque()->delete($document->chemin);
    }
}
