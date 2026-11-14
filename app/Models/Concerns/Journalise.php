<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Journal : toute création, modification ou suppression est enregistrée
 * avec l'auteur, la date et l'ancienne valeur (cahier des charges, 6.1).
 */
trait Journalise
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(static::nomJournal());
    }

    protected static function nomJournal(): string
    {
        return str(class_basename(static::class))->snake()->toString();
    }
}
