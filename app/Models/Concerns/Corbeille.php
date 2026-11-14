<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pas d'effacement direct : un élément supprimé va dans la corbeille
 * et n'est effacé définitivement qu'après 30 jours (commande model:prune, planifiée chaque nuit).
 */
trait Corbeille
{
    use Prunable, SoftDeletes;

    public const JOURS_CORBEILLE = 30;

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(self::JOURS_CORBEILLE));
    }
}
