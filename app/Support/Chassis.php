<?php

namespace App\Support;

/**
 * Numéro de châssis : majuscules, sans espaces ni tirets.
 * Évite qu'un même châssis soit enregistré deux fois sous deux écritures.
 */
final class Chassis
{
    public const REGLE = '/^[A-Z0-9]{5,30}$/';

    public static function normaliser(?string $chassis): ?string
    {
        if ($chassis === null) {
            return null;
        }

        return strtoupper(preg_replace('/[\s\-\.]/', '', $chassis));
    }
}
