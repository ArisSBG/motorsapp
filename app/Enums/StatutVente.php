<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutVente: string implements HasColor, HasLabel
{
    /** Moto réservée avec un acompte, pas encore vendue. */
    case Reservation = 'reservation';
    /** Vendue, il reste de l'argent à payer. */
    case EnCours = 'en_cours';
    /** Entièrement payée : le bénéfice est compté. */
    case Soldee = 'soldee';
    case Annulee = 'annulee';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reservation => 'Réservation',
            self::EnCours => 'Reste à payer',
            self::Soldee => 'Soldée',
            self::Annulee => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reservation => 'warning',
            self::EnCours => 'danger',
            self::Soldee => 'success',
            self::Annulee => 'gray',
        };
    }

    public function statutMoto(): StatutMoto
    {
        return match ($this) {
            self::Reservation => StatutMoto::Reservee,
            self::EnCours, self::Soldee => StatutMoto::Vendue,
            self::Annulee => StatutMoto::EnStock,
        };
    }
}
