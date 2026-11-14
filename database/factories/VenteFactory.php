<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Moto;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vente> */
class VenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'date_vente' => now(),
        ];
    }

    /** Ajoute une moto vendue à ce prix (coûts : immatriculation et casque). */
    public function avecMoto(?Moto $moto = null, int $prix = 900_000, int $immatriculation = 41_000, int $casque = 6_500): static
    {
        return $this->afterCreating(function (Vente $vente) use ($moto, $prix, $immatriculation, $casque) {
            $vente->lignes()->create([
                'moto_id' => ($moto ?? Moto::factory()->create())->id,
                'prix_vente' => $prix,
                'frais_immatriculation' => $immatriculation,
                'cout_casque' => $casque,
            ]);
        });
    }
}
