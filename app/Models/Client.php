<?php

namespace App\Models;

use App\Enums\StatutVente;
use App\Enums\TypeClient;
use App\Enums\TypePiece;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['nom', 'telephone', 'adresse', 'type', 'type_piece', 'numero_piece', 'notes'])]
class Client extends Model
{
    use Corbeille, HasFactory, Journalise;

    protected $attributes = [
        'type' => 'particulier',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeClient::class,
            'type_piece' => TypePiece::class,
        ];
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    /** Ajoute la colonne `reste_du` : ce que le client doit encore (ventes non annulées). */
    public function scopeAvecResteDu(Builder $query): Builder
    {
        return $query->addSelect(['reste_du' => self::sousRequeteResteDu()]);
    }

    /** Seulement les clients qui doivent encore de l'argent (SQL standard, sans HAVING). */
    public function scopeDoitEncore(Builder $query): Builder
    {
        return $query->where(self::sousRequeteResteDu(), '>', 0);
    }

    private static function sousRequeteResteDu(): Builder
    {
        return Vente::query()
            ->selectRaw('COALESCE(SUM('.Vente::sqlReste().'), 0)')
            ->whereColumn('ventes.client_id', 'clients.id')
            ->where('ventes.statut', '!=', StatutVente::Annulee->value);
    }

    public function resteDu(): int
    {
        if (array_key_exists('reste_du', $this->attributes)) {
            return (int) $this->attributes['reste_du'];
        }

        return (int) static::query()->avecResteDu()->whereKey($this->getKey())->value('reste_du');
    }

    /** Total que tous les clients doivent encore. */
    public static function totalDu(): int
    {
        return (int) DB::query()
            ->fromSub(static::query()->select('id')->avecResteDu(), 'c')
            ->sum('reste_du');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
