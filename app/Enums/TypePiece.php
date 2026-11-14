<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypePiece: string implements HasLabel
{
    case Cnib = 'cnib';
    case Passeport = 'passeport';
    case Permis = 'permis';
    case Autre = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cnib => 'CNIB',
            self::Passeport => 'Passeport',
            self::Permis => 'Permis de conduire',
            self::Autre => 'Autre',
        };
    }
}
