<?php

namespace App\Services;

use App\Enums\StatutVente;
use App\Models\ObjectifVente;
use App\Models\Vente;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Chiffres du tableau de bord et des rapports.
 *
 * - Chiffre d'affaires et motos vendues : comptés à la date de la vente (hors annulées et réservations).
 * - Bénéfice : compté à la date où la vente est soldée (choix du client, questionnaire 7.4).
 */
class Statistiques
{
    private const STATUTS_VENDUS = [StatutVente::EnCours, StatutVente::Soldee];

    /** @return array{chiffre_affaires: int, motos_vendues: int, nombre_ventes: int, benefice: int, encaisse: int} */
    public function mois(int $annee, int $mois): array
    {
        $debut = CarbonImmutable::create($annee, $mois, 1)->startOfMonth();

        return $this->periode($debut, $debut->endOfMonth());
    }

    /** @return array{chiffre_affaires: int, motos_vendues: int, nombre_ventes: int, benefice: int, encaisse: int} */
    public function periode(CarbonImmutable $du, CarbonImmutable $au): array
    {
        $ventes = Vente::query()->avecTotaux()->withCount('lignes')
            ->whereIn('statut', self::STATUTS_VENDUS)
            ->whereBetween('date_vente', [$du->toDateString(), $au->toDateString()])
            ->get();

        $benefice = (int) Vente::query()->avecTotaux()
            ->where('statut', StatutVente::Soldee)
            ->whereBetween('soldee_le', [$du->toDateString(), $au->toDateString()])
            ->get()
            ->sum(fn (Vente $v) => $v->benefice());

        $encaisse = (int) \App\Models\PaiementClient::query()
            ->whereBetween('date_paiement', [$du->toDateString(), $au->toDateString()])
            ->sum('montant');

        return [
            'chiffre_affaires' => (int) $ventes->sum(fn (Vente $v) => $v->total()),
            'motos_vendues' => (int) $ventes->sum('lignes_count'),
            'nombre_ventes' => $ventes->count(),
            'benefice' => $benefice,
            'encaisse' => $encaisse,
        ];
    }

    /**
     * Les 12 mois d'une année.
     *
     * @return Collection<int, array{mois: int, chiffre_affaires: int, motos_vendues: int, benefice: int}>
     */
    public function parMois(int $annee): Collection
    {
        $du = CarbonImmutable::create($annee, 1, 1);
        $au = $du->endOfYear();

        $ventes = Vente::query()->avecTotaux()->withCount('lignes')
            ->whereIn('statut', self::STATUTS_VENDUS)
            ->whereBetween('date_vente', [$du->toDateString(), $au->toDateString()])
            ->get()
            ->groupBy(fn (Vente $v) => $v->date_vente->month);

        $soldees = Vente::query()->avecTotaux()
            ->where('statut', StatutVente::Soldee)
            ->whereBetween('soldee_le', [$du->toDateString(), $au->toDateString()])
            ->get()
            ->groupBy(fn (Vente $v) => $v->soldee_le->month);

        return collect(range(1, 12))->map(fn (int $m) => [
            'mois' => $m,
            'chiffre_affaires' => (int) ($ventes->get($m)?->sum(fn (Vente $v) => $v->total()) ?? 0),
            'motos_vendues' => (int) ($ventes->get($m)?->sum('lignes_count') ?? 0),
            'benefice' => (int) ($soldees->get($m)?->sum(fn (Vente $v) => $v->benefice()) ?? 0),
        ]);
    }

    /** Avancement vers l'objectif du mois (null s'il n'y a pas d'objectif). */
    public function objectif(int $annee, int $mois): ?array
    {
        $cible = ObjectifVente::pour($annee, $mois);
        if (! $cible) {
            return null;
        }

        $vendues = $this->mois($annee, $mois)['motos_vendues'];

        return [
            'cible' => $cible,
            'vendues' => $vendues,
            'pourcentage' => (int) round($vendues / $cible * 100),
        ];
    }

    /** Évolution en % par rapport à une valeur précédente (null si pas de comparaison possible). */
    public static function evolution(int $actuel, int $precedent): ?int
    {
        if ($precedent === 0) {
            return null;
        }

        return (int) round(($actuel - $precedent) / abs($precedent) * 100);
    }

    public static function nomMois(int $mois): string
    {
        return ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'][$mois - 1];
    }
}
