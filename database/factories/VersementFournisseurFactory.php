<?php

namespace Database\Factories;

use App\Enums\ModePaiement;
use App\Models\FactureFournisseur;
use App\Models\VersementFournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VersementFournisseur> */
class VersementFournisseurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'facture_fournisseur_id' => FactureFournisseur::factory(),
            'date_versement' => now(),
            'montant' => 500_000,
            'mode' => ModePaiement::Virement,
        ];
    }
}
