<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventoRequest;
use App\Http\Resources\EventoResource;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class EventoController extends Controller
{
    /**
     * Lista paginada de eventos.
     *
     * Filtros (query string): busca (nome/descrição), categoria_id, local_id, por_pagina (máx. 100).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer'],
            'local_id' => ['nullable', 'integer'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $eventos = Evento::query()
            ->with(['categoria', 'local'])
            ->withCount('inscricoes')
            ->when($request->filled('busca'), function ($query) use ($request) {
                $termo = '%'.$request->string('busca')->trim().'%';
                $query->where(fn ($q) => $q->where('nome', 'like', $termo)->orWhere('descricao', 'like', $termo));
            })
            ->when($request->filled('categoria_id'), fn ($query) => $query->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->filled('local_id'), fn ($query) => $query->where('local_id', $request->integer('local_id')))
            ->orderBy('data_evento')
            ->orderBy('hora_evento')
            ->paginate($request->integer('por_pagina', 12))
            ->withQueryString();

        return EventoResource::collection($eventos);
    }

    public function show(Evento $evento): EventoResource
    {
        return new EventoResource($this->carregar($evento));
    }

    public function store(EventoRequest $request): JsonResponse
    {
        $dados = $request->safe()->except(['banner', 'remover_banner']);

        if ($request->hasFile('banner')) {
            $dados['banner'] = $request->file('banner')->store('banners', 'public');
        }

        $evento = Evento::create($dados);

        return EventoResource::make($this->carregar($evento))->response()->setStatusCode(201);
    }

    public function update(EventoRequest $request, Evento $evento): EventoResource
    {
        $dados = $request->safe()->except(['banner', 'remover_banner']);

        if ($request->hasFile('banner')) {
            $this->apagarBanner($evento);
            $dados['banner'] = $request->file('banner')->store('banners', 'public');
        } elseif ($request->boolean('remover_banner')) {
            $this->apagarBanner($evento);
            $dados['banner'] = null;
        }

        $evento->update($dados);

        return new EventoResource($this->carregar($evento));
    }

    public function destroy(Evento $evento): JsonResponse
    {
        $this->apagarBanner($evento);
        $evento->delete(); // as inscrições são removidas em cascata

        return response()->json(status: 204);
    }

    private function carregar(Evento $evento): Evento
    {
        return $evento->load(['categoria', 'local'])->loadCount('inscricoes');
    }

    private function apagarBanner(Evento $evento): void
    {
        if ($evento->banner) {
            Storage::disk('public')->delete($evento->banner);
        }
    }
}
