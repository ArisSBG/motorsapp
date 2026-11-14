<?php

namespace Tests\Feature;

use App\Enums\ModePaiement;
use App\Enums\StatutMoto;
use App\Enums\StatutVente;
use App\Models\Client;
use App\Models\Echeance;
use App\Models\FactureFournisseur;
use App\Models\Moto;
use App\Models\PaiementClient;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Règles d'argent du lot 2 : bénéfice, paiements par tranches, statuts, numéros.
 */
class VentesTest extends TestCase
{
    use RefreshDatabase;

    private function payer(Vente $vente, int $montant, ?string $date = null): PaiementClient
    {
        return $vente->paiements()->create([
            'date_paiement' => $date ?? now()->toDateString(),
            'montant' => $montant,
            'mode' => ModePaiement::Especes,
        ]);
    }

    /** Moto achetée 800.000 avec 3.500 de transport (exemple du questionnaire, 7.1). */
    private function motoDuQuestionnaire(): Moto
    {
        $facture = FactureFournisseur::factory()->create(['montant_transport' => 3_500]);

        return Moto::factory()->pourFacture($facture)->create(['prix_achat' => 800_000]);
    }

    public function test_exemple_reel_du_client_benefice_49000(): void
    {
        $vente = Vente::factory()->avecMoto($this->motoDuQuestionnaire(), prix: 900_000, immatriculation: 41_000, casque: 6_500)->create();

        $this->assertSame(900_000, $vente->total());
        $this->assertSame(851_000, $vente->couts());
        $this->assertSame(49_000, $vente->benefice());
    }

    public function test_la_commission_de_l_apporteur_reduit_le_benefice(): void
    {
        $vente = Vente::factory()->avecMoto($this->motoDuQuestionnaire())->create(['commission' => 10_000, 'apporteur_nom' => 'Issa']);

        $this->assertSame(39_000, $vente->benefice());
    }

    public function test_vente_de_plusieurs_motos_a_un_revendeur(): void
    {
        $vente = Vente::factory()
            ->avecMoto(Moto::factory()->create(['prix_achat' => 700_000]), prix: 760_000, immatriculation: 0, casque: 0)
            ->avecMoto(Moto::factory()->create(['prix_achat' => 700_000]), prix: 760_000, immatriculation: 0, casque: 0)
            ->create();

        $this->assertSame(1_520_000, $vente->total());
        $this->assertSame(120_000, $vente->benefice());
    }

    public function test_la_moto_vendue_sort_du_stock_et_revient_si_annulation(): void
    {
        $moto = Moto::factory()->create();
        $vente = Vente::factory()->avecMoto($moto)->create();
        $this->assertSame(StatutMoto::Vendue, $moto->fresh()->statut);

        $vente->update(['statut' => StatutVente::Annulee, 'motif_annulation' => 'Client a changé d’avis']);

        $this->assertSame(StatutMoto::EnStock, $moto->fresh()->statut);
    }

    public function test_reservation_puis_confirmation(): void
    {
        $moto = Moto::factory()->create();
        $vente = Vente::factory()->avecMoto($moto)->create(['statut' => StatutVente::Reservation]);
        $this->payer($vente, 200_000);

        $this->assertSame(StatutMoto::Reservee, $moto->fresh()->statut);
        $this->assertSame(StatutVente::Reservation, $vente->fresh()->statut, 'un acompte ne transforme pas la réservation en vente');

        $vente->update(['statut' => StatutVente::EnCours]);

        $this->assertSame(StatutMoto::Vendue, $moto->fresh()->statut);
    }

    public function test_retirer_une_moto_de_la_vente_la_remet_en_stock(): void
    {
        $m1 = Moto::factory()->create();
        $m2 = Moto::factory()->create();
        $vente = Vente::factory()->avecMoto($m1)->avecMoto($m2)->create();

        $vente->lignes()->where('moto_id', $m2->id)->first()->delete();

        $this->assertSame(StatutMoto::Vendue, $m1->fresh()->statut);
        $this->assertSame(StatutMoto::EnStock, $m2->fresh()->statut);
    }

    public function test_vente_mise_a_la_corbeille_libere_les_motos(): void
    {
        $moto = Moto::factory()->create();
        $vente = Vente::factory()->avecMoto($moto)->create();

        $vente->delete();
        $this->assertSame(StatutMoto::EnStock, $moto->fresh()->statut);

        $vente->restore();
        $this->assertSame(StatutMoto::Vendue, $moto->fresh()->statut);
    }

    public function test_une_annulation_ne_fait_pas_reculer_une_moto_deja_livree(): void
    {
        $moto = Moto::factory()->create();
        $vente = Vente::factory()->avecMoto($moto)->create();
        $moto->update(['statut' => StatutMoto::Livree]);

        $vente->update(['statut' => StatutVente::Annulee]);

        $this->assertSame(StatutMoto::Livree, $moto->fresh()->statut);
    }

    public function test_paiements_par_tranches_et_vente_soldee(): void
    {
        $vente = Vente::factory()->avecMoto(prix: 975_000)->create();
        $this->assertSame(StatutVente::EnCours, $vente->fresh()->statut);

        $this->payer($vente, 500_000, '2026-10-01');
        $vente = $vente->fresh();
        $this->assertSame(475_000, $vente->reste());
        $this->assertSame(StatutVente::EnCours, $vente->statut);
        $this->assertNull($vente->soldee_le);

        $dernier = $this->payer($vente, 475_000, '2026-11-05');
        $vente = $vente->fresh();
        $this->assertSame(0, $vente->reste());
        $this->assertSame(StatutVente::Soldee, $vente->statut);
        $this->assertSame('2026-11-05', $vente->soldee_le->toDateString(), 'le bénéfice compte le jour du dernier paiement');

        $dernier->delete();
        $this->assertSame(StatutVente::EnCours, $vente->fresh()->statut);
        $this->assertNull($vente->fresh()->soldee_le);
    }

    public function test_numerotation_des_factures_et_des_recus(): void
    {
        $this->travelTo('2026-06-01');
        $v1 = Vente::factory()->avecMoto()->create();
        $v2 = Vente::factory()->avecMoto()->create();
        $r1 = $this->payer($v1, 1_000);

        $this->assertSame('BURKINA-MOTORS-2026-0001', $v1->numero);
        $this->assertSame('BURKINA-MOTORS-2026-0002', $v2->numero);
        $this->assertSame('REC-2026-0001', $r1->numero_recu);

        // Une vente supprimée garde son numéro : il n'est jamais réutilisé
        $v2->delete();
        $this->assertSame('BURKINA-MOTORS-2026-0003', Vente::factory()->avecMoto()->create()->numero);

        $this->travelTo('2027-01-02');
        $this->assertSame('BURKINA-MOTORS-2027-0001', Vente::factory()->avecMoto()->create()->numero);
    }

    public function test_ce_que_doivent_les_clients(): void
    {
        $client = Client::factory()->create();
        $v1 = Vente::factory()->for($client)->avecMoto(prix: 900_000)->create();
        $this->payer($v1, 600_000);
        Vente::factory()->for($client)->avecMoto(prix: 800_000)->create(['statut' => StatutVente::Annulee]);
        Vente::factory()->avecMoto(prix: 700_000)->create();

        $this->assertSame(300_000, $client->resteDu());
        $this->assertSame(1_000_000, Client::totalDu());
    }

    public function test_papiers_remis_si_reste_a_payer_jusqu_a_50000(): void
    {
        $vente = Vente::factory()->avecMoto(prix: 900_000)->create();
        $this->payer($vente, 849_000);
        $this->assertFalse($vente->fresh()->peutRemettrePapiers());

        $this->payer($vente, 1_000);
        $this->assertTrue($vente->fresh()->peutRemettrePapiers());
    }

    public function test_echeance_en_retard(): void
    {
        $this->travelTo('2026-11-15');
        $vente = Vente::factory()->avecMoto(prix: 900_000)->create();
        $passee = Echeance::create(['vente_id' => $vente->id, 'date_prevue' => '2026-11-01', 'montant' => 300_000]);
        $future = Echeance::create(['vente_id' => $vente->id, 'date_prevue' => '2026-12-01', 'montant' => 300_000]);

        $this->assertTrue($passee->estEnRetard());
        $this->assertFalse($future->estEnRetard());

        $this->payer($vente, 300_000);
        $this->assertFalse($passee->fresh()->estEnRetard());
    }
}
