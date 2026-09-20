<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco com um usuário de teste e dados de demonstração.
     *
     * Usuário: test@example.com / senha: password
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Usuário de Teste',
            'email' => 'test@example.com',
        ]);

        $this->call([
            CategoriaSeeder::class,
            LocalSeeder::class,
            EventoSeeder::class,
        ]);
    }
}
