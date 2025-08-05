<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamPokemon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    /**
     * Obtener todos los equipos del usuario autenticado
     */
    public function index()
    {
        $user = Auth::user();
        $teams = $user->teams()->with(['pokemon' => function($query) {
            $query->orderBy('position');
        }])->get();

        // Agregar el conteo de pokémon a cada equipo
        $teams->each(function ($team) {
            $team->pokemon_count = $team->pokemon->count();
        });

        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
    }

    /**
     * Crear un nuevo equipo
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        $user = Auth::user();

        // Si es el primer equipo o se marca como activo, desactivar otros equipos
        $is_active = $request->boolean('is_active') || $user->teams()->count() === 0;
        
        if ($is_active) {
            $user->teams()->update(['is_active' => false]);
        }

        $team = $user->teams()->create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equipo creado exitosamente',
            'data' => $team->load('pokemon')
        ], 201);
    }

    /**
     * Obtener un equipo específico con sus Pokémon
     */
    public function show($id)
    {
        $team = Auth::user()->teams()->with(['pokemon' => function($query) {
            $query->orderBy('position');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $team
        ]);
    }

    /**
     * Actualizar un equipo
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        $team = Auth::user()->teams()->findOrFail($id);

        // Si se activa este equipo, desactivar los demás
        if ($request->boolean('is_active') && !$team->is_active) {
            Auth::user()->teams()->update(['is_active' => false]);
        }

        $team->update($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Equipo actualizado exitosamente',
            'data' => $team->load('pokemon')
        ]);
    }

    /**
     * Eliminar un equipo
     */
    public function destroy($id)
    {
        $team = Auth::user()->teams()->findOrFail($id);
        
        // Si era el equipo activo y hay otros equipos, activar otro
        if ($team->is_active) {
            $nextTeam = Auth::user()->teams()->where('id', '!=', $id)->first();
            if ($nextTeam) {
                $nextTeam->update(['is_active' => true]);
            }
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipo eliminado exitosamente'
        ]);
    }

    /**
     * Activar un equipo específico
     */
    public function activate($id)
    {
        $team = Auth::user()->teams()->findOrFail($id);
        $team->activate();

        return response()->json([
            'success' => true,
            'message' => 'Equipo activado exitosamente',
            'data' => $team->load('pokemon')
        ]);
    }

    /**
     * Agregar un Pokémon al equipo
     */
    public function addPokemon(Request $request, $teamId)
    {
        $request->validate([
            'pokemon_id' => 'required|integer|min:1',
            'pokemon_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'level' => 'integer|min:1|max:100',
            'position' => 'integer|min:1|max:6',
            'ability' => 'nullable|string|max:255',
            'nature' => 'nullable|string|max:255',
            'held_item' => 'nullable|string|max:255',
            'moves' => 'nullable|array|max:4',
            'moves.*' => 'string|max:255',
            'stats' => 'nullable|array',
            'sprite_url' => 'nullable|url'
        ]);

        $team = Auth::user()->teams()->findOrFail($teamId);

        // Verificar que el equipo no esté completo
        if ($team->pokemon()->count() >= 6) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo ya tiene 6 Pokémon'
            ], 400);
        }

        // Si no se especifica posición, usar la siguiente disponible
        $position = $request->position ?? $team->getNextPosition();

        // Verificar que la posición no esté ocupada
        if ($team->pokemon()->where('position', $position)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La posición ' . $position . ' ya está ocupada'
            ], 400);
        }

        $pokemon = $team->pokemon()->create([
            'pokemon_id' => $request->pokemon_id,
            'pokemon_name' => $request->pokemon_name,
            'nickname' => $request->nickname,
            'level' => $request->level ?? 50,
            'position' => $position,
            'ability' => $request->ability,
            'nature' => $request->nature,
            'held_item' => $request->held_item,
            'moves' => $request->moves ?? [],
            'stats' => $request->stats,
            'sprite_url' => $request->sprite_url
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pokémon agregado al equipo exitosamente',
            'data' => $pokemon
        ], 201);
    }

    /**
     * Actualizar un Pokémon del equipo
     */
    public function updatePokemon(Request $request, $teamId, $pokemonId)
    {
        $request->validate([
            'nickname' => 'nullable|string|max:255',
            'level' => 'integer|min:1|max:100',
            'position' => 'integer|min:1|max:6',
            'ability' => 'nullable|string|max:255',
            'nature' => 'nullable|string|max:255',
            'held_item' => 'nullable|string|max:255',
            'moves' => 'nullable|array|max:4',
            'moves.*' => 'string|max:255',
            'stats' => 'nullable|array'
        ]);

        $team = Auth::user()->teams()->findOrFail($teamId);
        $pokemon = $team->pokemon()->findOrFail($pokemonId);

        // Si se cambia la posición, verificar que no esté ocupada
        if ($request->has('position') && $request->position != $pokemon->position) {
            if ($team->pokemon()->where('position', $request->position)->where('id', '!=', $pokemonId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La posición ' . $request->position . ' ya está ocupada'
                ], 400);
            }
        }

        $pokemon->update($request->only([
            'nickname', 'level', 'position', 'ability', 'nature', 
            'held_item', 'moves', 'stats'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Pokémon actualizado exitosamente',
            'data' => $pokemon
        ]);
    }

    /**
     * Eliminar un Pokémon del equipo
     */
    public function removePokemon($teamId, $pokemonId)
    {
        try {
            // Log para depuración
            \Log::info("Intento de eliminar Pokémon ID: {$pokemonId} del equipo ID: {$teamId}");
            
            // Buscar el equipo asegurándonos que pertenece al usuario actual
            $team = Auth::user()->teams()->findOrFail($teamId);
            \Log::info("Equipo encontrado: {$team->id}");
            
            // Buscar el Pokémon en el equipo
            $pokemon = $team->pokemon()->where('id', $pokemonId)->first();
            
            if (!$pokemon) {
                \Log::warning("Pokémon no encontrado en el equipo");
                return response()->json([
                    'success' => false,
                    'message' => 'Pokémon no encontrado en el equipo'
                ], 404);
            }
            
            \Log::info("Pokémon encontrado: {$pokemon->id}, nombre: {$pokemon->pokemon_name}");
            
            // Eliminar el Pokémon del equipo
            $result = $pokemon->delete();
            \Log::info("Resultado de eliminar: " . ($result ? 'exitoso' : 'fallido'));
            
            if (!$result) {
                \Log::error("Error al eliminar el Pokémon");
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno al eliminar el Pokémon'
                ], 500);
            }
            
            \Log::info("Pokémon eliminado exitosamente");
            
            return response()->json([
                'success' => true,
                'message' => 'Pokémon eliminado del equipo exitosamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Excepción al eliminar Pokémon: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el Pokémon: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Intercambiar posiciones de dos Pokémon en el equipo
     */
    public function swapPokemonPositions(Request $request, $teamId)
    {
        $request->validate([
            'pokemon1_id' => 'required|integer',
            'pokemon2_id' => 'required|integer'
        ]);

        $team = Auth::user()->teams()->findOrFail($teamId);
        
        $pokemon1 = $team->pokemon()->findOrFail($request->pokemon1_id);
        $pokemon2 = $team->pokemon()->findOrFail($request->pokemon2_id);

        // Intercambiar posiciones
        $tempPosition = $pokemon1->position;
        $pokemon1->update(['position' => $pokemon2->position]);
        $pokemon2->update(['position' => $tempPosition]);

        return response()->json([
            'success' => true,
            'message' => 'Posiciones intercambiadas exitosamente',
            'data' => $team->load(['pokemon' => function($query) {
                $query->orderBy('position');
            }])
        ]);
    }
}
