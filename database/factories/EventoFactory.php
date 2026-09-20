<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Local;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evento>
 */
class EventoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->sentence(3),
            'descricao' => fake()->paragraph(),
            'data_evento' => fake()->dateTimeBetween('+1 day', '+3 months')->format('Y-m-d'),
            'hora_evento' => fake()->time('H:i'),
            'preco' => fake()->randomFloat(2, 0, 300),
            'endereco' => null,
            'vagas' => fake()->numberBetween(5, 200),
            'banner' => null,
            'categoria_id' => Categoria::factory(),
            'local_id' => Local::factory(),
        ];
    }
}
