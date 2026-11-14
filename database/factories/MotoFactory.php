<?php

namespace Database\Factories;

use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\Moto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Moto> */
class MotoFactory extends Factory
{
    public function definition(): array
    {
        $modeles = [
            'Yamaha' => ['Sirius 110', 'Sirius 115', 'Crypton'],
            'Honda' => ['Dream', 'Wave 110'],
            'Apsonic' => ['AP125', 'Scooter'],
            'Suzuki' => ['Viva 115'],
        ];
        $marque = fake()->randomElement(array_keys($modeles));

        return [
            'chassis' => fake()->unique()->bothify('RLCUE######?#####'),
            'marque' => $marque,
            'modele' => fake()->randomElement($modeles[$marque]),
            'couleur' => fake()->randomElement(['Noir', 'Rouge', 'Bleu', 'Blanc', 'Gris']),
            'fournisseur_id' => Fournisseur::factory(),
            'prix_achat' => fake()->randomElement([650_000, 700_000, 750_000, 800_000, 850_000]),
            'date_reception' => fake()->dateTimeBetween('-2 months'),
        ];
    }

    /** Moto rattachée à une facture (même fournisseur). */
    public function pourFacture(FactureFournisseur $facture): static
    {
        return $this->state(fn () => [
            'fournisseur_id' => $facture->fournisseur_id,
            'facture_fournisseur_id' => $facture->id,
            'date_reception' => $facture->date_facture,
        ]);
    }
}
