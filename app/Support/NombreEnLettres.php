<?php

namespace App\Support;

/**
 * Montant en toutes lettres pour les factures et reçus :
 * 975000 → « neuf cent soixante-quinze mille ».
 * Règles françaises : « vingt » et « cent » prennent un s seulement en fin de nombre,
 * « mille » est invariable, « un » devant « mille » disparaît.
 */
final class NombreEnLettres
{
    private const UNITES = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const DIZAINES = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante', 8 => 'quatre-vingt'];

    public static function convertir(int $nombre): string
    {
        if ($nombre === 0) {
            return 'zéro';
        }
        if ($nombre < 0) {
            return 'moins '.self::convertir(-$nombre);
        }

        $parties = [];
        foreach ([1_000_000_000 => 'milliard', 1_000_000 => 'million'] as $valeur => $nom) {
            if ($nombre >= $valeur) {
                $n = intdiv($nombre, $valeur);
                $parties[] = self::moinsDeMille($n, false).' '.$nom.($n > 1 ? 's' : '');
                $nombre %= $valeur;
            }
        }

        if ($nombre >= 1000) {
            $n = intdiv($nombre, 1000);
            $parties[] = $n === 1 ? 'mille' : self::moinsDeMille($n, false).' mille';
            $nombre %= 1000;
        }

        if ($nombre > 0) {
            $parties[] = self::moinsDeMille($nombre, true);
        }

        return implode(' ', $parties);
    }

    /** @param bool $final le groupe termine le nombre (accord de « cent » et « quatre-vingt ») */
    private static function moinsDeMille(int $n, bool $final): string
    {
        $centaines = intdiv($n, 100);
        $reste = $n % 100;
        $texte = '';

        if ($centaines > 0) {
            $texte = $centaines === 1 ? 'cent' : self::UNITES[$centaines].' cent';
            if ($reste === 0 && $centaines > 1 && $final) {
                $texte .= 's';
            }
        }

        if ($reste > 0) {
            $texte .= ($texte ? ' ' : '').self::moinsDeCent($reste, $final);
        }

        return $texte;
    }

    private static function moinsDeCent(int $n, bool $final): string
    {
        if ($n < 20) {
            return self::UNITES[$n];
        }

        $d = intdiv($n, 10);
        $u = $n % 10;

        // 70-79 et 90-99 : soixante-dix…, quatre-vingt-dix…
        if ($d === 7 || $d === 9) {
            $base = self::DIZAINES[$d - 1];
            $liaison = ($d === 7 && $u === 1) ? ' et ' : '-';

            return $base.$liaison.self::UNITES[10 + $u];
        }

        $base = self::DIZAINES[$d];

        if ($u === 0) {
            return $base.($d === 8 && $final ? 's' : '');
        }
        if ($u === 1 && $d !== 8) {
            return $base.' et un';
        }

        return $base.'-'.self::UNITES[$u];
    }

    /** « neuf cent soixante-quinze mille francs CFA » */
    public static function francsCfa(int $montant): string
    {
        return self::convertir($montant).' francs CFA';
    }
}
