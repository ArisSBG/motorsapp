<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'telephone' => '7'.fake()->numerify('# ## ## ##'),
            'adresse' => fake()->randomElement(['Secteur 1', 'Secteur 3', 'Koulwoko', 'Zaïtenga']),
            'type' => 'particulier',
            'type_piece' => 'cnib',
            'numero_piece' => 'B'.fake()->numerify('########'),
        ];
    }
}
