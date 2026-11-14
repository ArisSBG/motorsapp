<?php

namespace App\Models;

use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versement prévu d'un client (échéancier, questionnaire 6.8). */
#[Fillable(['vente_id', 'date_prevue', 'montant', 'notes'])]
class Echeance extends Model
{
    use HasFactory, Journalise;

    protected function casts(): array
    {
        return [
            'date_prevue' => 'date',
            'montant' => 'integer',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    /**
     * Une échéance est « en retard » si sa date est passée et que les paiements reçus
     * ne couvrent pas le cumul des échéances jusqu'à elle.
     */
    public function estEnRetard(): bool
    {
        if ($this->date_prevue->isFuture() || $this->date_prevue->isToday()) {
            return false;
        }

        $cumulPrevu = Echeance::where('vente_id', $this->vente_id)
            ->where('date_prevue', '<=', $this->date_prevue)
            ->sum('montant');

        return $this->vente->paye() < $cumulPrevu;
    }
}
