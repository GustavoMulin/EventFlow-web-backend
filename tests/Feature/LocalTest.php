<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Local;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_listagem_e_publica(): void
    {
        Local::factory()->count(3)->create();

        $this->getJson('/api/locais')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'nome', 'latitude', 'longitude', 'endereco']]]);
    }

    public function test_visitante_nao_pode_criar_local(): void
    {
        $this->postJson('/api/locais', ['nome' => 'X', 'latitude' => 1, 'longitude' => 1])->assertUnauthorized();
    }

    public function test_usuario_cria_local_com_endereco_opcional(): void
    {
        $this->logar();

        $this->postJson('/api/locais', [
            'nome' => 'Teatro Municipal',
            'latitude' => -8.7619,
            'longitude' => -63.9039,
        ])->assertCreated()->assertJsonPath('data.nome', 'Teatro Municipal');

        $this->assertDatabaseHas('locais', ['nome' => 'Teatro Municipal', 'endereco' => null]);
    }

    public function test_valida_campos_e_intervalo_de_coordenadas(): void
    {
        $this->logar();

        $this->postJson('/api/locais', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'latitude', 'longitude']);

        $this->postJson('/api/locais', ['nome' => 'X', 'latitude' => 120, 'longitude' => -200])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_usuario_edita_local(): void
    {
        $this->logar();
        $local = Local::factory()->create();

        $this->putJson("/api/locais/{$local->id}", [
            'nome' => 'Novo nome',
            'latitude' => 10.5,
            'longitude' => -20.25,
            'endereco' => 'Rua A, 10',
        ])->assertOk()->assertJsonPath('data.nome', 'Novo nome');

        $this->assertDatabaseHas('locais', ['id' => $local->id, 'endereco' => 'Rua A, 10']);
    }

    public function test_usuario_exclui_local_sem_eventos(): void
    {
        $this->logar();
        $local = Local::factory()->create();

        $this->deleteJson("/api/locais/{$local->id}")->assertNoContent();
        $this->assertDatabaseMissing('locais', ['id' => $local->id]);
    }

    public function test_nao_exclui_local_com_eventos_vinculados(): void
    {
        $this->logar();
        $evento = Evento::factory()->create();

        $this->deleteJson("/api/locais/{$evento->local_id}")->assertStatus(409);
        $this->assertDatabaseHas('locais', ['id' => $evento->local_id]);
    }
}
