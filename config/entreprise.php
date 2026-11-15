<?php

/**
 * Coordonnées et réglages de BURKINA MOTORS, utilisés sur les factures et reçus.
 * À compléter dans .env avec les vraies informations du client.
 */
return [
    'nom' => env('ENTREPRISE_NOM', 'BURKINA MOTORS'),
    'adresse' => env('ENTREPRISE_ADRESSE', 'Koupéla, Burkina Faso'),
    'telephone' => env('ENTREPRISE_TELEPHONE', '72 72 31 31'),
    'ifu' => env('ENTREPRISE_IFU'),
    'rccm' => env('ENTREPRISE_RCCM'),

    // Numérotation automatique (questionnaire 13.4 : « Burkina-motors-2026 »)
    'prefixe_facture' => env('ENTREPRISE_PREFIXE_FACTURE', 'BURKINA-MOTORS'),
    'prefixe_recu' => env('ENTREPRISE_PREFIXE_RECU', 'REC'),

    // Valeurs proposées par défaut à la saisie d'une vente (modifiables à chaque vente)
    'defaut_immatriculation' => (int) env('DEFAUT_IMMATRICULATION', 41000),
    'defaut_casque' => (int) env('DEFAUT_CASQUE', 6500),

    // Papiers remis avant paiement complet si le reste dû ne dépasse pas ce montant (questionnaire 6.7)
    'seuil_remise_papiers' => (int) env('SEUIL_REMISE_PAPIERS', 50000),

    // Premier compte administrateur créé par le seeder (à retirer du .env après l'installation)
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),

    // Alerte quand une moto vendue n'a toujours pas sa carte grise après N jours (proposition : 90)
    'jours_alerte_papiers' => (int) env('JOURS_ALERTE_PAPIERS', 90),
];
