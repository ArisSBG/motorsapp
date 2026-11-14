<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

// Accueil : vers l'espace de la personne connectée, sinon vers la connexion
Route::get('/', function () {
    $panneau = auth()->user()?->panneaux()->first();

    return redirect($panneau ? '/'.$panneau : '/gestion/login');
});

// Documents à imprimer (PDF) — connexion obligatoire, droits vérifiés pour chaque document
Route::middleware('auth')->prefix('documents')->name('documents.')->group(function () {
    Route::get('ventes/{vente}/facture', [DocumentController::class, 'factureVente'])
        ->name('facture-vente')
        ->can('view', 'vente');

    Route::get('paiements/{paiement}/recu', [DocumentController::class, 'recu'])
        ->name('recu')
        ->can('view', 'paiement');

    Route::get('fichiers/{document}', [DocumentController::class, 'fichier'])
        ->name('fichier')
        ->withTrashed()
        ->can('view', 'document');
});
