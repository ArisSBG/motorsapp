<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\PaiementClient;
use App\Services\StockageDocuments;
use Illuminate\Http\Request;
use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents imprimables (A4) : facture de vente et reçu de paiement.
 * L'accès est vérifié par les règles de droits (route ->can(...)).
 */
class DocumentController extends Controller
{
    public function factureVente(Vente $vente): Response
    {
        $vente->load(['client', 'lignes.moto', 'vendeur']);

        return Pdf::loadView('documents.facture-vente', ['vente' => $vente])
            ->setPaper('a4')
            ->stream("Facture-{$vente->numero}.pdf");
    }

    public function recu(PaiementClient $paiement): Response
    {
        $paiement->load(['vente.client', 'vente.lignes.moto', 'encaissePar']);
        $vente = $paiement->vente;

        // Situation juste après ce paiement (les paiements suivants ne comptent pas)
        $payeALaDate = $vente->paiements()
            ->where(fn ($q) => $q->where('date_paiement', '<', $paiement->date_paiement)
                ->orWhere(fn ($q) => $q->where('date_paiement', $paiement->date_paiement)->where('id', '<=', $paiement->id)))
            ->sum('montant');

        return Pdf::loadView('documents.recu', [
            'paiement' => $paiement,
            'vente' => $vente,
            'payeALaDate' => (int) $payeALaDate,
            'resteApres' => $vente->total() - (int) $payeALaDate,
        ])
            ->setPaper('a4')
            ->stream("Recu-{$paiement->numero_recu}.pdf");
    }

    /** Document scanné : déchiffré à la volée, affiché dans le navigateur ou téléchargé. */
    public function fichier(Request $request, Document $document, StockageDocuments $stockage): Response
    {
        $disposition = $request->boolean('telecharger') ? 'attachment' : 'inline';
        $nom = str_replace('"', '', $document->nom_original);

        return response($stockage->lire($document), 200, [
            'Content-Type' => $document->mime,
            'Content-Disposition' => "{$disposition}; filename=\"{$nom}\"",
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
