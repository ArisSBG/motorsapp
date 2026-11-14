<?php

use Illuminate\Support\Facades\Schedule;

// Chaque nuit (heure de Ouagadougou) : sauvegarde chiffrée, nettoyage des anciennes,
// vérification, et effacement définitif des éléments restés plus de 30 jours dans la corbeille.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('model:prune')->dailyAt('02:00');
Schedule::command('backup:monitor')->dailyAt('03:00');
