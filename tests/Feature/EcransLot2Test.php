<?php

namespace Tests\Feature;

use App\Enums\ModePaiement;
use App\Enums\Role;
use App\Enums\StatutMoto;
use App\Enums\StatutVente;
use App\Filament\Gestion\Resources\Ventes\Pages\CreateVente;
use App\Filament\Gestion\Resources\Ventes\Pages\ListVentes;
use App\Filament\Gestion\Resources\Ventes\Pages\ViewVente;
use App\Filament\Gestion\Resources\Ventes\RelationManagers\PaiementsRelationManager;
use App\Models\Client;
use App\Models\Moto;
use App\Models\User;
use App\Models\Vente;
use Database\Seeders\RolesSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EcransLot2Test extends TestCase
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

    private function venteAvecPaiement(): Vente
    {
        $vente = Vente::factory()->avecMoto(Moto::factory()->create(['prix_achat' => 800_000]), prix: 900_000)->create();
        $vente->paiements()->create(['date_paiement' => now(), 'montant' => 400_000, 'mode' => ModePaiement::Especes]);

        return $vente->fresh();
    }

    public function test_l_admin_ouvre_les_ecrans_et_les_documents(): void
    {
        $this->connecter(Role::Admin);
        $vente = $this->venteAvecPaiement();

        foreach (['/gestion/ventes', '/gestion/ventes/create', "/gestion/ventes/{$vente->id}", "/gestion/ventes/{$vente->id}/edit",
            '/gestion/clients', '/gestion/clients/create', "/gestion/clients/{$vente->client_id}"] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get("/gestion/ventes/{$vente->id}")->assertSee('Bénéfice');

        $pdf = $this->get(route('documents.facture-vente', $vente));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->get(route('documents.recu', $vente->paiements->first()))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_le_vendeur_vend_et_encaisse_mais_ne_modifie_pas_et_ne_voit_pas_le_benefice(): void
    {
        $this->connecter(Role::Vendeur);
        $vente = $this->venteAvecPaiement();

        $this->get('/gestion/ventes/create')->assertOk();
        $this->get("/gestion/ventes/{$vente->id}")->assertOk()->assertDontSee('Bénéfice')->assertDontSee('800.000');
        $this->get("/gestion/ventes/{$vente->id}/edit")->assertForbidden();
        $this->get(route('documents.facture-vente', $vente))->assertOk();

        Livewire::test(ListVentes::class)->assertTableColumnHidden('benefice');
    }

    public function test_le_magasinier_et_la_secretaire_n_ont_pas_acces_aux_ventes(): void
    {
        $vente = $this->venteAvecPaiement();

        foreach ([Role::Magasinier, Role::Secretaire] as $role) {
            $this->connecter($role);
            $this->get('/gestion/ventes')->assertForbidden();
            $this->get(route('documents.facture-vente', $vente))->assertForbidden();
        }
    }

    public function test_un_visiteur_ne_peut_pas_ouvrir_une_facture(): void
    {
        $vente = $this->venteAvecPaiement();

        $this->get(route('documents.facture-vente', $vente))->assertRedirect('/gestion/login');
    }

    public function test_creer_une_vente_avec_un_acompte(): void
    {
        $vendeur = $this->connecter(Role::Vendeur);
        $client = Client::factory()->create();
        $moto = Moto::factory()->create();
        $undo = Repeater::fake();

        Livewire::test(CreateVente::class)
            ->fillForm([
                'client_id' => $client->id,
                'date_vente' => now()->toDateString(),
                'lignes' => [['moto_id' => $moto->id, 'prix_vente' => 975_000, 'frais_immatriculation' => 41_000, 'cout_casque' => 6_500]],
                'acompte' => 500_000,
                'acompte_mode' => 'mobile_money',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $undo();

        $vente = Vente::firstOrFail();
        $this->assertSame(StatutVente::EnCours, $vente->statut);
        $this->assertSame($vendeur->id, $vente->user_id);
        $this->assertSame(475_000, $vente->reste());
        $this->assertSame(StatutMoto::Vendue, $moto->fresh()->statut);
        $this->assertSame(ModePaiement::MobileMoney, $vente->paiements()->first()->mode);
        $this->assertNotNull($vente->paiements()->first()->numero_recu);
    }

    public function test_une_moto_deja_vendue_ne_peut_pas_etre_revendue(): void
    {
        $this->connecter(Role::Vendeur);
        $moto = Moto::factory()->create(['statut' => StatutMoto::Vendue]);
        $undo = Repeater::fake();

        Livewire::test(CreateVente::class)
            ->fillForm([
                'client_id' => Client::factory()->create()->id,
                'date_vente' => now()->toDateString(),
                'lignes' => [['moto_id' => $moto->id, 'prix_vente' => 900_000]],
            ])
            ->call('create')
            ->assertHasFormErrors(['lignes.0.moto_id']);
        $undo();

        $this->assertDatabaseCount('ventes', 0);
    }

    public function test_l_acompte_ne_peut_pas_depasser_le_total(): void
    {
        $this->connecter(Role::Vendeur);
        $undo = Repeater::fake();

        Livewire::test(CreateVente::class)
            ->fillForm([
                'client_id' => Client::factory()->create()->id,
                'date_vente' => now()->toDateString(),
                'lignes' => [['moto_id' => Moto::factory()->create()->id, 'prix_vente' => 900_000]],
                'acompte' => 950_000,
            ])
            ->call('create')
            ->assertHasFormErrors(['acompte']);
        $undo();
    }

    public function test_une_reservation_reserve_la_moto(): void
    {
        $this->connecter(Role::Vendeur);
        $moto = Moto::factory()->create();
        $undo = Repeater::fake();

        Livewire::test(CreateVente::class)
            ->fillForm([
                'client_id' => Client::factory()->create()->id,
                'date_vente' => now()->toDateString(),
                'reservation' => true,
                'lignes' => [['moto_id' => $moto->id, 'prix_vente' => 900_000]],
                'acompte' => 100_000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $undo();

        $this->assertSame(StatutVente::Reservation, Vente::first()->statut);
        $this->assertSame(StatutMoto::Reservee, $moto->fresh()->statut);
    }

    public function test_l_admin_annule_une_vente_et_la_moto_revient_en_stock(): void
    {
        $this->connecter(Role::Admin);
        $vente = $this->venteAvecPaiement();
        $moto = $vente->lignes->first()->moto;

        Livewire::test(ViewVente::class, ['record' => $vente->getRouteKey()])
            ->callAction('annuler', ['motif_annulation' => 'Échange contre une autre marque'])
            ->assertHasNoActionErrors();

        $this->assertSame(StatutVente::Annulee, $vente->fresh()->statut);
        $this->assertSame(StatutMoto::EnStock, $moto->fresh()->statut);
    }

    public function test_le_vendeur_ne_voit_pas_le_bouton_annuler(): void
    {
        $this->connecter(Role::Vendeur);
        $vente = $this->venteAvecPaiement();

        Livewire::test(ViewVente::class, ['record' => $vente->getRouteKey()])
            ->assertActionHidden('annuler')
            ->assertActionVisible('facture');
    }

    public function test_un_paiement_ne_peut_pas_depasser_le_reste(): void
    {
        $this->connecter(Role::Vendeur);
        $vente = $this->venteAvecPaiement(); // reste 500.000

        $rm = fn () => Livewire::test(PaiementsRelationManager::class, ['ownerRecord' => $vente, 'pageClass' => ViewVente::class]);

        $rm()->callAction(TestAction::make(CreateAction::class)->table(), [
            'date_paiement' => now()->toDateString(), 'montant' => 600_000, 'mode' => 'especes',
        ])->assertHasFormErrors(['montant']);

        $rm()->callAction(TestAction::make(CreateAction::class)->table(), [
            'date_paiement' => now()->toDateString(), 'montant' => 500_000, 'mode' => 'especes',
        ])->assertHasNoFormErrors();

        $this->assertSame(StatutVente::Soldee, $vente->fresh()->statut);
    }
}
