<?php

return [
    'select' => [
        'actions' => [
            'clear' => ['label' => 'Effacer'],
            'remove_option' => ['label' => 'Retirer'],
        ],
        'error_message' => 'Le chargement des options a échoué.',
        'required_description' => 'Obligatoire',
        'search_label' => 'Rechercher',
    ],
    'checkbox_list' => ['required_description' => 'Obligatoire'],
    'toggle_buttons' => ['required_description' => 'Obligatoire'],
    'date_time_picker' => [
        'month_select' => ['label' => 'Mois'],
        'year_input' => ['label' => 'Année'],
        'hour_input' => ['label' => 'Heure'],
        'minute_input' => ['label' => 'Minute'],
        'second_input' => ['label' => 'Seconde'],
    ],
    'file_upload' => [
        'placeholder' => 'Glissez un fichier ici ou <span class="filepond--label-action">parcourez</span>',
        'actions' => [
            'download' => ['label' => 'Télécharger'],
            'open' => ['label' => 'Ouvrir'],
        ],
        'editor' => ['label' => 'Modifier l’image'],
    ],
];
