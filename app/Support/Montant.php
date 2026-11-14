<?php

namespace App\Support;

/**
 * Affichage des montants au format demandé par le client : 975.000 FCFA.
 */
final class Montant
{
    public static function format(int|float|string|null $montant, bool $devise = true): string
    {
        $texte = number_format((int) round((float) $montant), 0, ',', '.');

        return $devise ? $texte.' FCFA' : $texte;
    }
}
