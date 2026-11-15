<?php

namespace Tests\Feature;

use App\Enums\ModePaiement;
use App\Enums\StatutVente;
use App\Models\Moto;
use App\Models\ObjectifVente;
use App\Models\Vente;
use App\Services\Statistiques;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Calculs du tableau de bord : chiffre d'affaires à la date de vente,
 * bénéfice à la date où la vente est soldée.
 */
class StatistiquesTest extends TestCase
{
    use RefreshDatabase;

    private Statistiques $stats;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stats = app(Statistiques::class);
    }

    /** Vente d'une moto achetée 800.000 et vendue 900.000, sans immatriculation ni casque : bénéfice 100.000. */
    private function vente(string $date, int $prix = 900_000, array $attributs = []): Vente
    {
        return Vente::factory()
            ->avecMoto(Moto::factory()->create(['prix_achat' => 800_000]), prix: $prix, immatriculation: 0, casque: 0)
            ->create(['date_vente' => $date, ...$attributs]);
    }

    private function payer(Vente $vente, int $montant, string $date): void
    {
        $vente->paiements()->create(['date_paiement' => $date, 'montant' => $montant, 'mode' => ModePaiement::Especes]);
    }

    public function test_chiffres_du_mois(): void
    {
        $a = $this->vente('2026-10-03');
        $this->payer($a, 900_000, '2026-10-03');        // soldée en octobre : bénéfice 100.000 en octobre
        $b = $this->vente('2026-10-15', 1_000_000);
        $this->payer($b, 400_000, '2026-10-15');        // pas soldée : CA oui, bénéfice non
        $this->vente('2026-10-20', attributs: ['statut' => StatutVente::Reservation]); // réservation : ni CA ni moto vendue
        $annulee = $this->vente('2026-10-22');
        $annulee->update(['statut' => StatutVente::Annulee]);
        $this->vente('2026-09-30');                     // autre mois

        $octobre = $this->stats->mois(2026, 10);

        $this->assertSame(1_900_000, $octobre['chiffre_affaires']);
        $this->assertSame(2, $octobre['motos_vendues']);
        $this->assertSame(2, $octobre['nombre_ventes']);
        $this->assertSame(100_000, $octobre['benefice']);
        $this->assertSame(1_300_000, $octobre['encaisse']);
    }

    public function test_le_benefice_compte_le_mois_ou_la_vente_est_soldee(): void
    {
        $vente = $this->vente('2026-09-25');
        $this->payer($vente, 500_000, '2026-09-25');
        $this->payer($vente, 400_000, '2026-11-02');

        $this->assertSame(900_000, $this->stats->mois(2026, 9)['chiffre_affaires']);
        $this->assertSame(0, $this->stats->mois(2026, 9)['benefice']);
        $this->assertSame(100_000, $this->stats->mois(2026, 11)['benefice']);
        $this->assertSame(0, $this->stats->mois(2026, 11)['chiffre_affaires']);
    }

    public function test_douze_mois_de_l_annee(): void
    {
        $this->payer($this->vente('2026-01-10'), 900_000, '2026-01-10');
        $this->vente('2026-03-05', 1_200_000);
        $this->vente('2026-03-06', 800_000);
        $this->vente('2025-03-05'); // année précédente

        $annee = $this->stats->parMois(2026);

        $this->assertCount(12, $annee);
        $this->assertSame(900_000, $annee[0]['chiffre_affaires']);
        $this->assertSame(100_000, $annee[0]['benefice']);
        $this->assertSame(2_000_000, $annee[2]['chiffre_affaires']);
        $this->assertSame(2, $annee[2]['motos_vendues']);
        $this->assertSame(0, $annee[1]['chiffre_affaires']);
    }

    public function test_objectif_du_mois(): void
    {
        ObjectifVente::create(['annee' => 2026, 'mois' => 10, 'nombre_motos' => 4]);
        $this->vente('2026-10-01');
        $this->vente('2026-10-02');
        $this->vente('2026-10-03');

        $objectif = $this->stats->objectif(2026, 10);

        $this->assertSame(['cible' => 4, 'vendues' => 3, 'pourcentage' => 75], $objectif);
        $this->assertNull($this->stats->objectif(2026, 11));
    }

    public function test_evolution_en_pourcentage(): void
    {
        $this->assertSame(25, Statistiques::evolution(1_250_000, 1_000_000));
        $this->assertSame(-50, Statistiques::evolution(500, 1_000));
        $this->assertNull(Statistiques::evolution(500, 0));
    }

    public function test_periode_libre(): void
    {
        $this->vente('2026-10-01');
        $this->vente('2026-10-10');
        $this->vente('2026-10-20');

        $p = $this->stats->periode(CarbonImmutable::parse('2026-10-05'), CarbonImmutable::parse('2026-10-15'));

        $this->assertSame(1, $p['motos_vendues']);
    }
}
