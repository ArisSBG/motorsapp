<?php

namespace Tests\Feature;

use App\Enums\DocumentRemis;
use App\Enums\ModePaiement;
use App\Enums\StatutMoto;
use App\Enums\StatutVente;
use App\Enums\TypeFrais;
use App\Models\Client;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\Immatriculation;
use App\Models\Moto;
use App\Models\Vente;
use App\Services\Statistiques;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rejoue les données de la feuille « Tests_UAT_BURKINA_MOTORS.xlsx » et vérifie que l'application
 * calcule exactement les résultats attendus écrits dans la feuille. Si ce test échoue, la feuille
 * UAT et l'application ne sont plus d'accord.
 */
class ScenarioUatTest extends TestCase
{
    use RefreshDatabase;

    private array $motos = [];

    private function facture(string $numero, string $fournisseur, int $transport, int $remise, array $lignes): FactureFournisseur
    {
        $facture = FactureFournisseur::create([
            'fournisseur_id' => Fournisseur::create(['nom' => $fournisseur])->id,
            'numero' => $numero, 'date_facture' => now(), 'montant_transport' => $transport, 'remise' => $remise,
        ]);
        foreach ($lignes as [$code, $chassis, $marque, $modele, $prix]) {
            $this->motos[$code] = Moto::create([
                'chassis' => $chassis, 'marque' => $marque, 'modele' => $modele, 'prix_achat' => $prix,
                'fournisseur_id' => $facture->fournisseur_id, 'facture_fournisseur_id' => $facture->id, 'date_reception' => now(),
            ]);
        }

        return $facture;
    }

    private function vendre(string $client, array $lignes, int $paiement, int $commission = 0, string $statut = 'en_cours'): Vente
    {
        $vente = Vente::create([
            'client_id' => Client::firstOrCreate(['nom' => $client])->id, 'date_vente' => now(),
            'statut' => $statut, 'commission' => $commission,
        ]);
        foreach ($lignes as [$code, $prix, $immat, $casque]) {
            $vente->lignes()->create(['moto_id' => $this->motos[$code]->id, 'prix_vente' => $prix, 'frais_immatriculation' => $immat, 'cout_casque' => $casque]);
        }
        $this->payer($vente, $paiement);

        return $vente;
    }

    private function payer(Vente $vente, int $montant): void
    {
        $vente->paiements()->create(['date_paiement' => now(), 'montant' => $montant, 'mode' => ModePaiement::Especes]);
    }

    public function test_les_resultats_attendus_de_la_feuille_uat(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));

        $lignesA = [];
        foreach (range(1, 10) as $n) {
            $lignesA[] = [sprintf('A%02d', $n), $n <= 6 ? sprintf('RLCUE5210UAT%05d', $n) : sprintf('RLCUE5215UAT%05d', $n),
                'Yamaha', $n <= 6 ? 'Sirius 110' : 'Sirius 115', $n <= 6 ? 800_000 : 850_000];
        }
        $fa = $this->facture('FA-UAT-001', 'Armado Motos', 20_000, 0, $lignesA);
        $si = $this->facture('SI-UAT-002', 'Soulemane Import', 7_000, 50_000, [
            ['B01', 'LC6PAGA1UAT00011', 'Apsonic', 'AP125', 650_000], ['B02', 'LC6PAGA1UAT00012', 'Apsonic', 'AP125', 650_000],
            ['B03', 'LC6PAGA1UAT00013', 'Apsonic', 'AP125', 650_000], ['B04', 'LC6PAGA1UAT00014', 'Apsonic', 'AP125', 650_000],
            ['B05', 'MH3SE21UAT000015', 'Apsonic', 'Scooter', 700_000], ['B06', 'MH3SG21UAT000016', 'Apsonic', 'Scooter', 700_000],
            ['B07', 'MH3SEL1UAT000017', 'Apsonic', 'Scooter', 700_000],
        ]);
        $ad = $this->facture('AD-UAT-003', 'Abdoul Distribution', 10_000, 0, [
            ['C01', 'LALJC1UAT0000018', 'Honda', 'Dream', 750_000], ['C02', 'LALJC1UAT0000019', 'Honda', 'Dream', 750_000],
            ['C03', 'LC6PVIVAUAT00020', 'Suzuki', 'Viva 115', 700_000],
        ]);

        // A-06 / A-07 : totaux et parts de transport
        $this->assertSame(8_200_000, $fa->total());
        $this->assertSame(4_650_000, $si->total());
        $this->assertSame(2_200_000, $ad->total());
        $this->assertSame([2_000, 1_000, 3_334, 3_333, 3_333], array_map(
            fn ($c) => $this->motos[$c]->totalFrais(), ['A01', 'B01', 'C01', 'C02', 'C03']));

        // A-08 à A-10 : versements
        $fa->versements()->create(['date_versement' => now(), 'montant' => 3_000_000, 'mode' => 'virement']);
        $fa->versements()->create(['date_versement' => now(), 'montant' => 2_000_000, 'mode' => 'mobile_money']);
        $si->versements()->create(['date_versement' => now(), 'montant' => 4_650_000, 'mode' => 'virement']);
        $this->assertSame(3_200_000, $fa->fresh()->solde());
        $this->assertSame(5_400_000, Fournisseur::totalDu());

        // A-11 : réparation de A03
        $this->motos['A03']->frais()->create(['type' => TypeFrais::Reparation, 'montant' => 15_000, 'date_frais' => now()]);
        $this->assertSame(817_000, $this->motos['A03']->fresh()->coutDeRevient());

        // V-01 à V-09
        $v1 = $this->vendre('Ouédraogo Issa', [['A01', 900_000, 41_000, 6_500]], 900_000, commission: 10_000);
        $v2 = $this->vendre('Kaboré Aminata', [['A07', 975_000, 41_000, 6_500]], 500_000);
        $v3 = $this->vendre('Garage Zida & Fils', [['B01', 720_000, 0, 0], ['B02', 720_000, 0, 0]], 1_000_000);
        $v4 = $this->vendre('Sawadogo Moussa', [['C03', 820_000, 41_000, 6_500]], 100_000, statut: 'reservation');
        $this->assertSame(StatutMoto::Reservee, $this->motos['C03']->fresh()->statut);
        $v4->update(['statut' => StatutVente::EnCours]);
        $v5 = $this->vendre('Zongo Paul', [['A02', 900_000, 41_000, 6_500]], 200_000);
        $v5->update(['statut' => StatutVente::Annulee, 'motif_annulation' => 'Échange']);
        $v6 = $this->vendre('Zongo Paul', [['C01', 880_000, 41_000, 6_500]], 880_000);

        $this->assertSame('BURKINA-MOTORS-'.now()->year.'-0006', $v6->numero);
        $this->assertSame(StatutMoto::EnStock, $this->motos['A02']->fresh()->statut);
        $this->assertSame([40_500, 75_500, 138_000, 69_167, 79_166], array_map(fn (Vente $v) => $v->fresh()->benefice(), [$v1, $v2, $v3, $v4, $v6]));
        $this->assertSame(440_000, $v3->fresh()->reste());

        // P-02 : 6 dossiers, pas pour A02
        $this->assertSame(6, Immatriculation::count());
        $this->assertNull($this->motos['A02']->fresh()->immatriculation);

        // P-03 à P-08, V-11, V-12 : papiers et paiements de V2
        $dossierA01 = $this->motos['A01']->fresh()->immatriculation;
        $dossierA01->update(['date_provisoire' => now(), 'date_carte_grise' => now()]);
        $dossierA01->retraits()->create(['date_retrait' => now(), 'document' => DocumentRemis::CarteGrise, 'retire_par' => 'Ouédraogo Issa']);
        $dossierA07 = $this->motos['A07']->fresh()->immatriculation;
        $dossierA07->update(['date_carte_grise' => now()]);
        $this->assertFalse($v2->fresh()->peutRemettrePapiers(), 'P-07 : remise refusée (reste 475.000)');
        $this->payer($v2, 425_000);
        $this->assertTrue($v2->fresh()->peutRemettrePapiers(), 'P-08 : remise acceptée (reste 50.000)');
        $dossierA07->retraits()->create(['date_retrait' => now(), 'document' => DocumentRemis::CarteGrise, 'retire_par' => 'Kaboré Ali (frère)', 'est_le_client' => false]);
        $this->payer($v2, 50_000);
        $this->assertSame(StatutVente::Soldee, $v2->fresh()->statut);
        $this->assertSame(StatutMoto::Livree, $this->motos['A01']->fresh()->statut);
        $this->assertSame(StatutMoto::Livree, $this->motos['A07']->fresh()->statut);

        // V-13, T-01, R-01 à R-03 : chiffres attendus de la feuille
        $mois = app(Statistiques::class)->mois(now()->year, now()->month);
        $this->assertSame(14, Moto::disponibles()->count());
        $this->assertSame(10_700_000, (int) Moto::disponibles()->sum('prix_achat'));
        $this->assertSame(5_400_000, Fournisseur::totalDu());
        $this->assertSame(1_160_000, Client::totalDu());
        $this->assertSame(5_015_000, $mois['chiffre_affaires']);
        $this->assertSame(6, $mois['motos_vendues']);
        $this->assertSame(195_166, $mois['benefice']);
        $this->assertSame(4_055_000, $mois['encaisse']);
        $this->assertSame(4, Immatriculation::where('statut', '!=', 'carte_grise_recue')->count());
    }
}
