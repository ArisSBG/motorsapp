<?php

namespace App\Services;

use App\Enums\TypeFrais;
use App\Models\FactureFournisseur;
use App\Models\FraisMoto;
use Illuminate\Support\Facades\DB;

/**
 * Répartit le transport d'une facture à parts égales entre ses motos
 * (choix du client, questionnaire 4.11).
 *
 * Exemple : 10.000 FCFA pour 3 motos → 3.334 + 3.333 + 3.333.
 * Le reste de la division va aux premières motos, pour que la somme soit exacte.
 */
class RepartitionTransport
{
    public function repartir(?FactureFournisseur $facture): void
    {
        if ($facture === null || ! $facture->exists) {
            return;
        }

        DB::transaction(function () use ($facture) {
            FraisMoto::query()
                ->where('facture_fournisseur_id', $facture->id)
                ->where('type', TypeFrais::Transport)
                ->delete();

            if ($facture->trashed()) {
                return;
            }

            $motos = $facture->motos()->orderBy('id')->get(['id']);
            $nombre = $motos->count();
            $montant = (int) $facture->montant_transport;

            if ($nombre === 0 || $montant === 0) {
                return;
            }

            $part = intdiv($montant, $nombre);
            $reste = $montant % $nombre;

            foreach ($motos->values() as $i => $moto) {
                FraisMoto::create([
                    'moto_id' => $moto->id,
                    'type' => TypeFrais::Transport,
                    'montant' => $part + ($i < $reste ? 1 : 0),
                    'description' => "Part du transport — facture {$facture->numero}",
                    'date_frais' => $facture->date_facture,
                    'facture_fournisseur_id' => $facture->id,
                ]);
            }
        });
    }
}
