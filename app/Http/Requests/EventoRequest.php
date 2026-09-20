<?php

namespace App\Http\Requests;

use App\Models\Evento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Ao editar, não deixa reduzir as vagas abaixo do número de inscritos.
        $evento = $this->route('evento');
        $minimoVagas = $evento instanceof Evento ? max(1, $evento->inscricoes()->count()) : 1;

        return [
            'nome' => ['required', 'string', 'max:150'],
            'descricao' => ['required', 'string', 'max:5000'],
            'data_evento' => ['required', 'date_format:Y-m-d'],
            'hora_evento' => ['required', 'date_format:H:i'],
            'preco' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'vagas' => ['required', 'integer', 'min:'.$minimoVagas, 'max:100000'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'local_id' => ['required', 'integer', 'exists:locais,id'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remover_banner' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vagas.min' => 'As vagas não podem ser menores que o número de inscritos (:min).',
            'banner.max' => 'O banner não pode ser maior que 2 MB.',
        ];
    }
}
