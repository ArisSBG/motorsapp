<?php

namespace App\Models;

use App\Enums\ModePaiement;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('versements_fournisseur')]
#[Fillable(['facture_fournisseur_id', 'date_versement', 'montant', 'mode', 'reference', 'notes'])]
class VersementFournisseur extends Model
{
    use Corbeille, HasFactory, Journalise;

    protected function casts(): array
    {
        return [
            'date_versement' => 'date',
            'montant' => 'integer',
            'mode' => ModePaiement::class,
        ];
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_fournisseur_id');
    }

    /** Motos précises visées par ce versement (vide = facture entière). */
    public function motos(): BelongsToMany
    {
        return $this->belongsToMany(Moto::class, 'moto_versement_fournisseur');
    }
}
