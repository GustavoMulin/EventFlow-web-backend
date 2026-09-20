<?php

namespace Database\Factories;

use App\Models\Evento;
use App\Models\Inscricao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscricao>
 */
class InscricaoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evento_id' => Evento::factory(),
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'documento' => null,
        ];
    }
}
