<?php

namespace App\Models;

use App\Enums\TypeFrais;
use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('frais_moto')]
#[Fillable(['moto_id', 'type', 'montant', 'description', 'date_frais', 'facture_fournisseur_id'])]
class FraisMoto extends Model
{
    use HasFactory, Journalise;

    protected function casts(): array
    {
        return [
            'type' => TypeFrais::class,
            'montant' => 'integer',
            'date_frais' => 'date',
        ];
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_fournisseur_id');
    }

    /** Part de transport calculée automatiquement : non modifiable à la main. */
    public function estAutomatique(): bool
    {
        return $this->facture_fournisseur_id !== null && $this->type === TypeFrais::Transport;
    }
}
