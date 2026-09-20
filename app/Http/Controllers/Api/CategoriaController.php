<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    /**
     * Lista todas as categorias (ordem alfabética).
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoriaResource::collection(
            Categoria::withCount('eventos')->orderBy('nome')->get()
        );
    }

    public function show(Categoria $categoria): CategoriaResource
    {
        return new CategoriaResource($categoria->loadCount('eventos'));
    }

    public function store(CategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::create($request->validated());

        return CategoriaResource::make($categoria)->response()->setStatusCode(201);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): CategoriaResource
    {
        $categoria->update($request->validated());

        return new CategoriaResource($categoria);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        if ($categoria->eventos()->exists()) {
            return response()->json([
                'message' => 'Esta categoria possui eventos vinculados e não pode ser excluída.',
            ], 409);
        }

        $categoria->delete();

        return response()->json(status: 204);
    }
}
