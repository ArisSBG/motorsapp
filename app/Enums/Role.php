<?php

namespace App\Enums;

/**
 * Les quatre profils prévus au cahier des charges (section 3).
 */
enum Role: string
{
    case Admin = 'admin';
    case Vendeur = 'vendeur';
    case Secretaire = 'secretaire';
    case Magasinier = 'magasinier';

    public function libelle(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Vendeur => 'Vendeur',
            self::Secretaire => 'Secrétaire',
            self::Magasinier => 'Magasinier',
        };
    }

    /** Espaces (panneaux Filament) auxquels le profil a accès. */
    public function panneaux(): array
    {
        return match ($this) {
            self::Admin => ['gestion', 'papiers'],
            self::Vendeur, self::Magasinier => ['gestion'],
            self::Secretaire => ['papiers'],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->libelle()])->all();
    }
}
