<?php

namespace App\Models;

use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['nom', 'telephone', 'adresse', 'piece_identite', 'notes'])]
class Fournisseur extends Model
{
    use Corbeille, HasFactory, Journalise;

    public function factures(): HasMany
    {
        return $this->hasMany(FactureFournisseur::class);
    }

    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class);
    }

    /**
     * Ajoute la colonne `reste_a_payer` (factures − remises − versements), calculée en SQL.
     */
    public function scopeAvecResteAPayer(Builder $query): Builder
    {
        return $query->addSelect([
            'reste_a_payer' => FactureFournisseur::query()
                ->selectRaw('COALESCE(SUM(('.FactureFournisseur::sqlTotal().') - ('.FactureFournisseur::sqlVerse().')), 0)')
                ->whereColumn('factures_fournisseur.fournisseur_id', 'fournisseurs.id'),
        ]);
    }

    /** Reste à payer à ce fournisseur, en FCFA. */
    public function resteAPayer(): int
    {
        if (array_key_exists('reste_a_payer', $this->attributes)) {
            return (int) $this->attributes['reste_a_payer'];
        }

        return (int) static::query()->avecResteAPayer()->whereKey($this->getKey())->value('reste_a_payer');
    }

    /** Total dû à tous les fournisseurs, en FCFA. */
    public static function totalDu(): int
    {
        return (int) DB::query()
            ->fromSub(static::query()->select('id')->avecResteAPayer(), 'f')
            ->sum('reste_a_payer');
    }
}
