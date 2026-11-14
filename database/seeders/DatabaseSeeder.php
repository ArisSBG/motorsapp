<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        // Premier compte administrateur : identifiants lus dans .env (jamais dans le code)
        $email = env('ADMIN_EMAIL');
        $motDePasse = env('ADMIN_PASSWORD');

        if (! $email || ! $motDePasse) {
            throw new RuntimeException('Renseigner ADMIN_EMAIL et ADMIN_PASSWORD dans .env avant de lancer le seeder.');
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Administrateur', 'password' => $motDePasse, 'actif' => true],
        );
        $admin->syncRoles([Role::Admin->value]);

        // Comptes de démonstration, uniquement en local (un par profil)
        if (app()->isLocal()) {
            foreach ([Role::Vendeur, Role::Secretaire, Role::Magasinier] as $role) {
                User::updateOrCreate(
                    ['email' => $role->value.'@burkina-motors.test'],
                    ['name' => $role->libelle().' (démo)', 'password' => $motDePasse, 'actif' => true],
                )->syncRoles([$role->value]);
            }
        }
    }
}
