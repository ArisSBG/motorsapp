<?php

namespace App\Models;

use App\Enums\DocumentRemis;
use App\Models\Concerns\Journalise;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Remise d'un papier : date, personne qui retire (le client ou un autre), document remis. */
#[Fillable(['immatriculation_id', 'user_id', 'date_retrait', 'document', 'retire_par', 'est_le_client', 'telephone', 'notes'])]
class Retrait extends Model
{
    use HasFactory, Journalise;

    protected function casts(): array
    {
        return [
            'date_retrait' => 'date',
            'document' => DocumentRemis::class,
            'est_le_client' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Retrait $r) => $r->user_id ??= auth()->id());
    }

    public function immatriculation(): BelongsTo
    {
        return $this->belongsTo(Immatriculation::class);
    }

    public function remisPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
