<?php

namespace Database\Seeders;

use App\Models\Local;
use Illuminate\Database\Seeder;

class LocalSeeder extends Seeder
{
    public function run(): void
    {
        $locais = [
            ['nome' => 'Praça da Sé', 'latitude' => -23.5505000, 'longitude' => -46.6333000, 'endereco' => 'Praça da Sé, Sé, São Paulo - SP'],
            ['nome' => 'Estádio do Maracanã', 'latitude' => -22.9121000, 'longitude' => -43.2302000, 'endereco' => 'Maracanã, Rio de Janeiro - RJ'],
            ['nome' => 'Praça dos Três Poderes', 'latitude' => -15.7998000, 'longitude' => -47.8645000, 'endereco' => 'Praça dos Três Poderes, Brasília - DF'],
            ['nome' => 'Teatro Amazonas', 'latitude' => -3.1303000, 'longitude' => -60.0233000, 'endereco' => 'Centro, Manaus - AM'],
            ['nome' => 'Mercado Ver-o-Peso', 'latitude' => -1.4526000, 'longitude' => -48.5030000, 'endereco' => 'Ver-o-Peso, Belém - PA'],
            ['nome' => 'Centro de Porto Velho', 'latitude' => -8.7619000, 'longitude' => -63.9039000, 'endereco' => 'Centro, Porto Velho - RO'],
        ];

        foreach ($locais as $local) {
            Local::firstOrCreate(['nome' => $local['nome']], $local);
        }
    }
}
