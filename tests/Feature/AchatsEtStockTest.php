<?php

namespace Tests\Feature;

use App\Enums\TypeFrais;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\FraisMoto;
use App\Models\Moto;
use App\Models\VersementFournisseur;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Règles d'argent du lot 1 : transport, soldes fournisseurs, châssis.
 */
class AchatsEtStockTest extends TestCase
{
    use RefreshDatabase;

    private function partsTransport(FactureFournisseur $facture): array
    {
        return FraisMoto::where('facture_fournisseur_id', $facture->id)
            ->where('type', TypeFrais::Transport)
            ->orderBy('moto_id')->pluck('montant')->all();
    }

    public function test_transport_reparti_a_parts_egales_et_somme_exacte(): void
    {
        $facture = FactureFournisseur::factory()->create(['montant_transport' => 10_000]);
        Moto::factory()->count(3)->pourFacture($facture)->create();

        $this->assertSame([3_334, 3_333, 3_333], $this->partsTransport($facture));
        $this->assertSame(10_000, array_sum($this->partsTransport($facture)));
    }

    public function test_transport_recalcule_quand_on_ajoute_ou_retire_une_moto(): void
    {
        $facture = FactureFournisseur::factory()->create(['montant_transport' => 20_000]);
        $motos = Moto::factory()->count(4)->pourFacture($facture)->create();
        $this->assertSame([5_000, 5_000, 5_000, 5_000], $this->partsTransport($facture));

        $motos->first()->delete(); // corbeille
        $this->assertSame([6_667, 6_667, 6_666], $this->partsTransport($facture));

        $motos->first()->restore();
        $this->assertSame([5_000, 5_000, 5_000, 5_000], $this->partsTransport($facture));
    }

    public function test_transport_recalcule_quand_le_montant_change(): void
    {
        $facture = FactureFournisseur::factory()->create(['montant_transport' => 7_000]);
        Moto::factory()->count(2)->pourFacture($facture)->create();

        $facture->update(['montant_transport' => 5_000]);

        $this->assertSame([2_500, 2_500], $this->partsTransport($facture));
    }

    public function test_moto_deplacee_vers_une_autre_facture(): void
    {
        $f1 = FactureFournisseur::factory()->create(['montant_transport' => 6_000]);
        $f2 = FactureFournisseur::factory()->create(['montant_transport' => 3_000, 'fournisseur_id' => $f1->fournisseur_id]);
        $motos = Moto::factory()->count(2)->pourFacture($f1)->create();

        $motos->last()->update(['facture_fournisseur_id' => $f2->id]);

        $this->assertSame([6_000], $this->partsTransport($f1));
        $this->assertSame([3_000], $this->partsTransport($f2));
    }

    public function test_cout_de_revient_inclut_transport_et_reparation(): void
    {
        $facture = FactureFournisseur::factory()->create(['montant_transport' => 3_500]);
        $moto = Moto::factory()->pourFacture($facture)->create(['prix_achat' => 800_000]);
        $moto->frais()->create(['type' => TypeFrais::Reparation, 'montant' => 15_000, 'date_frais' => now()]);

        $this->assertSame(818_500, $moto->fresh()->coutDeRevient());
    }

    public function test_solde_facture_avec_remise_et_versements(): void
    {
        $facture = FactureFournisseur::factory()->create(['remise' => 50_000, 'montant_transport' => 7_000]);
        Moto::factory()->pourFacture($facture)->create(['prix_achat' => 800_000]);
        Moto::factory()->pourFacture($facture)->create(['prix_achat' => 650_000]);
        VersementFournisseur::factory()->for($facture, 'facture')->create(['montant' => 1_000_000]);
        VersementFournisseur::factory()->for($facture, 'facture')->create(['montant' => 200_000]);

        $facture = $facture->fresh();
        // Le transport n'est pas dû au fournisseur
        $this->assertSame(1_400_000, $facture->total());
        $this->assertSame(1_200_000, $facture->verse());
        $this->assertSame(200_000, $facture->solde());
        $this->assertFalse($facture->estSoldee());
    }

    public function test_versement_supprime_n_est_plus_compte(): void
    {
        $facture = FactureFournisseur::factory()->create();
        Moto::factory()->pourFacture($facture)->create(['prix_achat' => 700_000]);
        $v = VersementFournisseur::factory()->for($facture, 'facture')->create(['montant' => 700_000]);
        $this->assertTrue($facture->fresh()->estSoldee());

        $v->delete();

        $this->assertSame(700_000, $facture->fresh()->solde());
    }

    public function test_reste_a_payer_par_fournisseur_et_total_du(): void
    {
        $a = Fournisseur::factory()->create();
        $b = Fournisseur::factory()->create();

        $fa = FactureFournisseur::factory()->for($a)->create();
        Moto::factory()->count(2)->pourFacture($fa)->create(['prix_achat' => 700_000]);
        VersementFournisseur::factory()->for($fa, 'facture')->create(['montant' => 400_000]);

        $fb = FactureFournisseur::factory()->for($b)->create();
        Moto::factory()->pourFacture($fb)->create(['prix_achat' => 850_000]);

        $this->assertSame(1_000_000, $a->resteAPayer());
        $this->assertSame(850_000, $b->resteAPayer());
        $this->assertSame(1_850_000, Fournisseur::totalDu());

        $liste = Fournisseur::query()->avecResteAPayer()->orderBy('id')->get();
        $this->assertSame([1_000_000, 850_000], $liste->map->resteAPayer()->all());
    }

    public function test_chassis_normalise_en_majuscules_sans_espaces(): void
    {
        $moto = Moto::factory()->create(['chassis' => ' rlcue 12-345.678 ']);

        $this->assertSame('RLCUE12345678', $moto->chassis);
    }

    public function test_un_chassis_ne_peut_pas_exister_deux_fois(): void
    {
        Moto::factory()->create(['chassis' => 'MH3SE123456']);

        $this->expectException(QueryException::class);
        Moto::factory()->create(['chassis' => 'mh3se 123456']);
    }

    public function test_les_modifications_sont_journalisees(): void
    {
        $moto = Moto::factory()->create(['prix_achat' => 700_000]);
        $moto->update(['prix_achat' => 720_000]);

        $log = $moto->activitiesAsSubject()->latest('id')->first();
        $this->assertSame(700_000, $log->attribute_changes['old']['prix_achat']);
        $this->assertSame(720_000, $log->attribute_changes['attributes']['prix_achat']);
    }
}
