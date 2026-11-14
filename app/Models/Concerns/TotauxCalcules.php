<?php

namespace App\Models\Concerns;

/**
 * Totaux calculés en SQL (scope `avecTotaux`) :
 * - dans une liste, ils arrivent avec la requête ;
 * - sur une fiche seule, ils sont calculés à la demande et gardés en mémoire
 *   (hors des attributs, pour ne jamais être enregistrés comme des colonnes).
 */
trait TotauxCalcules
{
    /** @var array<string, int> */
    private array $totauxEnMemoire = [];

    /** @return list<string> */
    abstract protected static function colonnesTotaux(): array;

    protected function totalCalcule(string $colonne): int
    {
        if (array_key_exists($colonne, $this->attributes)) {
            return (int) $this->attributes[$colonne];
        }

        if ($this->totauxEnMemoire === []) {
            $ligne = static::query()->withoutGlobalScopes()->avecTotaux()->whereKey($this->getKey())->first();
            foreach (static::colonnesTotaux() as $c) {
                $this->totauxEnMemoire[$c] = (int) ($ligne?->getAttributes()[$c] ?? 0);
            }
        }

        return $this->totauxEnMemoire[$colonne];
    }

    /** Oublie les totaux (après un ajout de moto, de paiement…). */
    public function rafraichirTotaux(): static
    {
        foreach (static::colonnesTotaux() as $c) {
            unset($this->attributes[$c], $this->original[$c]);
        }
        $this->totauxEnMemoire = [];

        return $this;
    }

    public function refresh()
    {
        $this->totauxEnMemoire = [];

        return parent::refresh();
    }
}
