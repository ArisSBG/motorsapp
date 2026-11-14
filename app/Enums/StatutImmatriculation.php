<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Parcours des papiers (cahier des charges, 4.5) : provisoire remis sur place,
 * récépissé vers 3 mois, carte grise et plaque en 2 à 6 mois.
 */
enum StatutImmatriculation: string implements HasColor, HasLabel
{
    case DossierEnCours = 'dossier_en_cours';
    case ProvisoireRecu = 'provisoire_recu';
    case RecepisseRecu = 'recepisse_recu';
    case CarteGriseRecue = 'carte_grise_recue';

    public function getLabel(): string
    {
        return match ($this) {
            self::DossierEnCours => 'Dossier en cours',
            self::ProvisoireRecu => 'Provisoire reçu',
            self::RecepisseRecu => 'Récépissé reçu',
            self::CarteGriseRecue => 'Carte grise reçue',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DossierEnCours => 'gray',
            self::ProvisoireRecu => 'warning',
            self::RecepisseRecu => 'info',
            self::CarteGriseRecue => 'success',
        };
    }

    public function rang(): int
    {
        return array_search($this, self::cases(), true);
    }
}
