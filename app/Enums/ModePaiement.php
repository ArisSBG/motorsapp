<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModePaiement: string implements HasLabel
{
    case Especes = 'especes';
    case Virement = 'virement';
    case MobileMoney = 'mobile_money';
    case Cheque = 'cheque';

    public function getLabel(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Virement => 'Virement bancaire',
            self::MobileMoney => 'Mobile Money',
            self::Cheque => 'Chèque',
        };
    }
}
