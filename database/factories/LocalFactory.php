<?php

namespace Database\Factories;

use App\Models\Local;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Local>
 */
class LocalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->company(),
            'latitude' => fake()->latitude(-30, 5),
            'longitude' => fake()->longitude(-72, -36),
            'endereco' => fake()->streetAddress(),
        ];
    }
}
