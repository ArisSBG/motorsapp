<?php

namespace App\Models;

use App\Enums\StatutVente;
use App\Models\Concerns\Corbeille;
use App\Models\Concerns\Journalise;
use App\Models\Concerns\TotauxCalcules;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vente d'une ou plusieurs motos (un revendeur peut prendre plusieurs CMC).
 *
 * Total = somme des prix de vente.
 * Coûts = prix d'achat + frais des motos (transport, réparations) + immatriculation + casque + commission.
 * Bénéfice = total − coûts. Il n'est compté qu'à la date où la vente est soldée (questionnaire 7.4).
 */
#[Fillable([
    'numero', 'client_id', 'user_id', 'date_vente', 'statut',
    'apporteur_nom', 'apporteur_telephone', 'commission', 'soldee_le', 'motif_annulation', 'notes',
])]
class Vente extends Model
{
    use Corbeille, HasFactory, Journalise, TotauxCalcules;

    protected static function colonnesTotaux(): array
    {
        return ['total', 'paye', 'reste', 'couts', 'benefice'];
    }

    protected $attributes = [
        'statut' => 'en_cours',
        'commission' => 0,
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutVente::class,
            'date_vente' => 'date',
            'soldee_le' => 'date',
            'commission' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Vente $vente) {
            $vente->numero ??= Numerotation::suivant(self::class, 'numero', config('entreprise.prefixe_facture'));
            $vente->user_id ??= auth()->id();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vendeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(VenteLigne::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementClient::class);
    }

    public function echeances(): HasMany
    {
        return $this->hasMany(Echeance::class)->orderBy('date_prevue');
    }

    // --- Totaux en SQL (réutilisés pour les listes et les rapports) ---

    public static function sqlTotal(): string
    {
        return '(SELECT COALESCE(SUM(l.prix_vente), 0) FROM vente_lignes l WHERE l.vente_id = ventes.id)';
    }

    public static function sqlPaye(): string
    {
        return '(SELECT COALESCE(SUM(p.montant), 0) FROM paiements_client p WHERE p.vente_id = ventes.id AND p.deleted_at IS NULL)';
    }

    public static function sqlReste(): string
    {
        return '('.self::sqlTotal().' - '.self::sqlPaye().')';
    }

    public static function sqlCouts(): string
    {
        return '(SELECT COALESCE(SUM(l.frais_immatriculation + l.cout_casque + m.prix_achat'
            .' + (SELECT COALESCE(SUM(f.montant), 0) FROM frais_moto f WHERE f.moto_id = m.id)), 0)'
            .' FROM vente_lignes l JOIN motos m ON m.id = l.moto_id WHERE l.vente_id = ventes.id) + ventes.commission';
    }

    public static function sqlBenefice(): string
    {
        return '('.self::sqlTotal().' - ('.self::sqlCouts().'))';
    }

    /** Ajoute les colonnes `total`, `paye`, `reste`, `couts`, `benefice`. */
    public function scopeAvecTotaux(Builder $query): Builder
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('ventes.*');
        }

        return $query
            ->selectRaw(self::sqlTotal().' as total')
            ->selectRaw(self::sqlPaye().' as paye')
            ->selectRaw(self::sqlReste().' as reste')
            ->selectRaw('('.self::sqlCouts().') as couts')
            ->selectRaw(self::sqlBenefice().' as benefice');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('ventes.statut', '!=', StatutVente::Annulee->value);
    }

    public function total(): int
    {
        return $this->totalCalcule('total');
    }

    public function paye(): int
    {
        return $this->totalCalcule('paye');
    }

    public function reste(): int
    {
        return $this->totalCalcule('reste');
    }

    public function couts(): int
    {
        return $this->totalCalcule('couts');
    }

    public function benefice(): int
    {
        return $this->totalCalcule('benefice');
    }

    public function estModifiable(): bool
    {
        return $this->statut !== StatutVente::Annulee;
    }

    /** Papiers remis avant paiement complet si le reste dû ne dépasse pas le seuil (50.000 FCFA). */
    public function peutRemettrePapiers(): bool
    {
        return $this->reste() <= config('entreprise.seuil_remise_papiers');
    }

    public function immatriculations(): HasMany
    {
        return $this->hasMany(Immatriculation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
