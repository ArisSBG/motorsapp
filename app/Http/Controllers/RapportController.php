<?php

namespace App\Http\Controllers;

use App\Enums\StatutImmatriculation;
use App\Enums\StatutVente;
use App\Models\Client;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\Immatriculation;
use App\Models\Moto;
use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rapports imprimables (PDF), questionnaire 12.4.
 * Le stock est consultable par tous les profils qui voient le stock (sans les prix sauf l'administrateur) ;
 * les autres rapports demandent le droit « exporter ».
 */
class RapportController extends Controller
{
    public const RAPPORTS = [
        'benefices' => 'Bénéfice par moto',
        'clients' => 'Soldes des clients',
        'fournisseurs' => 'Situation des fournisseurs',
        'stock' => 'Stock actuel',
        'sans-papiers' => 'Motos sans papiers définitifs',
        'ventes' => 'Ventes de la période',
    ];

    public function __invoke(Request $request, string $rapport): Response
    {
        abort_unless(array_key_exists($rapport, self::RAPPORTS), 404);
        abort_unless($request->user()->can($rapport === 'stock' ? 'voir_stock' : 'exporter'), 403);

        $du = CarbonImmutable::parse($request->query('du', now()->startOfMonth()->toDateString()))->startOfDay();
        $au = CarbonImmutable::parse($request->query('au', now()->toDateString()))->endOfDay();

        $donnees = match ($rapport) {
            'benefices' => ['ventes' => Vente::query()->avecTotaux()->with(['client', 'lignes.moto'])
                ->where('statut', StatutVente::Soldee)
                ->whereBetween('soldee_le', [$du->toDateString(), $au->toDateString()])
                ->orderBy('soldee_le')->get()],
            'ventes' => ['ventes' => Vente::query()->avecTotaux()->with(['client', 'lignes.moto', 'vendeur'])
                ->whereIn('statut', [StatutVente::EnCours, StatutVente::Soldee])
                ->whereBetween('date_vente', [$du->toDateString(), $au->toDateString()])
                ->orderBy('date_vente')->get()],
            'clients' => ['clients' => Client::query()->select('clients.*')->avecResteDu()
                ->with(['ventes' => fn ($q) => $q->actives()->avecTotaux()])
                ->doitEncore()->orderByDesc('reste_du')->get()],
            'fournisseurs' => ['fournisseurs' => Fournisseur::query()->select('fournisseurs.*')->avecResteAPayer()
                ->doitEncore()->orderByDesc('reste_a_payer')->get()
                ->each(fn (Fournisseur $f) => $f->setRelation('facturesDues', FactureFournisseur::query()
                    ->avecTotaux()->where('fournisseur_id', $f->id)->nonSoldees()->orderBy('date_facture')->get()))],
            'stock' => ['motos' => Moto::query()->disponibles()->avecFrais()->with('fournisseur')
                ->orderBy('marque')->orderBy('modele')->get(),
                'avecPrix' => $request->user()->can('voir_prix_achat')],
            'sans-papiers' => ['dossiers' => Immatriculation::query()->with(['moto', 'vente.client'])
                ->where('statut', '!=', StatutImmatriculation::CarteGriseRecue)
                ->orderBy('created_at')->get()],
        };

        $periode = in_array($rapport, ['benefices', 'ventes'], true);

        return Pdf::loadView("rapports.{$rapport}", [
            ...$donnees,
            'titre' => self::RAPPORTS[$rapport],
            'du' => $du,
            'au' => $au,
            'periode' => $periode ? 'du '.$du->format('d/m/Y').' au '.$au->format('d/m/Y') : 'au '.now()->format('d/m/Y'),
        ])
            ->setPaper('a4', in_array($rapport, ['benefices', 'ventes', 'stock'], true) ? 'landscape' : 'portrait')
            ->stream("Rapport-{$rapport}-".now()->format('Y-m-d').'.pdf');
    }
}
