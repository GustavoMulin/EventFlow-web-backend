<?php

namespace App\Http\Resources;

use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Evento
 */
class EventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $local = $this->relationLoaded('local') ? $this->local : null;

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'data_evento' => $this->data_evento->format('Y-m-d'),
            'hora_evento' => substr($this->hora_evento, 0, 5),
            'preco' => (float) $this->preco,
            // Endereço efetivo: o informado no evento ou, se vazio, o do local.
            'endereco' => $this->endereco ?? $local?->endereco,
            'endereco_evento' => $this->endereco,
            'vagas' => $this->vagas,
            'inscritos' => $this->inscricoes_count ?? $this->inscricoes()->count(),
            'vagas_restantes' => $this->vagasRestantes(),
            'banner_url' => $this->banner ? asset('storage/'.$this->banner) : null,
            'categoria_id' => $this->categoria_id,
            'local_id' => $this->local_id,
            'categoria' => CategoriaResource::make($this->whenLoaded('categoria')),
            'local' => LocalResource::make($this->whenLoaded('local')),
        ];
    }
}
