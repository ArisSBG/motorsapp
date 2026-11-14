<?php

namespace Database\Seeders;

use App\Enums\ModePaiement;
use App\Enums\TypeFrais;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\Client;
use App\Models\Moto;
use App\Models\Vente;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration (développement local uniquement) :
 * 3 fournisseurs, 4 factures, une vingtaine de motos, quelques versements.
 * Commande : php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $fournisseurs = collect(['Armado Motos', 'Soulemane Import', 'Abdoul Distribution'])
            ->map(fn (string $nom) => Fournisseur::factory()->create(['nom' => $nom]));

        $lots = [
            [0, 'FA-2026-031', 20_000, 0, [['Yamaha', 'Sirius 110', 800_000, 6], ['Yamaha', 'Sirius 115', 850_000, 4]], [1_500_000, 2_000_000]],
            [1, 'SI-0457', 7_000, 50_000, [['Apsonic', 'AP125', 650_000, 3]], [1_900_000]],
            [2, 'AD/26/112', 15_000, 0, [['Honda', 'Dream', 750_000, 2], ['Suzuki', 'Viva 115', 700_000, 3]], []],
            [0, 'FA-2026-044', 5_000, 0, [['Yamaha', 'Crypton', 780_000, 2]], [500_000]],
        ];

        foreach ($lots as [$f, $numero, $transport, $remise, $motos, $versements]) {
            $facture = FactureFournisseur::factory()->create([
                'fournisseur_id' => $fournisseurs[$f]->id,
                'numero' => $numero,
                'montant_transport' => $transport,
                'remise' => $remise,
                'date_facture' => now()->subDays(rand(5, 60)),
            ]);

            foreach ($motos as [$marque, $modele, $prix, $nombre]) {
                Moto::factory()->count($nombre)->pourFacture($facture)->create([
                    'marque' => $marque,
                    'modele' => $modele,
                    'prix_achat' => $prix,
                ]);
            }

            foreach ($versements as $montant) {
                $facture->versements()->create([
                    'date_versement' => now()->subDays(rand(1, 5)),
                    'montant' => $montant,
                    'mode' => ModePaiement::Virement,
                ]);
            }
        }

        // Clients et ventes : une soldée, une avec reste à payer, une réservation
        $clients = collect([
            ['nom' => 'Ouédraogo Issa', 'telephone' => '70 11 22 33', 'type_piece' => 'cnib', 'numero_piece' => 'B12345678'],
            ['nom' => 'Kaboré Aminata', 'telephone' => '76 44 55 66', 'type_piece' => 'cnib', 'numero_piece' => 'B87654321'],
            ['nom' => 'Garage Zida & Fils', 'telephone' => '78 99 00 11', 'type' => 'revendeur'],
        ])->map(fn (array $c) => Client::create($c));

        $stock = Moto::query()->where('statut', 'en_stock')->orderBy('id')->get();
        $vendre = function (Client $client, array $motos, array $paiements, string $statut = 'en_cours', int $commission = 0) {
            $vente = Vente::create([
                'client_id' => $client->id, 'date_vente' => now()->subDays(rand(1, 20)), 'statut' => $statut,
                'commission' => $commission, 'apporteur_nom' => $commission ? 'Sawadogo Adama' : null,
            ]);
            foreach ($motos as [$moto, $prix]) {
                $vente->lignes()->create(['moto_id' => $moto->id, 'prix_vente' => $prix,
                    'frais_immatriculation' => config('entreprise.defaut_immatriculation'), 'cout_casque' => config('entreprise.defaut_casque')]);
            }
            foreach ($paiements as $montant) {
                $vente->paiements()->create(['date_paiement' => now()->subDays(rand(0, 3)), 'montant' => $montant, 'mode' => ModePaiement::Especes]);
            }
        };

        $vendre($clients[0], [[$stock[0], 900_000]], [900_000], commission: 10_000);
        $vendre($clients[1], [[$stock[1], 975_000]], [500_000]);
        $vendre($clients[2], [[$stock[2], 880_000], [$stock[3], 880_000]], [1_000_000]);
        $vendre($clients[1], [[$stock[4], 950_000]], [100_000], 'reservation');

        // Papiers à des étapes différentes : carte grise arrivée, provisoire, récépissé
        $dossiers = \App\Models\Immatriculation::orderBy('id')->get();
        $dossiers->get(0)?->update(['date_provisoire' => now()->subDays(55), 'date_carte_grise' => now()->subDay(), 'numero_carte_grise' => 'CG-2026-0153']);
        $dossiers->get(0)?->moto->update(['plaque' => '11 KK 4521']);
        $dossiers->get(1)?->update(['date_provisoire' => now()->subDays(20)]);
        $dossiers->get(2)?->update(['date_provisoire' => now()->subDays(80), 'date_recepisse' => now()->subDays(5)]);

        // Une réparation avant vente
        Moto::query()->first()?->frais()->create([
            'type' => TypeFrais::Reparation,
            'montant' => 15_000,
            'description' => 'Réglage carburateur',
            'date_frais' => now(),
        ]);
    }
}
