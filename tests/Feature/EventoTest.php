<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Local;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventoTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Workshop de Vue',
            'descricao' => 'Curso prático de Vue 3.',
            'data_evento' => now()->addDays(10)->format('Y-m-d'),
            'hora_evento' => '19:30',
            'preco' => '49.90',
            'vagas' => 40,
            'categoria_id' => Categoria::factory()->create()->id,
            'local_id' => Local::factory()->create()->id,
        ], $extra);
    }

    /** @var array<string, string> */
    private array $json = ['Accept' => 'application/json'];

    // ---------- leitura pública ----------

    public function test_listagem_publica_e_paginada(): void
    {
        Evento::factory()->count(15)->create();

        $this->getJson('/api/eventos')
            ->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonStructure(['data' => [['id', 'nome', 'data_evento', 'hora_evento', 'preco', 'banner_url', 'categoria' => ['nome'], 'local' => ['latitude', 'longitude']]]]);
    }

    public function test_por_pagina_e_respeitado(): void
    {
        Evento::factory()->count(5)->create();

        $this->getJson('/api/eventos?por_pagina=2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/eventos?por_pagina=500')->assertStatus(422)->assertJsonValidationErrors('por_pagina');
    }

    public function test_busca_por_nome(): void
    {
        Evento::factory()->create(['nome' => 'Festival de Jazz']);
        Evento::factory()->create(['nome' => 'Corrida de Rua']);

        $this->getJson('/api/eventos?busca=jazz')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Festival de Jazz');
    }

    public function test_filtro_por_categoria(): void
    {
        $categoria = Categoria::factory()->create();
        Evento::factory()->count(2)->create(['categoria_id' => $categoria->id]);
        Evento::factory()->count(3)->create();

        $this->getJson("/api/eventos?categoria_id={$categoria->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_detalhe_mostra_categoria_local_e_vagas_restantes(): void
    {
        $evento = Evento::factory()->create(['vagas' => 10, 'endereco' => null]);
        Inscricao::factory()->count(3)->create(['evento_id' => $evento->id]);

        $this->getJson("/api/eventos/{$evento->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $evento->id)
            ->assertJsonPath('data.vagas', 10)
            ->assertJsonPath('data.inscritos', 3)
            ->assertJsonPath('data.vagas_restantes', 7)
            ->assertJsonPath('data.categoria.id', $evento->categoria_id)
            ->assertJsonPath('data.local.id', $evento->local_id)
            ->assertJsonPath('data.endereco', $evento->local->endereco);
    }

    public function test_detalhe_de_evento_inexistente_retorna_404(): void
    {
        $this->getJson('/api/eventos/9999')->assertNotFound()->assertJsonPath('message', 'Recurso não encontrado.');
    }

    // ---------- criação ----------

    public function test_visitante_nao_pode_criar_evento(): void
    {
        $this->postJson('/api/eventos', $this->dadosValidos())->assertUnauthorized();
    }

    public function test_usuario_cria_evento_com_banner(): void
    {
        Storage::fake('public');
        $this->logar();

        $resposta = $this->post('/api/eventos', $this->dadosValidos([
            'banner' => UploadedFile::fake()->create('banner.jpg', 200, 'image/jpeg'),
        ]), $this->json)
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Workshop de Vue')
            ->assertJsonPath('data.hora_evento', '19:30')
            ->assertJsonPath('data.preco', 49.9);

        $evento = Evento::firstOrFail();
        $this->assertNotNull($evento->banner);
        Storage::disk('public')->assertExists($evento->banner);
        $this->assertStringContainsString('storage/banners/', $resposta->json('data.banner_url'));
    }

    public function test_usuario_cria_evento_sem_banner(): void
    {
        $this->logar();

        $this->postJson('/api/eventos', $this->dadosValidos())
            ->assertCreated()
            ->assertJsonPath('data.banner_url', null);
    }

    public function test_valida_campos_obrigatorios(): void
    {
        $this->logar();

        $this->postJson('/api/eventos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'descricao', 'data_evento', 'hora_evento', 'preco', 'vagas', 'categoria_id', 'local_id']);
    }

    public function test_valida_categoria_local_e_formatos(): void
    {
        $this->logar();

        $this->postJson('/api/eventos', $this->dadosValidos([
            'categoria_id' => 9999,
            'local_id' => 9999,
            'hora_evento' => '25:99',
            'preco' => -5,
            'vagas' => 0,
        ]))->assertStatus(422)->assertJsonValidationErrors(['categoria_id', 'local_id', 'hora_evento', 'preco', 'vagas']);
    }

    public function test_banner_precisa_ser_imagem(): void
    {
        Storage::fake('public');
        $this->logar();

        $this->post('/api/eventos', $this->dadosValidos([
            'banner' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]), $this->json)->assertStatus(422)->assertJsonValidationErrors('banner');
    }

    public function test_banner_nao_pode_passar_de_2mb(): void
    {
        Storage::fake('public');
        $this->logar();

        $this->post('/api/eventos', $this->dadosValidos([
            'banner' => UploadedFile::fake()->create('grande.jpg', 3000, 'image/jpeg'),
        ]), $this->json)->assertStatus(422)->assertJsonValidationErrors('banner');
    }

    // ---------- edição ----------

    public function test_usuario_edita_evento(): void
    {
        $this->logar();
        $evento = Evento::factory()->create();

        $this->putJson("/api/eventos/{$evento->id}", $this->dadosValidos(['nome' => 'Nome atualizado']))
            ->assertOk()
            ->assertJsonPath('data.nome', 'Nome atualizado');

        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'nome' => 'Nome atualizado']);
    }

    public function test_edicao_com_upload_troca_o_banner_e_apaga_o_antigo(): void
    {
        Storage::fake('public');
        $this->logar();

        $antigo = UploadedFile::fake()->create('antigo.jpg', 50, 'image/jpeg')->store('banners', 'public');
        $evento = Evento::factory()->create(['banner' => $antigo]);

        // multipart não funciona com PUT, então o front envia POST com _method=PUT
        $this->post("/api/eventos/{$evento->id}", $this->dadosValidos([
            '_method' => 'PUT',
            'banner' => UploadedFile::fake()->create('novo.png', 50, 'image/png'),
        ]), $this->json)->assertOk();

        $evento->refresh();
        $this->assertNotSame($antigo, $evento->banner);
        Storage::disk('public')->assertMissing($antigo);
        Storage::disk('public')->assertExists($evento->banner);
    }

    public function test_edicao_pode_remover_o_banner(): void
    {
        Storage::fake('public');
        $this->logar();

        $antigo = UploadedFile::fake()->create('antigo.jpg', 50, 'image/jpeg')->store('banners', 'public');
        $evento = Evento::factory()->create(['banner' => $antigo]);

        $this->post("/api/eventos/{$evento->id}", $this->dadosValidos([
            '_method' => 'PUT',
            'remover_banner' => '1',
        ]), $this->json)->assertOk()->assertJsonPath('data.banner_url', null);

        $this->assertNull($evento->fresh()->banner);
        Storage::disk('public')->assertMissing($antigo);
    }

    public function test_nao_reduz_vagas_abaixo_do_numero_de_inscritos(): void
    {
        $this->logar();
        $evento = Evento::factory()->create(['vagas' => 10]);
        Inscricao::factory()->count(5)->create(['evento_id' => $evento->id]);

        $this->putJson("/api/eventos/{$evento->id}", $this->dadosValidos(['vagas' => 3]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('vagas');

        $this->putJson("/api/eventos/{$evento->id}", $this->dadosValidos(['vagas' => 5]))->assertOk();
    }

    // ---------- exclusão ----------

    public function test_visitante_nao_pode_excluir_evento(): void
    {
        $evento = Evento::factory()->create();

        $this->deleteJson("/api/eventos/{$evento->id}")->assertUnauthorized();
    }

    public function test_exclusao_remove_evento_banner_e_inscricoes(): void
    {
        Storage::fake('public');
        $this->logar();

        $banner = UploadedFile::fake()->create('b.jpg', 50, 'image/jpeg')->store('banners', 'public');
        $evento = Evento::factory()->create(['banner' => $banner]);
        Inscricao::factory()->count(2)->create(['evento_id' => $evento->id]);

        $this->deleteJson("/api/eventos/{$evento->id}")->assertNoContent();

        $this->assertDatabaseMissing('eventos', ['id' => $evento->id]);
        $this->assertDatabaseCount('inscricoes', 0);
        Storage::disk('public')->assertMissing($banner);
    }
}
