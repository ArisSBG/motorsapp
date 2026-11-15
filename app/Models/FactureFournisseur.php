<?php

namespace App\Models;

use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use App\Models\Concerns\TotauxCalcules;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Facture d'un fournisseur : 3 à 20 motos à prix différents, payée en un ou plusieurs versements.
 * Total dû au fournisseur = somme des prix d'achat des motos − remise.
 * Le transport n'est pas dû au fournisseur : il est réparti en frais sur les motos.
 */
#[Table('factures_fournisseur')]
#[Fillable(['fournisseur_id', 'numero', 'date_facture', 'montant_transport', 'remise', 'notes'])]
class FactureFournisseur extends Model
{
    use Corbeille, HasFactory, Journalise, TotauxCalcules;

    protected static function colonnesTotaux(): array
    {
        return ['total', 'verse', 'solde'];
    }

    protected function casts(): array
    {
        return [
            'date_facture' => 'date',
            'montant_transport' => 'integer',
            'remise' => 'integer',
        ];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class);
    }

    public function versements(): HasMany
    {
        return $this->hasMany(VersementFournisseur::class);
    }

    // --- Totaux calculés en SQL (réutilisés par Fournisseur::avecResteAPayer) ---

    /** Expression SQL : prix des motos − remise. */
    public static function sqlTotal(): string
    {
        return '(SELECT COALESCE(SUM(m.prix_achat), 0) FROM motos m WHERE m.facture_fournisseur_id = factures_fournisseur.id AND m.deleted_at IS NULL) - factures_fournisseur.remise';
    }

    /** Expression SQL : somme des versements. */
    public static function sqlVerse(): string
    {
        return '(SELECT COALESCE(SUM(v.montant), 0) FROM versements_fournisseur v WHERE v.facture_fournisseur_id = factures_fournisseur.id AND v.deleted_at IS NULL)';
    }

    /** Ajoute les colonnes `total`, `verse` et `solde`. */
    public function scopeAvecTotaux(Builder $query): Builder
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('factures_fournisseur.*');
        }

        return $query
            ->selectRaw('('.self::sqlTotal().') as total')
            ->selectRaw('('.self::sqlVerse().') as verse')
            ->selectRaw('(('.self::sqlTotal().') - ('.self::sqlVerse().')) as solde');
    }

    /** Seulement les factures pas encore soldées. */
    public function scopeNonSoldees(Builder $query): Builder
    {
        return $query->whereRaw('(('.self::sqlTotal().') - ('.self::sqlVerse().')) > 0');
    }

    public function total(): int
    {
        return $this->totalCalcule('total');
    }

    public function verse(): int
    {
        return $this->totalCalcule('verse');
    }

    public function solde(): int
    {
        return $this->totalCalcule('solde');
    }

    public function estSoldee(): bool
    {
        return $this->solde() <= 0;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
