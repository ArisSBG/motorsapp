<?php

/**
 * Réglages Livewire propres à BURKINA MOTORS (les autres valeurs restent celles par défaut).
 */
return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        // 20 Mo comme annoncé à l'écran (12 Mo par défaut), et seulement des PDF ou des images
        'rules' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,heic'],
        'directory' => null,
        // Limite les envois en rafale : 30 fichiers par minute et par personne
        'middleware' => 'throttle:30,1',
        // Aperçus autorisés : images courantes seulement (pas de SVG, qui peut contenir du script)
        'preview_mimes' => ['png', 'jpg', 'jpeg', 'webp'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
