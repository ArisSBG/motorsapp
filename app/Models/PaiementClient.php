<?php

namespace App\Models;

use App\Enums\ModePaiement;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('paiements_client')]
#[Fillable(['numero_recu', 'vente_id', 'user_id', 'date_paiement', 'montant', 'mode', 'reference', 'notes'])]
class PaiementClient extends Model
{
    use Corbeille, HasFactory, Journalise;

    protected function casts(): array
    {
        return [
            'date_paiement' => 'date',
            'montant' => 'integer',
            'mode' => ModePaiement::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaiementClient $paiement) {
            $paiement->numero_recu ??= Numerotation::suivant(self::class, 'numero_recu', config('entreprise.prefixe_recu'));
            $paiement->user_id ??= auth()->id();
        });
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function encaissePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
