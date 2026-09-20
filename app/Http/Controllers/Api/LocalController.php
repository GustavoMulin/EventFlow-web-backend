<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocalRequest;
use App\Http\Resources\LocalResource;
use App\Models\Local;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocalController extends Controller
{
    /**
     * Lista todos os locais (ordem alfabética).
     */
    public function index(): AnonymousResourceCollection
    {
        return LocalResource::collection(
            Local::withCount('eventos')->orderBy('nome')->get()
        );
    }

    public function show(Local $local): LocalResource
    {
        return new LocalResource($local->loadCount('eventos'));
    }

    public function store(LocalRequest $request): JsonResponse
    {
        $local = Local::create($request->validated());

        return LocalResource::make($local)->response()->setStatusCode(201);
    }

    public function update(LocalRequest $request, Local $local): LocalResource
    {
        $local->update($request->validated());

        return new LocalResource($local);
    }

    public function destroy(Local $local): JsonResponse
    {
        if ($local->eventos()->exists()) {
            return response()->json([
                'message' => 'Este local possui eventos vinculados e não pode ser excluído.',
            ], 409);
        }

        $local->delete();

        return response()->json(status: 204);
    }
}
