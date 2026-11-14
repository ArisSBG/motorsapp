<?php

namespace App\Models;

use App\Enums\StatutMoto;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use App\Support\Chassis;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'chassis', 'numero_moteur', 'marque', 'modele', 'couleur', 'cylindree',
    'fournisseur_id', 'facture_fournisseur_id', 'prix_achat', 'date_reception',
    'plaque', 'cmc', 'statut', 'notes',
])]
class Moto extends Model
{
    use Corbeille, HasFactory, Journalise;

    /** Marques vendues par le client (questionnaire, 1.8) — suggestions à la saisie. */
    public const MARQUES = ['Yamaha', 'Honda', 'Safari', 'Suzuki', 'Apsonic', 'MK', 'WAW'];

    protected $attributes = [
        'statut' => 'en_stock',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutMoto::class,
            'date_reception' => 'date',
            'prix_achat' => 'integer',
            'cylindree' => 'integer',
        ];
    }

    protected function chassis(): Attribute
    {
        return Attribute::make(set: fn (?string $valeur) => Chassis::normaliser($valeur));
    }

    protected function plaque(): Attribute
    {
        return Attribute::make(set: fn (?string $valeur) => $valeur === null ? null : strtoupper(trim($valeur)));
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_fournisseur_id');
    }

    public function frais(): HasMany
    {
        return $this->hasMany(FraisMoto::class);
    }

    public function versements(): BelongsToMany
    {
        return $this->belongsToMany(VersementFournisseur::class, 'moto_versement_fournisseur');
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('statut', StatutMoto::EnStock);
    }

    /** Ajoute la colonne `total_frais` (transport, réparations…). */
    public function scopeAvecFrais(Builder $query): Builder
    {
        return $query->withSum('frais as total_frais', 'montant');
    }

    public function totalFrais(): int
    {
        if (array_key_exists('total_frais', $this->attributes)) {
            return (int) $this->attributes['total_frais'];
        }

        return (int) $this->frais()->sum('montant');
    }

    /** Coût de revient avant vente : prix d'achat + frais. */
    public function coutDeRevient(): int
    {
        return $this->prix_achat + $this->totalFrais();
    }

    public function immatriculation(): HasOne
    {
        return $this->hasOne(Immatriculation::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_moto');
    }
}
