<?php

namespace App\Support;

/**
 * Numéros burkinabè : 8 chiffres (ex. 72 72 31 31), indicatif +226.
 */
final class Telephone
{
    public static function international(string $numero): string
    {
        $chiffres = preg_replace('/\D/', '', $numero);

        if (str_starts_with($chiffres, '00')) {
            $chiffres = substr($chiffres, 2);
        }

        return strlen($chiffres) === 8 ? '226'.$chiffres : $chiffres;
    }

    public static function lienWhatsApp(string $numero, string $message): string
    {
        return 'https://wa.me/'.self::international($numero).'?text='.rawurlencode($message);
    }
}
