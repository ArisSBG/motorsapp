<?php

namespace Database\Factories;

use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FactureFournisseur> */
class FactureFournisseurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fournisseur_id' => Fournisseur::factory(),
            'numero' => 'F-'.fake()->unique()->numerify('####'),
            'date_facture' => fake()->dateTimeBetween('-3 months'),
            'montant_transport' => 0,
            'remise' => 0,
        ];
    }
}
