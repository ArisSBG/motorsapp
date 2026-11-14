<?php

namespace Tests\Feature;

use App\Enums\DocumentRemis;
use App\Enums\StatutImmatriculation;
use App\Enums\StatutMoto;
use App\Enums\StatutVente;
use App\Enums\TypeDocument;
use App\Models\Document;
use App\Models\Immatriculation;
use App\Models\Moto;
use App\Models\Vente;
use App\Services\StockageDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Règles du lot 3 : dossiers d'immatriculation, retraits, documents chiffrés.
 */
class PapiersTest extends TestCase
{
    use RefreshDatabase;

    private function vendre(?Moto $moto = null, array $attributs = []): Vente
    {
        return Vente::factory()->avecMoto($moto ?? Moto::factory()->create())->create($attributs);
    }

    public function test_un_dossier_s_ouvre_a_la_vente_mais_pas_pour_une_reservation(): void
    {
        $vendue = Moto::factory()->create();
        $reservee = Moto::factory()->create();

        $this->vendre($vendue);
        $reservation = $this->vendre($reservee, ['statut' => StatutVente::Reservation]);

        $this->assertNotNull($vendue->immatriculation);
        $this->assertSame(StatutImmatriculation::DossierEnCours, $vendue->immatriculation->statut);
        $this->assertNull($reservee->immatriculation);

        // Confirmation de la réservation : le dossier s'ouvre
        $reservation->update(['statut' => StatutVente::EnCours]);
        $this->assertNotNull($reservee->fresh()->immatriculation);
    }

    public function test_le_statut_du_dossier_et_de_la_moto_suit_les_papiers(): void
    {
        $moto = Moto::factory()->create();
        $this->vendre($moto);
        $dossier = $moto->immatriculation;

        $dossier->update(['date_provisoire' => now()]);
        $this->assertSame(StatutImmatriculation::ProvisoireRecu, $dossier->fresh()->statut);
        $this->assertSame(StatutMoto::ImmatriculationEnCours, $moto->fresh()->statut);
        $this->assertSame(now()->addDays(60)->toDateString(), $dossier->fresh()->provisoireExpireLe()->toDateString());

        $dossier->update(['date_recepisse' => now()]);
        $this->assertSame(StatutImmatriculation::RecepisseRecu, $dossier->fresh()->statut);

        $dossier->update(['date_carte_grise' => now(), 'numero_carte_grise' => 'CG-001']);
        $this->assertSame(StatutImmatriculation::CarteGriseRecue, $dossier->fresh()->statut);
        $this->assertSame(StatutMoto::PapiersRecus, $moto->fresh()->statut);
        $this->assertTrue(Immatriculation::aRemettre()->whereKey($dossier->id)->exists());

        // Le client récupère sa carte grise : moto livrée
        $retrait = $dossier->retraits()->create([
            'date_retrait' => now(), 'document' => DocumentRemis::CarteGrise, 'retire_par' => 'Frère du client', 'est_le_client' => false,
        ]);
        $this->assertSame(StatutMoto::Livree, $moto->fresh()->statut);
        $this->assertFalse(Immatriculation::aRemettre()->whereKey($dossier->id)->exists());
        $this->assertTrue(Immatriculation::remis()->whereKey($dossier->id)->exists());

        // Retrait enregistré par erreur puis supprimé : retour à « Papiers reçus »
        $retrait->delete();
        $this->assertSame(StatutMoto::PapiersRecus, $moto->fresh()->statut);
    }

    public function test_un_paiement_ne_fait_pas_reculer_une_moto_en_immatriculation(): void
    {
        $moto = Moto::factory()->create();
        $vente = $this->vendre($moto);
        $moto->immatriculation->update(['date_provisoire' => now()]);

        $vente->paiements()->create(['date_paiement' => now(), 'montant' => $vente->total(), 'mode' => 'especes']);

        $this->assertSame(StatutVente::Soldee, $vente->fresh()->statut);
        $this->assertSame(StatutMoto::ImmatriculationEnCours, $moto->fresh()->statut);
    }

    public function test_annulation_ferme_le_dossier_et_remet_la_moto_en_stock(): void
    {
        $moto = Moto::factory()->create();
        $vente = $this->vendre($moto);
        $moto->immatriculation->update(['date_provisoire' => now()]);

        $vente->update(['statut' => StatutVente::Annulee]);

        $this->assertSame(StatutMoto::EnStock, $moto->fresh()->statut);
        $this->assertNull($moto->fresh()->immatriculation);
        $this->assertSame(1, Immatriculation::onlyTrashed()->count());

        // Revendue plus tard : une seule ligne par moto, remise à zéro pour la nouvelle vente
        $nouvelle = $this->vendre($moto);
        $this->assertSame(1, Immatriculation::withTrashed()->count());
        $dossier = $moto->fresh()->immatriculation;
        $this->assertSame($nouvelle->id, $dossier->vente_id);
        $this->assertNull($dossier->date_provisoire);
        $this->assertSame(StatutImmatriculation::DossierEnCours, $dossier->statut);
        $this->assertSame(StatutMoto::Vendue, $moto->fresh()->statut);
    }

    public function test_dossier_en_retard_apres_le_delai(): void
    {
        $this->vendre();
        $dossier = Immatriculation::first();

        $this->assertFalse(Immatriculation::enRetard()->exists());

        $this->travel(config('entreprise.jours_alerte_papiers') + 1)->days();
        $this->assertTrue(Immatriculation::enRetard()->exists());

        $dossier->update(['date_carte_grise' => now()]);
        $this->assertFalse(Immatriculation::enRetard()->exists());
    }

    public function test_les_documents_sont_chiffres_sur_le_disque_et_relus_en_clair(): void
    {
        Storage::fake(Document::DISQUE);
        $contenu = '%PDF-1.4 carte grise de test';
        Storage::disk(Document::DISQUE)->put('scans/cg.pdf', $contenu);

        $moto = Moto::factory()->create();
        $document = Document::create([
            'type' => TypeDocument::CarteGrise, 'titre' => 'Carte grise', 'chemin' => 'scans/cg.pdf',
            'nom_original' => 'cg.pdf', 'mime' => 'application/pdf', 'taille' => 0,
        ]);
        $document->motos()->attach($moto);

        $document->refresh();
        $this->assertTrue($document->chiffre);
        $this->assertSame(strlen($contenu), $document->taille);
        $this->assertStringNotContainsString('carte grise', Storage::disk(Document::DISQUE)->get('scans/cg.pdf'));
        $this->assertSame($contenu, app(StockageDocuments::class)->lire($document));
        $this->assertTrue($moto->documents->contains($document));

        // Supprimé définitivement : le fichier disparaît du disque
        $document->forceDelete();
        Storage::disk(Document::DISQUE)->assertMissing('scans/cg.pdf');
    }
}
