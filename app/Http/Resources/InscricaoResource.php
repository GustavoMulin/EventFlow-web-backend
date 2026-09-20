<?php

namespace App\Http\Resources;

use App\Models\Inscricao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Inscricao
 */
class InscricaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo' => $this->codigo,
            'evento_id' => $this->evento_id,
            'nome' => $this->nome,
            'email' => $this->email,
            'documento' => $this->documento,
            'inscrito_em' => $this->created_at?->toIso8601String(),
            'evento' => EventoResource::make($this->whenLoaded('evento')),
        ];
    }
}
