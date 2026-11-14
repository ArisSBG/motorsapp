<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fournisseur> */
class FournisseurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'telephone' => '7'.fake()->numerify('# ## ## ##'),
            'adresse' => fake()->randomElement(['Koupéla', 'Ouagadougou', 'Bobo-Dioulasso', 'Tenkodogo']),
        ];
    }
}
