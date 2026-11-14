<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Étapes de vie d'une moto (cahier des charges, 4.1).
 * Le lot 1 utilise « En stock » ; les autres statuts arrivent avec les ventes et les papiers.
 */
enum StatutMoto: string implements HasColor, HasLabel
{
    case EnStock = 'en_stock';
    case Reservee = 'reservee';
    case Vendue = 'vendue';
    case ImmatriculationEnCours = 'immatriculation_en_cours';
    case PapiersRecus = 'papiers_recus';
    case Livree = 'livree';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnStock => 'En stock',
            self::Reservee => 'Réservée',
            self::Vendue => 'Vendue',
            self::ImmatriculationEnCours => 'Immatriculation en cours',
            self::PapiersRecus => 'Papiers reçus',
            self::Livree => 'Livrée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnStock => 'success',
            self::Reservee => 'warning',
            self::Vendue, self::ImmatriculationEnCours => 'info',
            self::PapiersRecus => 'primary',
            self::Livree => 'gray',
        };
    }

    /** Une moto est « disponible » tant qu'elle n'est ni réservée ni vendue. */
    public function estDisponible(): bool
    {
        return $this === self::EnStock;
    }
}
