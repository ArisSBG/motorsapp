<?php

namespace App\Models;

use App\Enums\TypeDocument;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Document scanné. Le fichier est chiffré sur le disque (voir StockageDocuments) :
 * il ne se lit qu'à travers l'application, après vérification des droits.
 */
#[Fillable([
    'type', 'titre', 'chemin', 'nom_original', 'mime', 'taille', 'chiffre',
    'client_id', 'facture_fournisseur_id', 'vente_id', 'user_id', 'notes',
])]
class Document extends Model
{
    use Corbeille, HasFactory, Journalise;

    public const DISQUE = 'documents';

    protected function casts(): array
    {
        return [
            'type' => TypeDocument::class,
            'taille' => 'integer',
            'chiffre' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $d) {
            $d->user_id ??= auth()->id();

            // Type et taille lus sur le fichier déposé (avant son chiffrement)
            $disque = Storage::disk(self::DISQUE);
            if ($d->chemin && $disque->exists($d->chemin)) {
                $d->mime ??= $disque->mimeType($d->chemin) ?: 'application/octet-stream';
                $d->taille ??= $disque->size($d->chemin);
            }
            $d->mime ??= 'application/octet-stream';
            $d->taille ??= 0;
        });
    }

    public function motos(): BelongsToMany
    {
        return $this->belongsToMany(Moto::class, 'document_moto')->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function factureFournisseur(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class)->withTrashed();
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class)->withTrashed();
    }

    public function ajoutePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function url(): string
    {
        return route('documents.fichier', $this);
    }
}
