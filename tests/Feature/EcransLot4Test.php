<?php

namespace Tests\Feature;

use App\Enums\ModePaiement;
use App\Enums\Role;
use App\Filament\Gestion\Pages\Rapports;
use App\Filament\Gestion\Pages\TableauDeBord;
use App\Filament\Gestion\Widgets\ApercuStock;
use App\Filament\Gestion\Widgets\ChiffresDuMois;
use App\Filament\Gestion\Widgets\GraphiqueBeneficeParMois;
use App\Filament\Gestion\Widgets\GraphiqueVentesParMois;
use App\Http\Controllers\RapportController;
use App\Models\Moto;
use App\Models\ObjectifVente;
use App\Models\User;
use App\Models\Vente;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EcransLot4Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Filament::setCurrentPanel('gestion');
        $this->travelTo('2026-10-20');
    }

    private function connecter(Role $role): User
    {
        $user = tap(User::factory()->create())->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    private function venteSoldee(string $date, int $prix = 900_000): Vente
    {
        $vente = Vente::factory()
            ->avecMoto(Moto::factory()->create(['prix_achat' => 800_000]), prix: $prix, immatriculation: 0, casque: 0)
            ->create(['date_vente' => $date]);
        $vente->paiements()->create(['date_paiement' => $date, 'montant' => $prix, 'mode' => ModePaiement::Especes]);

        return $vente;
    }

    public function test_les_chiffres_du_mois_et_la_comparaison(): void
    {
        $this->connecter(Role::Admin);
        $this->venteSoldee('2026-10-05', 1_000_000);   // octobre : CA 1.000.000, bénéfice 200.000
        $this->venteSoldee('2026-09-10', 900_000);     // septembre : CA 900.000
        ObjectifVente::create(['annee' => 2026, 'mois' => 10, 'nombre_motos' => 2]);

        Livewire::test(ChiffresDuMois::class, ['pageFilters' => ['mois' => 10, 'annee' => 2026]])
            ->assertSee('Chiffres de octobre 2026')
            ->assertSee('1.000.000 FCFA')
            ->assertSee('200.000 FCFA')
            ->assertSee('+11 % vs mois précédent')
            ->assertSee('1 / 2 motos')
            ->assertSee('50 % atteint');
    }

    public function test_les_graphiques_s_affichent(): void
    {
        $this->connecter(Role::Admin);
        $this->venteSoldee('2026-03-05');

        Livewire::test(GraphiqueBeneficeParMois::class, ['pageFilters' => ['annee' => 2026]])->assertOk()->assertSee('Bénéfice par mois — 2026');
        Livewire::test(GraphiqueVentesParMois::class, ['pageFilters' => ['annee' => 2026]])->assertOk()->assertSee('Motos vendues par mois — 2026');
    }

    public function test_le_vendeur_ne_voit_que_la_situation_du_stock(): void
    {
        $this->connecter(Role::Vendeur);

        $this->get('/gestion')->assertOk();
        $this->assertFalse(ChiffresDuMois::canView());
        $this->assertFalse(GraphiqueBeneficeParMois::canView());
        $this->assertTrue(ApercuStock::canView());

        Livewire::test(ApercuStock::class)->assertSee('Motos en stock')->assertDontSee('Total dû aux fournisseurs');
    }

    public function test_l_admin_fixe_l_objectif_du_mois(): void
    {
        $this->connecter(Role::Admin);

        Livewire::test(TableauDeBord::class)
            ->set('filters.mois', 11)
            ->set('filters.annee', 2026)
            ->callAction('objectif', ['nombre_motos' => 20])
            ->assertHasNoActionErrors();

        $this->assertSame(20, ObjectifVente::pour(2026, 11));
    }

    public function test_l_admin_ouvre_tous_les_rapports(): void
    {
        $this->connecter(Role::Admin);
        $this->venteSoldee('2026-10-05');
        Vente::factory()->avecMoto()->create(); // client qui doit encore
        Moto::factory()->count(2)->create();    // stock

        $this->get('/gestion/rapports')->assertOk()->assertSee('Bénéfice par moto')->assertSee('Situation des fournisseurs');

        foreach (array_keys(RapportController::RAPPORTS) as $rapport) {
            $this->get(route('documents.rapport', ['rapport' => $rapport, 'du' => '2026-10-01', 'au' => '2026-10-31']))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }

        $this->get(route('documents.rapport', ['rapport' => 'inconnu']))->assertNotFound();
    }

    public function test_le_vendeur_imprime_le_stock_mais_pas_les_rapports_financiers(): void
    {
        $this->connecter(Role::Vendeur);

        $this->get('/gestion/rapports')->assertOk()->assertSee('Stock actuel')->assertDontSee('Bénéfice par moto');
        $this->get(route('documents.rapport', 'stock'))->assertOk();

        foreach (['benefices', 'clients', 'fournisseurs', 'ventes', 'sans-papiers'] as $rapport) {
            $this->get(route('documents.rapport', $rapport))->assertForbidden();
        }
    }

    public function test_le_stock_imprime_par_le_vendeur_ne_montre_pas_les_prix(): void
    {
        $moto = Moto::factory()->create(['prix_achat' => 777_000]);
        $donnees = ['motos' => Moto::query()->avecFrais()->with('fournisseur')->get(), 'titre' => 'Stock actuel', 'periode' => 'au 20/10/2026'];

        $sansPrix = view('rapports.stock', [...$donnees, 'avecPrix' => false])->render();
        $avecPrix = view('rapports.stock', [...$donnees, 'avecPrix' => true])->render();

        $this->assertStringContainsString($moto->chassis, $sansPrix);
        $this->assertStringNotContainsString('777.000', $sansPrix);
        $this->assertStringContainsString('777.000', $avecPrix);
    }

    public function test_les_filtres_doit_encore_et_non_soldees(): void
    {
        $this->connecter(Role::Admin);

        $doit = Vente::factory()->avecMoto()->create()->client;
        $solde = $this->venteSoldee('2026-10-01')->client;
        \Livewire\Livewire::test(\App\Filament\Gestion\Resources\Clients\Pages\ListClients::class)
            ->filterTable('debiteurs')
            ->assertCanSeeTableRecords([$doit])
            ->assertCanNotSeeTableRecords([$solde]);

        $facturePayee = \App\Models\FactureFournisseur::factory()->create();
        Moto::factory()->pourFacture($facturePayee)->create(['prix_achat' => 500_000]);
        $facturePayee->versements()->create(['date_versement' => now(), 'montant' => 500_000, 'mode' => 'especes']);
        $factureDue = \App\Models\FactureFournisseur::factory()->create();
        Moto::factory()->pourFacture($factureDue)->create(['prix_achat' => 500_000]);

        \Livewire\Livewire::test(\App\Filament\Gestion\Resources\FactureFournisseurs\Pages\ListFactureFournisseurs::class)
            ->filterTable('non_soldees')
            ->assertCanSeeTableRecords([$factureDue])
            ->assertCanNotSeeTableRecords([$facturePayee]);

        \Livewire\Livewire::test(\App\Filament\Gestion\Resources\Fournisseurs\Pages\ListFournisseurs::class)
            ->filterTable('doit')
            ->assertCanSeeTableRecords([$factureDue->fournisseur])
            ->assertCanNotSeeTableRecords([$facturePayee->fournisseur]);
    }

    public function test_le_magasinier_n_a_que_le_stock(): void
    {
        $this->connecter(Role::Magasinier);

        $this->assertTrue(Rapports::canAccess());
        $this->get(route('documents.rapport', 'stock'))->assertOk();
        $this->get(route('documents.rapport', 'clients'))->assertForbidden();
    }
}
