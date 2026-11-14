<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeClient: string implements HasLabel
{
    case Particulier = 'particulier';
    case Entreprise = 'entreprise';
    case Revendeur = 'revendeur';

    public function getLabel(): string
    {
        return match ($this) {
            self::Particulier => 'Particulier',
            self::Entreprise => 'Entreprise / organisation',
            self::Revendeur => 'Revendeur',
        };
    }
}
