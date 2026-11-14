<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Auth\Login;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConnexionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Filament::setCurrentPanel('gestion');

        $this->admin = User::factory()->create(['email' => 'admin@test.bf', 'password' => 'BonMotDePasse1']);
        $this->admin->assignRole(Role::Admin->value);
    }

    private function essayer(string $motDePasse)
    {
        return Livewire::test(Login::class)
            ->set('data.email', 'admin@test.bf')
            ->set('data.password', $motDePasse)
            ->call('authenticate');
    }

    public function test_connexion_reussie_avec_le_bon_mot_de_passe(): void
    {
        $this->essayer('BonMotDePasse1')->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_compte_bloque_15_minutes_apres_5_essais_rates(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->essayer('mauvais');
        }

        // 6e essai, même avec le bon mot de passe : refusé
        $this->essayer('BonMotDePasse1');
        $this->assertGuest();

        // Toujours bloqué après 10 minutes
        $this->travel(10)->minutes();
        $this->essayer('BonMotDePasse1');
        $this->assertGuest();

        // Débloqué après 15 minutes
        $this->travel(6)->minutes();
        $this->essayer('BonMotDePasse1');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $this->admin->update(['actif' => false]);

        $this->essayer('BonMotDePasse1')->assertHasFormErrors(['email']);
        $this->assertGuest();
    }
}
