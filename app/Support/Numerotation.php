<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Numéros automatiques par année : BURKINA-MOTORS-2026-0001, REC-2026-0001…
 * À appeler dans une transaction : la ligne la plus récente est verrouillée
 * pour que deux personnes n'obtiennent pas le même numéro.
 */
final class Numerotation
{
    /**
     * @param  class-string<Model>  $modele
     */
    public static function suivant(string $modele, string $colonne, string $prefixe, ?int $annee = null): string
    {
        $annee ??= (int) now()->year;
        $debut = "{$prefixe}-{$annee}-";

        $dernier = DB::table((new $modele)->getTable())
            ->where($colonne, 'like', $debut.'%')
            ->lockForUpdate()
            ->orderByDesc($colonne)
            ->value($colonne);

        $rang = $dernier ? ((int) substr($dernier, strlen($debut))) + 1 : 1;

        return $debut.str_pad((string) $rang, 4, '0', STR_PAD_LEFT);
    }
}
