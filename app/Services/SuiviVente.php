<?php

namespace App\Services;

use App\Enums\StatutMoto;
use App\Enums\StatutVente;
use App\Models\Moto;
use App\Models\Vente;

/**
 * Garde cohérents les statuts des ventes et des motos :
 * - une vente entièrement payée passe « Soldée » (le bénéfice compte à la date du dernier paiement) ;
 * - une moto vendue passe « Vendue », réservée « Réservée », et revient « En stock » si la vente est annulée.
 */
class SuiviVente
{
    /** Statuts qu'une vente peut donner à une moto ; au-delà, la moto suit le parcours de ses papiers. */
    private const STATUTS_PILOTES = [StatutMoto::EnStock, StatutMoto::Reservee, StatutMoto::Vendue];

    /** Une annulation peut aussi remettre en stock une moto dont l'immatriculation venait de commencer. */
    private const STATUTS_ANNULABLES = [StatutMoto::Reservee, StatutMoto::Vendue, StatutMoto::ImmatriculationEnCours];

    public function recalculer(Vente $vente): void
    {
        $vente->rafraichirTotaux();

        if (in_array($vente->statut, [StatutVente::Annulee, StatutVente::Reservation], true)) {
            $vente->soldee_le = null;
        } elseif ($vente->total() > 0 && $vente->reste() <= 0) {
            $vente->statut = StatutVente::Soldee;
            $vente->soldee_le = $vente->paiements()->max('date_paiement');
        } else {
            $vente->statut = StatutVente::EnCours;
            $vente->soldee_le = null;
        }

        if ($vente->isDirty()) {
            $vente->save();
        }
    }

    /** Aligne le statut des motos de la vente sur celui de la vente. */
    public function synchroniserMotos(Vente $vente): void
    {
        $cible = $vente->trashed() ? StatutMoto::EnStock : $vente->statut->statutMoto();

        $vente->lignes()->with('moto')->get()->each(fn ($ligne) => $this->changerStatut($ligne->moto, $cible));
    }

    public function changerStatut(?Moto $moto, StatutMoto $cible): void
    {
        if ($moto === null || $moto->statut === $cible) {
            return;
        }

        // Ne jamais faire reculer une moto dont les papiers avancent ; une moto livrée ne bouge plus
        $autorises = $cible === StatutMoto::EnStock ? self::STATUTS_ANNULABLES : self::STATUTS_PILOTES;
        if (! in_array($moto->statut, $autorises, true)) {
            return;
        }

        $moto->update(['statut' => $cible]);
    }

    public function liberer(?Moto $moto): void
    {
        $this->changerStatut($moto, StatutMoto::EnStock);
    }
}
