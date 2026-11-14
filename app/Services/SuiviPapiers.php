<?php

namespace App\Services;

use App\Enums\StatutImmatriculation;
use App\Enums\StatutMoto;
use App\Enums\StatutVente;
use App\Models\Immatriculation;
use App\Models\Moto;
use App\Models\Vente;

/**
 * Parcours des papiers :
 * - un dossier d'immatriculation s'ouvre pour chaque moto vendue (pas pour une simple réservation) ;
 * - le statut du dossier suit les dates saisies (provisoire, récépissé, carte grise) ;
 * - la moto avance : Vendue → Immatriculation en cours → Papiers reçus → Livrée (carte grise remise).
 */
class SuiviPapiers
{
    private const STATUTS_SUIVIS = [StatutMoto::Vendue, StatutMoto::ImmatriculationEnCours, StatutMoto::PapiersRecus, StatutMoto::Livree];

    public static function venteOuvreDesDossiers(Vente $vente): bool
    {
        return ! $vente->trashed() && in_array($vente->statut, [StatutVente::EnCours, StatutVente::Soldee], true);
    }

    public function ouvrirDossiers(Vente $vente): void
    {
        if (! self::venteOuvreDesDossiers($vente)) {
            return;
        }

        foreach ($vente->lignes()->pluck('moto_id') as $motoId) {
            $this->ouvrirDossier($motoId, $vente);
        }
    }

    public function ouvrirDossier(int $motoId, Vente $vente): Immatriculation
    {
        $dossier = Immatriculation::withTrashed()->firstOrNew(['moto_id' => $motoId]);

        // Moto revendue après une annulation : nouveau dossier, les anciennes dates ne valent plus
        if ($dossier->exists && $dossier->vente_id !== $vente->id) {
            $dossier->fill([
                'prestataire' => null, 'date_depot' => null, 'date_provisoire' => null, 'date_recepisse' => null,
                'date_carte_grise' => null, 'numero_carte_grise' => null, 'client_prevenu_le' => null,
            ]);
            $dossier->retraits()->delete();
        }
        if ($dossier->trashed()) {
            $dossier->deleted_at = null;
        }
        $dossier->vente_id = $vente->id;
        $dossier->save();

        return $dossier;
    }

    /** Vente annulée ou supprimée : les dossiers sans carte grise vont à la corbeille. */
    public function fermerDossiers(Vente $vente): void
    {
        Immatriculation::query()
            ->where('vente_id', $vente->id)
            ->where('statut', '!=', StatutImmatriculation::CarteGriseRecue)
            ->get()
            ->each->delete();
    }

    public function fermerDossierDeLaMoto(int $motoId): void
    {
        Immatriculation::query()
            ->where('moto_id', $motoId)
            ->where('statut', '!=', StatutImmatriculation::CarteGriseRecue)
            ->first()?->delete();
    }

    /** Le statut du dossier découle des dates saisies : pas de double saisie. */
    public static function statutSelonDates(Immatriculation $dossier): StatutImmatriculation
    {
        return match (true) {
            $dossier->date_carte_grise !== null => StatutImmatriculation::CarteGriseRecue,
            $dossier->date_recepisse !== null => StatutImmatriculation::RecepisseRecu,
            $dossier->date_provisoire !== null => StatutImmatriculation::ProvisoireRecu,
            default => StatutImmatriculation::DossierEnCours,
        };
    }

    /** Aligne le statut de la moto sur l'avancement de ses papiers. */
    public function synchroniserMoto(Immatriculation $dossier): void
    {
        /** @var Moto|null $moto */
        $moto = $dossier->moto;
        if ($moto === null || ! in_array($moto->statut, self::STATUTS_SUIVIS, true) || $dossier->trashed()) {
            return;
        }

        $dossier->load('retraits');
        $cible = match (true) {
            $dossier->carteGriseRemise() => StatutMoto::Livree,
            $dossier->statut === StatutImmatriculation::CarteGriseRecue => StatutMoto::PapiersRecus,
            in_array($dossier->statut, [StatutImmatriculation::ProvisoireRecu, StatutImmatriculation::RecepisseRecu], true) => StatutMoto::ImmatriculationEnCours,
            default => StatutMoto::Vendue,
        };

        if ($moto->statut !== $cible) {
            $moto->update(['statut' => $cible]);
        }
    }
}
