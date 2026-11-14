<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccesEspacesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function utilisateur(Role $role, bool $actif = true): User
    {
        return tap(User::factory()->create(['actif' => $actif]))->assignRole($role->value);
    }

    public static function accesAttendus(): array
    {
        // profil, accès Gestion, accès Papiers (cahier des charges, section 3)
        return [
            'admin' => [Role::Admin, 200, 200],
            'vendeur' => [Role::Vendeur, 200, 403],
            'secretaire' => [Role::Secretaire, 403, 200],
            'magasinier' => [Role::Magasinier, 200, 403],
        ];
    }

    #[DataProvider('accesAttendus')]
    public function test_chaque_profil_accede_seulement_a_ses_espaces(Role $role, int $gestion, int $papiers): void
    {
        $user = $this->utilisateur($role);

        $this->actingAs($user)->get('/gestion')->assertStatus($gestion);
        $this->actingAs($user)->get('/papiers')->assertStatus($papiers);
    }

    public function test_un_compte_desactive_ne_peut_plus_entrer(): void
    {
        $user = $this->utilisateur(Role::Admin, actif: false);

        $this->actingAs($user)->get('/gestion')->assertForbidden();
        $this->actingAs($user)->get('/papiers')->assertForbidden();
    }

    public function test_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get('/')->assertRedirect('/gestion/login');
        $this->get('/gestion')->assertRedirect('/gestion/login');
        $this->get('/papiers')->assertRedirect('/papiers/login');
    }

    public function test_seul_l_admin_voit_prix_achat_et_benefice(): void
    {
        $this->assertTrue($this->utilisateur(Role::Admin)->can('voir_prix_achat'));
        $this->assertTrue($this->utilisateur(Role::Admin)->can('voir_benefice'));

        foreach ([Role::Vendeur, Role::Secretaire, Role::Magasinier] as $role) {
            $user = $this->utilisateur($role);
            $this->assertFalse($user->can('voir_prix_achat'), $role->value);
            $this->assertFalse($user->can('voir_benefice'), $role->value);
        }
    }
}
