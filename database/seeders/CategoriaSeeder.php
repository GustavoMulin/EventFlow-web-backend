<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Tecnologia', 'Música', 'Esporte', 'Educação', 'Gastronomia', 'Negócios'] as $nome) {
            Categoria::firstOrCreate(['nome' => $nome]);
        }
    }
}
