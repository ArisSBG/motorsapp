<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Auth\Login;
use App\Filament\Auth\Profil;
use App\Filament\Gestion\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Corrections de l'audit de sécurité.
 */
class SecuriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Filament::setCurrentPanel('gestion');
    }

    private function utilisateur(Role $role, array $attributs = []): User
    {
        return tap(User::factory()->create($attributs))->assignRole($role->value);
    }

    private function sessionOuverte(User $user): void
    {
        DB::table('sessions')->insert([
            'id' => str()->random(40), 'user_id' => $user->id, 'ip_address' => '41.138.10.1',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/130.0', 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
    }

    public function test_pas_de_case_se_souvenir_de_moi(): void
    {
        $this->get('/gestion/login')->assertOk()->assertDontSee('Se souvenir de moi');

        $admin = $this->utilisateur(Role::Admin, ['email' => 'a@test.bf', 'password' => 'Secret123']);
        $jetonAvant = $admin->remember_token;

        Livewire::test(Login::class)
            ->set('data.email', 'a@test.bf')->set('data.password', 'Secret123')->set('data.remember', true)
            ->call('authenticate');

        $this->assertAuthenticated();
        // Avec « se souvenir de moi », Laravel régénère ce jeton : il ne doit pas bouger
        $this->assertSame($jetonAvant, $admin->fresh()->remember_token, 'aucun cookie de connexion longue durée');
    }

    public function test_l_admin_deconnecte_tous_les_appareils_d_un_utilisateur(): void
    {
        $admin = $this->utilisateur(Role::Admin);
        $vendeur = $this->utilisateur(Role::Vendeur);
        $this->sessionOuverte($vendeur);
        $this->sessionOuverte($vendeur);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)->callTableAction('deconnecter', $vendeur)->assertHasNoTableActionErrors();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $vendeur->id)->count());
        $this->assertTrue(Activity::where('event', 'deconnexion_forcee')->exists());
    }

    public function test_desactiver_un_compte_le_deconnecte_partout(): void
    {
        $this->actingAs($this->utilisateur(Role::Admin));
        $vendeur = $this->utilisateur(Role::Vendeur);
        $this->sessionOuverte($vendeur);

        $vendeur->update(['actif' => false]);

        $this->assertSame(0, DB::table('sessions')->where('user_id', $vendeur->id)->count());
    }

    public function test_un_utilisateur_ne_change_pas_son_email_dans_le_profil(): void
    {
        $vendeur = $this->utilisateur(Role::Vendeur, ['email' => 'v@test.bf', 'name' => 'Ancien nom']);
        $this->actingAs($vendeur);

        Livewire::test(Profil::class)
            ->fillForm(['name' => 'Nouveau nom', 'email' => 'pirate@test.bf', 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $vendeur->refresh();
        $this->assertSame('Nouveau nom', $vendeur->name);
        $this->assertSame('v@test.bf', $vendeur->email);
    }

    public function test_les_connexions_et_les_echecs_sont_journalises(): void
    {
        $this->utilisateur(Role::Admin, ['email' => 'a@test.bf', 'password' => 'Secret123']);

        Livewire::test(Login::class)->set('data.email', 'a@test.bf')->set('data.password', 'mauvais')->call('authenticate');
        Livewire::test(Login::class)->set('data.email', 'a@test.bf')->set('data.password', 'Secret123')->call('authenticate');

        $echec = Activity::where('event', 'echec_connexion')->first();
        $this->assertNotNull($echec);
        $this->assertSame('a@test.bf', $echec->properties['identifiant']);
        $this->assertStringNotContainsString('mauvais', json_encode($echec->properties));
        $this->assertTrue(Activity::where('event', 'connexion')->exists());
    }

    public function test_en_tetes_de_securite(): void
    {
        $this->get('/gestion/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=()');

        $this->get('https://localhost/gestion/login')->assertHeader('Strict-Transport-Security');
    }

    public function test_derriere_cloudflare_le_https_et_l_ip_reelle_sont_reconnus(): void
    {
        config(['app.trusted_proxies' => '*']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $reponse = $this->withServerVariables(['REMOTE_ADDR' => '172.70.1.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '41.138.10.1'])
            ->get('/gestion/login');

        $reponse->assertHeader('Strict-Transport-Security');
        $this->assertSame('41.138.10.1', request()->ip());

        \Illuminate\Http\Middleware\TrustProxies::flushState();
    }

    public function test_limite_d_envoi_de_fichiers_a_20_mo_pdf_et_images(): void
    {
        $regles = config('livewire.temporary_file_upload.rules');

        $this->assertContains('max:20480', $regles);
        $this->assertNotContains('svg', config('livewire.temporary_file_upload.preview_mimes'));
    }

    public function test_les_documents_sont_limites_en_debit(): void
    {
        $route = app('router')->getRoutes()->getByName('documents.fichier');

        $this->assertContains('throttle:60,1', $route->gatherMiddleware());
    }
}
