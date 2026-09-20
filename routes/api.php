<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticação (público)
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

/*
|--------------------------------------------------------------------------
| Leitura pública: categorias
|--------------------------------------------------------------------------
*/
Route::get('categorias', [CategoriaController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Área autenticada (Sanctum bearer token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Sessão / perfil
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::patch('user/profile', [ProfileController::class, 'update']);

    // Categorias
    Route::apiResource('categorias', CategoriaController::class)->only(['show', 'store', 'update', 'destroy']);
});
