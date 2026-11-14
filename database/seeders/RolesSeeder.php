<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Profils et droits du cahier des charges (section 3).
 * Les droits liés aux écrans (motos, ventes…) seront ajoutés avec chaque lot.
 */
class RolesSeeder extends Seeder
{
    /** droit => profils qui l'ont (l'administrateur a tout) */
    public const DROITS = [
        'voir_prix_achat' => [],
        'voir_benefice' => [],
        'gerer_achats' => [],
        'enregistrer_vente' => [RoleEnum::Vendeur],
        'modifier_vente' => [],
        'enregistrer_paiement_client' => [RoleEnum::Vendeur],
        'suivre_papiers' => [RoleEnum::Secretaire],
        'ajouter_document' => [RoleEnum::Secretaire, RoleEnum::Vendeur],
        'supprimer_document' => [],
        'voir_stock' => [RoleEnum::Vendeur, RoleEnum::Secretaire, RoleEnum::Magasinier],
        'voir_tableau_de_bord' => [],
        'exporter' => [],
        'gerer_sauvegardes' => [],
        'gerer_comptes' => [],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        foreach (self::DROITS as $droit => $profils) {
            Permission::findOrCreate($droit, 'web');
        }

        Role::findByName(RoleEnum::Admin->value, 'web')->syncPermissions(array_keys(self::DROITS));

        foreach ([RoleEnum::Vendeur, RoleEnum::Secretaire, RoleEnum::Magasinier] as $role) {
            $droits = collect(self::DROITS)
                ->filter(fn (array $profils) => in_array($role, $profils, true))
                ->keys();
            Role::findByName($role->value, 'web')->syncPermissions($droits);
        }
    }
}
