<?php

namespace App\Http\Resources;

use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Local
 */
class LocalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'endereco' => $this->endereco,
            'eventos_count' => $this->whenCounted('eventos'),
        ];
    }
}
