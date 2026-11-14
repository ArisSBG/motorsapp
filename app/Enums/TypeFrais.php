<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeFrais: string implements HasLabel
{
    case Transport = 'transport';
    case Reparation = 'reparation';
    case Capots = 'capots';
    case Autre = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::Transport => 'Transport',
            self::Reparation => 'Réparation / préparation',
            self::Capots => 'Changement de capots',
            self::Autre => 'Autre',
        };
    }

    /** Types saisis à la main (le transport est calculé depuis la facture). */
    public static function saisissables(): array
    {
        return collect([self::Reparation, self::Capots, self::Autre])
            ->mapWithKeys(fn (self $t) => [$t->value => $t->getLabel()])->all();
    }
}
