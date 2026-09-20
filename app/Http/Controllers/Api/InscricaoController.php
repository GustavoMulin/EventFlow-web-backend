<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InscricaoRequest;
use App\Http\Resources\InscricaoResource;
use App\Models\Evento;
use App\Models\Inscricao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscricaoController extends Controller
{
    /**
     * Participantes inscritos em um evento (área de gestão).
     */
    public function index(Evento $evento): AnonymousResourceCollection
    {
        return InscricaoResource::collection(
            $evento->inscricoes()->orderByDesc('created_at')->orderByDesc('id')->get()
        );
    }

    /**
     * Inscrição pública de um participante em um evento.
     */
    public function store(InscricaoRequest $request, Evento $evento): JsonResponse
    {
        $inscricao = DB::transaction(function () use ($request, $evento) {
            // Trava o evento para que duas inscrições simultâneas não ultrapassem as vagas.
            $evento = Evento::whereKey($evento->id)->lockForUpdate()->firstOrFail();

            if ($evento->data_evento->endOfDay()->isPast()) {
                throw ValidationException::withMessages([
                    'evento' => ['As inscrições para este evento estão encerradas.'],
                ]);
            }

            if ($evento->inscricoes()->count() >= $evento->vagas) {
                throw ValidationException::withMessages([
                    'evento' => ['As vagas deste evento estão esgotadas.'],
                ]);
            }

            if ($evento->inscricoes()->where('email', $request->validated('email'))->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['Este e-mail já está inscrito neste evento.'],
                ]);
            }

            return $evento->inscricoes()->create($request->validated());
        });

        return InscricaoResource::make($inscricao->load('evento.local'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cancela uma inscrição (área de gestão).
     */
    public function destroy(Inscricao $inscricao): JsonResponse
    {
        $inscricao->delete();

        return response()->json(status: 204);
    }
}
