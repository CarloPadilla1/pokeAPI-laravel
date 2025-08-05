<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeamController;

Route::get('/hola', function () {
    return response()->json(['message' => 'Bienvenido a la API de Pokémon Teams']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->get('/perfil', [AuthController::class, 'perfil']);

// Rutas protegidas para el manejo de equipos Pokémon
Route::middleware('auth:sanctum')->group(function () {
    // Operaciones CRUD de equipos
    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{id}', [TeamController::class, 'show']);
    Route::put('/teams/{id}', [TeamController::class, 'update']);
    Route::delete('/teams/{id}', [TeamController::class, 'destroy']);
    
    // Debug temporal - crear equipo de prueba
    Route::post('/teams/create-test', function() {
        $user = Auth::user();
        $team = $user->teams()->create([
            'name' => 'Equipo de Prueba',
            'description' => 'Un equipo creado para probar la funcionalidad',
            'is_active' => true
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Equipo de prueba creado',
            'data' => $team
        ]);
    });
    
    // Activar un equipo específico
    Route::post('/teams/{id}/activate', [TeamController::class, 'activate']);
    
    // Manejo de Pokémon en equipos
    Route::post('/teams/{teamId}/pokemon', [TeamController::class, 'addPokemon']);
    Route::put('/teams/{teamId}/pokemon/{pokemonId}', [TeamController::class, 'updatePokemon']);
    Route::delete('/teams/{teamId}/pokemon/{pokemonId}', [TeamController::class, 'removePokemon']);
    
    // Intercambiar posiciones de Pokémon
    Route::post('/teams/{teamId}/swap-positions', [TeamController::class, 'swapPokemonPositions']);
});
