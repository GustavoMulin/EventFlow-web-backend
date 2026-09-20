<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Local;
use Illuminate\Database\Seeder;

class EventoSeeder extends Seeder
{
    public function run(): void
    {
        $eventos = [
            ['Semana de Tecnologia', 'Palestras, oficinas e networking sobre desenvolvimento web, nuvem e inteligência artificial.', 'Tecnologia', 'Praça da Sé', 7, '09:00', 0, 200],
            ['Festival de Música ao Vivo', 'Bandas locais e convidados em uma noite de música ao ar livre.', 'Música', 'Estádio do Maracanã', 14, '19:30', 80, 500],
            ['Corrida de Rua 10K', 'Percurso de 10 km com kit do atleta, medalha e hidratação.', 'Esporte', 'Praça dos Três Poderes', 21, '06:30', 45.90, 300],
            ['Workshop de Laravel e Vue', 'Curso prático de construção de APIs com Laravel e interfaces com Vue 3.', 'Educação', 'Teatro Amazonas', 10, '14:00', 120, 30],
            ['Feira Gastronômica Amazônica', 'Pratos típicos, ingredientes regionais e degustações.', 'Gastronomia', 'Mercado Ver-o-Peso', 5, '11:00', 25, 150],
            ['Encontro de Empreendedores', 'Rodadas de negócios, painéis e mentorias para novos empreendedores.', 'Negócios', 'Centro de Porto Velho', 30, '18:00', 60, 80],
            ['Hackathon EventFlow', 'Maratona de programação de 24 horas com premiação para as melhores soluções.', 'Tecnologia', 'Teatro Amazonas', 45, '08:00', 0, 5],
            ['Noite de Jazz', 'Apresentações intimistas de jazz e blues com repertório clássico.', 'Música', 'Praça da Sé', 18, '20:00', 55, 120],
        ];

        foreach ($eventos as [$nome, $descricao, $categoria, $local, $dias, $hora, $preco, $vagas]) {
            Evento::create([
                'nome' => $nome,
                'descricao' => $descricao,
                'data_evento' => now()->addDays($dias)->format('Y-m-d'),
                'hora_evento' => $hora,
                'preco' => $preco,
                'vagas' => $vagas,
                'categoria_id' => Categoria::where('nome', $categoria)->value('id'),
                'local_id' => Local::where('nome', $local)->value('id'),
            ]);
        }

        // Algumas inscrições de exemplo (o Hackathon fica lotado para demonstrar "vagas esgotadas").
        $hackathon = Evento::where('nome', 'Hackathon EventFlow')->firstOrFail();
        Inscricao::factory()->count($hackathon->vagas)->create(['evento_id' => $hackathon->id]);

        $semana = Evento::where('nome', 'Semana de Tecnologia')->firstOrFail();
        Inscricao::factory()->count(12)->create(['evento_id' => $semana->id]);
    }
}
