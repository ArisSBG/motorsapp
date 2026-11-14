<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Document remis au client lors d'un retrait. */
enum DocumentRemis: string implements HasLabel
{
    case Provisoire = 'provisoire';
    case Recepisse = 'recepisse';
    case CarteGrise = 'carte_grise';
    case Plaque = 'plaque';

    public function getLabel(): string
    {
        return match ($this) {
            self::Provisoire => 'Document provisoire',
            self::Recepisse => 'Récépissé',
            self::CarteGrise => 'Carte grise définitive',
            self::Plaque => 'Plaque d’immatriculation',
        };
    }
}
