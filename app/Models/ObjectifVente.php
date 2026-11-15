<?php

namespace App\Models;

use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('objectifs_vente')]
#[Fillable(['annee', 'mois', 'nombre_motos'])]
class ObjectifVente extends Model
{
    use Journalise;

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'mois' => 'integer',
            'nombre_motos' => 'integer',
        ];
    }

    public static function pour(int $annee, int $mois): ?int
    {
        return static::where(['annee' => $annee, 'mois' => $mois])->value('nombre_motos');
    }
}
