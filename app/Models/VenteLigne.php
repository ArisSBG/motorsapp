<?php

namespace App\Models;

use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une moto dans une vente, avec son prix et ses coûts propres. */
#[Table('vente_lignes')]
#[Fillable(['vente_id', 'moto_id', 'prix_vente', 'frais_immatriculation', 'cout_casque'])]
class VenteLigne extends Model
{
    use HasFactory, Journalise;

    protected $attributes = [
        'frais_immatriculation' => 0,
        'cout_casque' => 0,
    ];

    protected function casts(): array
    {
        return [
            'prix_vente' => 'integer',
            'frais_immatriculation' => 'integer',
            'cout_casque' => 'integer',
        ];
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class)->withTrashed();
    }

    /** Bénéfice sur cette moto (hors commission, qui est au niveau de la vente). */
    public function benefice(): int
    {
        return $this->prix_vente - $this->frais_immatriculation - $this->cout_casque - $this->moto->coutDeRevient();
    }
}
