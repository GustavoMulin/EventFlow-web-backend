<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscricaoTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    /**
     * @return array<string, string>
     */
    private function participante(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Maria Souza',
            'email' => 'maria@example.com',
            'documento' => '123.456.789-00',
        ], $extra);
    }

    public function test_visitante_pode_se_inscrever_em_um_evento(): void
    {
        $evento = Evento::factory()->create(['vagas' => 5]);

        $resposta = $this->postJson("/api/eventos/{$evento->id}/inscricoes", $this->participante())
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Maria Souza')
            ->assertJsonPath('data.email', 'maria@example.com')
            ->assertJsonPath('data.evento.id', $evento->id)
            ->assertJsonStructure(['data' => ['codigo', 'inscrito_em']]);

        $this->assertDatabaseHas('inscricoes', [
            'evento_id' => $evento->id,
            'email' => 'maria@example.com',
            'codigo' => $resposta->json('data.codigo'),
        ]);
    }

    public function test_valida_dados_do_participante(): void
    {
        $evento = Evento::factory()->create();

        $this->postJson("/api/eventos/{$evento->id}/inscricoes", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'email']);

        $this->postJson("/api/eventos/{$evento->id}/inscricoes", $this->participante(['email' => 'invalido']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_nao_permite_o_mesmo_email_duas_vezes_no_mesmo_evento(): void
    {
        $evento = Evento::factory()->create();
        Inscricao::factory()->create(['evento_id' => $evento->id, 'email' => 'maria@example.com']);

        $this->postJson("/api/eventos/{$evento->id}/inscricoes", $this->participante())
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        // O mesmo e-mail pode se inscrever em outro evento.
        $outro = Evento::factory()->create();
        $this->postJson("/api/eventos/{$outro->id}/inscricoes", $this->participante())->assertCreated();
    }

    public function test_bloqueia_inscricao_quando_as_vagas_acabaram(): void
    {
        $evento = Evento::factory()->create(['vagas' => 2]);
        Inscricao::factory()->count(2)->create(['evento_id' => $evento->id]);

        $this->postJson("/api/eventos/{$evento->id}/inscricoes", $this->participante())
            ->assertStatus(422)
            ->assertJsonValidationErrors('evento');

        $this->assertDatabaseCount('inscricoes', 2);
    }

    public function test_bloqueia_inscricao_em_evento_que_ja_aconteceu(): void
    {
        $evento = Evento::factory()->create(['data_evento' => now()->subDays(3)->format('Y-m-d')]);

        $this->postJson("/api/eventos/{$evento->id}/inscricoes", $this->participante())
            ->assertStatus(422)
            ->assertJsonValidationErrors('evento');
    }

    public function test_apenas_usuario_logado_lista_os_inscritos(): void
    {
        $evento = Evento::factory()->create();
        Inscricao::factory()->count(3)->create(['evento_id' => $evento->id]);

        $this->getJson("/api/eventos/{$evento->id}/inscricoes")->assertUnauthorized();

        $this->logar();
        $this->getJson("/api/eventos/{$evento->id}/inscricoes")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['codigo', 'nome', 'email', 'documento', 'inscrito_em']]]);
    }

    public function test_baixa_o_ingresso_em_pdf_pelo_codigo(): void
    {
        $inscricao = Inscricao::factory()->create(['nome' => 'João da Silva', 'documento' => '000.000.000-00']);

        $resposta = $this->get("/api/inscricoes/{$inscricao->codigo}/ingresso");

        $resposta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $resposta->getContent());
        $this->assertStringContainsString('attachment', (string) $resposta->headers->get('content-disposition'));
    }

    public function test_ingresso_com_codigo_inexistente_retorna_404(): void
    {
        $this->get('/api/inscricoes/00000000-0000-0000-0000-000000000000/ingresso')->assertNotFound();
    }

    public function test_ingresso_nao_e_acessivel_pelo_id_numerico(): void
    {
        $inscricao = Inscricao::factory()->create();

        $this->get("/api/inscricoes/{$inscricao->id}/ingresso")->assertNotFound();
    }

    public function test_usuario_cancela_uma_inscricao(): void
    {
        $inscricao = Inscricao::factory()->create();

        $this->deleteJson("/api/inscricoes/{$inscricao->codigo}")->assertUnauthorized();

        $this->logar();
        $this->deleteJson("/api/inscricoes/{$inscricao->codigo}")->assertNoContent();
        $this->assertDatabaseMissing('inscricoes', ['id' => $inscricao->id]);
    }
}
