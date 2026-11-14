<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Gestion\Resources\FactureFournisseurs\Pages\ViewFactureFournisseur;
use App\Filament\Gestion\Resources\FactureFournisseurs\RelationManagers\MotosRelationManager;
use App\Filament\Gestion\Resources\FactureFournisseurs\RelationManagers\VersementsRelationManager;
use App\Filament\Gestion\Resources\Motos\Pages\CreateMoto;
use App\Filament\Gestion\Resources\Motos\Pages\ListMotos;
use App\Filament\Gestion\Resources\Users\Pages\CreateUser;
use App\Filament\Gestion\Resources\Users\Pages\EditUser;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\Moto;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EcransLot1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Filament::setCurrentPanel('gestion');
    }

    private function connecter(Role $role): User
    {
        $user = tap(User::factory()->create())->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_l_admin_ouvre_tous_les_ecrans_du_lot_1(): void
    {
        $this->connecter(Role::Admin);
        $facture = FactureFournisseur::factory()->create();
        $moto = Moto::factory()->pourFacture($facture)->create();

        foreach ([
            '/gestion', '/gestion/motos', '/gestion/motos/create', "/gestion/motos/{$moto->id}", "/gestion/motos/{$moto->id}/edit",
            '/gestion/fournisseurs', '/gestion/fournisseurs/create', "/gestion/fournisseurs/{$facture->fournisseur_id}",
            '/gestion/factures-fournisseur', '/gestion/factures-fournisseur/create', "/gestion/factures-fournisseur/{$facture->id}",
            '/gestion/utilisateurs', '/gestion/utilisateurs/create', '/gestion/journal', '/gestion/sauvegardes',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_le_vendeur_voit_le_stock_sans_les_prix_ni_les_achats(): void
    {
        $this->connecter(Role::Vendeur);
        $moto = Moto::factory()->create(['prix_achat' => 777_000]);

        $this->get('/gestion/motos')->assertOk()->assertSee($moto->chassis)->assertDontSee('777.000');
        $this->get("/gestion/motos/{$moto->id}")->assertOk()->assertDontSee('777.000');
        $this->get('/gestion/motos/create')->assertForbidden();
        $this->get('/gestion/fournisseurs')->assertForbidden();
        $this->get('/gestion/factures-fournisseur')->assertForbidden();
        $this->get('/gestion/utilisateurs')->assertForbidden();
        $this->get('/gestion/journal')->assertForbidden();
        $this->get('/gestion/sauvegardes')->assertForbidden();

        Livewire::test(ListMotos::class)->assertTableColumnHidden('prix_achat');
    }

    public function test_l_onglet_en_stock_cache_les_motos_vendues(): void
    {
        $this->connecter(Role::Magasinier);
        $enStock = Moto::factory()->create();
        $vendue = Moto::factory()->create(['statut' => 'vendue']);

        Livewire::test(ListMotos::class)
            ->assertCanSeeTableRecords([$enStock])
            ->assertCanNotSeeTableRecords([$vendue]);
    }

    public function test_la_double_saisie_du_chassis_doit_correspondre(): void
    {
        $this->connecter(Role::Admin);
        $fournisseur = Fournisseur::factory()->create();

        $donnees = [
            'chassis' => 'rlcue 123456', 'marque' => 'Yamaha', 'modele' => 'Sirius 110',
            'fournisseur_id' => $fournisseur->id, 'prix_achat' => 700_000, 'date_reception' => now()->toDateString(),
            'statut' => 'en_stock',
        ];

        Livewire::test(CreateMoto::class)
            ->fillForm([...$donnees, 'chassis_confirmation' => 'RLCUE123465'])
            ->call('create')
            ->assertHasFormErrors(['chassis_confirmation']);
        $this->assertDatabaseCount('motos', 0);

        Livewire::test(CreateMoto::class)
            ->fillForm([...$donnees, 'chassis_confirmation' => 'RLCUE-123456'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('motos', ['chassis' => 'RLCUE123456']);

        // Même châssis une 2e fois : refusé avec un message clair
        Livewire::test(CreateMoto::class)
            ->fillForm([...$donnees, 'chassis' => 'RLCUE123456', 'chassis_confirmation' => 'RLCUE123456'])
            ->call('create')
            ->assertHasFormErrors(['chassis']);
    }

    public function test_ajouter_une_moto_depuis_la_facture_reprend_le_fournisseur(): void
    {
        $this->connecter(Role::Admin);
        $facture = FactureFournisseur::factory()->create(['montant_transport' => 5_000]);

        Livewire::test(MotosRelationManager::class, ['ownerRecord' => $facture, 'pageClass' => ViewFactureFournisseur::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'chassis' => 'MH3SE000111', 'chassis_confirmation' => 'MH3SE000111',
                'marque' => 'Apsonic', 'modele' => 'Scooter', 'prix_achat' => 650_000,
                'date_reception' => now()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $moto = Moto::firstWhere('chassis', 'MH3SE000111');
        $this->assertSame($facture->fournisseur_id, $moto->fournisseur_id);
        $this->assertSame(5_000, $moto->totalFrais());
    }

    public function test_un_versement_ne_peut_pas_depasser_le_reste_a_payer(): void
    {
        $this->connecter(Role::Admin);
        $facture = FactureFournisseur::factory()->create();
        Moto::factory()->pourFacture($facture)->create(['prix_achat' => 700_000]);

        $rm = fn () => Livewire::test(VersementsRelationManager::class, ['ownerRecord' => $facture, 'pageClass' => ViewFactureFournisseur::class]);

        $rm()->callAction(TestAction::make(CreateAction::class)->table(), [
            'date_versement' => now()->toDateString(), 'montant' => 800_000, 'mode' => 'especes',
        ])->assertHasFormErrors(['montant']);

        $rm()->callAction(TestAction::make(CreateAction::class)->table(), [
            'date_versement' => now()->toDateString(), 'montant' => 700_000, 'mode' => 'especes',
        ])->assertHasNoFormErrors();

        $this->assertTrue($facture->fresh()->estSoldee());
    }

    public function test_creer_un_compte_attribue_le_profil(): void
    {
        $this->connecter(Role::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Awa Ouédraogo', 'email' => 'awa@burkina-motors.test', 'role' => 'secretaire',
                'actif' => true, 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(User::firstWhere('email', 'awa@burkina-motors.test')->hasRole('secretaire'));
    }

    public function test_l_admin_ne_peut_pas_se_desactiver_lui_meme(): void
    {
        $admin = $this->connecter(Role::Admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['actif' => false])
            ->call('save');

        $this->assertTrue($admin->fresh()->actif);
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
