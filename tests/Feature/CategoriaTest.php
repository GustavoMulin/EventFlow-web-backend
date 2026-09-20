<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_listagem_e_publica_e_ordenada_por_nome(): void
    {
        Categoria::factory()->create(['nome' => 'Música']);
        Categoria::factory()->create(['nome' => 'Arte']);

        $this->getJson('/api/categorias')
            ->assertOk()
            ->assertJsonPath('data.0.nome', 'Arte')
            ->assertJsonPath('data.1.nome', 'Música');
    }

    public function test_visitante_nao_pode_criar_categoria(): void
    {
        $this->postJson('/api/categorias', ['nome' => 'Teatro'])->assertUnauthorized();
    }

    public function test_usuario_cria_categoria(): void
    {
        $this->logar();

        $this->postJson('/api/categorias', ['nome' => 'Teatro'])
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Teatro');

        $this->assertDatabaseHas('categorias', ['nome' => 'Teatro']);
    }

    public function test_nome_e_obrigatorio_e_unico(): void
    {
        $this->logar();
        Categoria::factory()->create(['nome' => 'Teatro']);

        $this->postJson('/api/categorias', [])->assertStatus(422)->assertJsonValidationErrors('nome');
        $this->postJson('/api/categorias', ['nome' => 'Teatro'])->assertStatus(422)->assertJsonValidationErrors('nome');
    }

    public function test_usuario_edita_categoria(): void
    {
        $this->logar();
        $categoria = Categoria::factory()->create(['nome' => 'Teatro']);

        // Manter o mesmo nome ao editar é permitido (a regra unique ignora o próprio registro).
        $this->putJson("/api/categorias/{$categoria->id}", ['nome' => 'Teatro'])->assertOk();
        $this->putJson("/api/categorias/{$categoria->id}", ['nome' => 'Dança'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Dança');
    }

    public function test_usuario_exclui_categoria_sem_eventos(): void
    {
        $this->logar();
        $categoria = Categoria::factory()->create();

        $this->deleteJson("/api/categorias/{$categoria->id}")->assertNoContent();
        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_nao_exclui_categoria_com_eventos_vinculados(): void
    {
        $this->logar();
        $evento = Evento::factory()->create();

        $this->deleteJson("/api/categorias/{$evento->categoria_id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta categoria possui eventos vinculados e não pode ser excluída.');

        $this->assertDatabaseHas('categorias', ['id' => $evento->categoria_id]);
    }
}
