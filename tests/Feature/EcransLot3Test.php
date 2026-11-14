<?php

namespace Tests\Feature;

use App\Enums\ModePaiement;
use App\Enums\Role;
use App\Enums\StatutMoto;
use App\Enums\TypeDocument;
use App\Filament\Auth\Login;
use App\Filament\Papiers\Resources\Documents\Pages\CreateDocument;
use App\Filament\Papiers\Resources\Immatriculations\Pages\ViewImmatriculation;
use App\Models\Document;
use App\Models\Immatriculation;
use App\Models\Moto;
use App\Models\User;
use App\Models\Vente;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EcransLot3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Filament::setCurrentPanel('papiers');
    }

    private function connecter(Role $role): User
    {
        $user = tap(User::factory()->create())->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    /** Moto vendue 900.000, avec un premier paiement. */
    private function dossier(int $paye = 900_000): Immatriculation
    {
        $moto = Moto::factory()->create();
        $vente = Vente::factory()->avecMoto($moto, prix: 900_000)->create();
        if ($paye) {
            $vente->paiements()->create(['date_paiement' => now(), 'montant' => $paye, 'mode' => ModePaiement::Especes]);
        }

        return $moto->fresh()->immatriculation;
    }

    public function test_la_secretaire_travaille_dans_l_espace_papiers_uniquement(): void
    {
        $this->connecter(Role::Secretaire);
        $dossier = $this->dossier();

        foreach (['/papiers', '/papiers/immatriculations', "/papiers/immatriculations/{$dossier->id}", "/papiers/immatriculations/{$dossier->id}/edit",
            '/papiers/archives', '/papiers/archives/create'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/gestion')->assertForbidden();
        $this->get('/')->assertRedirect('/papiers');
    }

    public function test_le_vendeur_et_le_magasinier_n_entrent_pas_dans_l_espace_papiers(): void
    {
        foreach ([Role::Vendeur, Role::Magasinier] as $role) {
            $this->connecter($role);
            $this->get('/papiers')->assertForbidden();
        }
    }

    public function test_la_secretaire_qui_se_connecte_par_gestion_arrive_dans_papiers(): void
    {
        Filament::setCurrentPanel('gestion');
        $secretaire = tap(User::factory()->create(['email' => 's@test.bf', 'password' => 'Secret123']))->assignRole('secretaire');

        Livewire::test(Login::class)
            ->set('data.email', 's@test.bf')
            ->set('data.password', 'Secret123')
            ->call('authenticate')
            ->assertRedirect('/papiers');

        $this->assertAuthenticatedAs($secretaire);
    }

    public function test_carte_grise_recue_enregistre_la_plaque(): void
    {
        $this->connecter(Role::Secretaire);
        $dossier = $this->dossier();

        Livewire::test(ViewImmatriculation::class, ['record' => $dossier->getRouteKey()])
            ->callAction('carte_grise', ['date_carte_grise' => now()->toDateString(), 'numero_carte_grise' => 'CG-2026-77', 'plaque' => '11 kk 2233'])
            ->assertHasNoActionErrors();

        $moto = $dossier->moto->fresh();
        $this->assertSame('11 KK 2233', $moto->plaque);
        $this->assertSame(StatutMoto::PapiersRecus, $moto->statut);
    }

    public function test_prevenir_le_client_ouvre_whatsapp_et_note_la_date(): void
    {
        $this->connecter(Role::Secretaire);
        $dossier = $this->dossier();
        $dossier->update(['date_carte_grise' => now()]);

        Livewire::test(ViewImmatriculation::class, ['record' => $dossier->getRouteKey()])
            ->callAction('prevenir')
            ->assertHasNoActionErrors();

        $this->assertNotNull($dossier->fresh()->client_prevenu_le);
    }

    public function test_papiers_refuses_si_le_client_doit_plus_de_50000(): void
    {
        $this->connecter(Role::Secretaire);
        $dossier = $this->dossier(paye: 800_000); // doit encore 100.000
        $dossier->update(['date_carte_grise' => now()]);

        Livewire::test(ViewImmatriculation::class, ['record' => $dossier->getRouteKey()])
            ->callAction('retrait', ['document' => 'carte_grise', 'date_retrait' => now()->toDateString(), 'est_le_client' => true]);

        $this->assertSame(0, $dossier->retraits()->count());
        $this->assertSame(StatutMoto::PapiersRecus, $dossier->moto->fresh()->statut);
    }

    public function test_l_admin_peut_forcer_la_remise(): void
    {
        $this->connecter(Role::Admin);
        $dossier = $this->dossier(paye: 800_000);
        $dossier->update(['date_carte_grise' => now()]);

        Livewire::test(ViewImmatriculation::class, ['record' => $dossier->getRouteKey()])
            ->callAction('retrait', ['document' => 'carte_grise', 'date_retrait' => now()->toDateString(), 'est_le_client' => false,
                'retire_par' => 'Oumarou (frère)', 'forcer' => true])
            ->assertHasNoActionErrors();

        $this->assertSame('Oumarou (frère)', $dossier->retraits()->first()->retire_par);
        $this->assertSame(StatutMoto::Livree, $dossier->moto->fresh()->statut);
    }

    public function test_client_qui_a_tout_paye_recupere_sa_carte_grise(): void
    {
        $this->connecter(Role::Secretaire);
        $dossier = $this->dossier();
        $dossier->update(['date_carte_grise' => now()]);

        Livewire::test(ViewImmatriculation::class, ['record' => $dossier->getRouteKey()])
            ->callAction('retrait', ['document' => 'carte_grise', 'date_retrait' => now()->toDateString(), 'est_le_client' => true])
            ->assertHasNoActionErrors();

        $retrait = $dossier->retraits()->first();
        $this->assertSame($dossier->vente->client->nom, $retrait->retire_par);
        $this->assertSame(StatutMoto::Livree, $dossier->moto->fresh()->statut);
    }

    public function test_deposer_un_document_puis_le_relire(): void
    {
        Storage::fake(Document::DISQUE);
        $secretaire = $this->connecter(Role::Secretaire);
        $moto = Moto::factory()->create();
        $fichier = UploadedFile::fake()->createWithContent('carte-grise.pdf', '%PDF-1.4 contenu de test');

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'chemin' => $fichier, 'type' => TypeDocument::CarteGrise->value, 'titre' => 'Carte grise', 'motos' => [$moto->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::firstOrFail();
        $this->assertTrue($document->chiffre);
        $this->assertSame('carte-grise.pdf', $document->nom_original);
        $this->assertTrue($document->motos->contains($moto));
        $this->assertSame($secretaire->id, $document->user_id);
        $this->assertStringNotContainsString('contenu de test', Storage::disk(Document::DISQUE)->get($document->chemin));

        $this->get(route('documents.fichier', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSeeText('contenu de test', false);

        // Le magasinier n'a pas accès aux documents
        $this->connecter(Role::Magasinier);
        $this->get(route('documents.fichier', $document))->assertForbidden();
    }

    public function test_les_pages_proposent_l_installation_sur_telephone(): void
    {
        $this->get('/papiers/login')->assertOk()->assertSee('rel="manifest"', false)->assertSee('/sw.js', false);
    }
}
