<?php

namespace App\Models;

use App\Enums\DocumentRemis;
use App\Enums\StatutImmatriculation;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'moto_id', 'vente_id', 'statut', 'prestataire', 'date_depot', 'date_provisoire',
    'date_recepisse', 'date_carte_grise', 'numero_carte_grise', 'client_prevenu_le', 'notes',
])]
class Immatriculation extends Model
{
    use Corbeille, HasFactory, Journalise;

    /** Validité du document provisoire (questionnaire 8.12). */
    public const JOURS_VALIDITE_PROVISOIRE = 60;

    protected $attributes = [
        'statut' => 'dossier_en_cours',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutImmatriculation::class,
            'date_depot' => 'date',
            'date_provisoire' => 'date',
            'date_recepisse' => 'date',
            'date_carte_grise' => 'date',
            'client_prevenu_le' => 'datetime',
        ];
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class)->withTrashed();
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class)->withTrashed();
    }

    public function retraits(): HasMany
    {
        return $this->hasMany(Retrait::class)->latest('date_retrait');
    }

    /** Documents archivés de la moto de ce dossier. */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_moto', 'moto_id', 'document_id', 'moto_id', 'id');
    }

    /** Fin de validité du provisoire, tant qu'aucun papier plus récent n'est arrivé. */
    public function provisoireExpireLe(): ?CarbonInterface
    {
        if ($this->statut !== StatutImmatriculation::ProvisoireRecu) {
            return null;
        }

        return $this->date_provisoire?->copy()->addDays(self::JOURS_VALIDITE_PROVISOIRE);
    }

    public function carteGriseRemise(): bool
    {
        return $this->retraits->contains(fn (Retrait $r) => $r->document === DocumentRemis::CarteGrise);
    }

    /** Carte grise arrivée mais pas encore récupérée par le client. */
    public function scopeARemettre(Builder $query): Builder
    {
        return $query->where('statut', StatutImmatriculation::CarteGriseRecue)
            ->whereDoesntHave('retraits', fn ($q) => $q->where('document', DocumentRemis::CarteGrise));
    }

    public function scopeRemis(Builder $query): Builder
    {
        return $query->whereHas('retraits', fn ($q) => $q->where('document', DocumentRemis::CarteGrise));
    }

    /** Vendue depuis plus de N jours sans carte grise (alerte, questionnaire 8.9). */
    public function scopeEnRetard(Builder $query): Builder
    {
        return $query->where('statut', '!=', StatutImmatriculation::CarteGriseRecue)
            ->where('created_at', '<=', now()->subDays(config('entreprise.jours_alerte_papiers')));
    }
}
