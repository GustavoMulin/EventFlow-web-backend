<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    /**
     * Update the authenticated user's password.
     *
     * All API tokens are revoked so a leaked token cannot outlive the change;
     * a fresh token is issued to the caller.
     */
    public function update(PasswordUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['password' => $request->validated()['password']]);

        $user->tokens()->delete();

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
        ]);
    }
}
