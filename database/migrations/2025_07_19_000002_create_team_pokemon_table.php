<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_pokemon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->integer('pokemon_id'); // ID del Pokémon en la PokeAPI
            $table->string('pokemon_name'); // Nombre del Pokémon para referencia rápida
            $table->string('nickname')->nullable(); // Apodo personalizado
            $table->integer('level')->default(50); // Nivel del Pokémon
            $table->integer('position')->default(1); // Posición en el equipo (1-6)
            $table->string('ability')->nullable(); // Habilidad del Pokémon
            $table->string('nature')->nullable(); // Naturaleza del Pokémon
            $table->string('held_item')->nullable(); // Objeto/baya que porta
            $table->json('moves'); // Array de hasta 4 movimientos [move1, move2, move3, move4]
            $table->json('stats')->nullable(); // Estadísticas del Pokémon (HP, Attack, Defense, etc.)
            $table->string('sprite_url')->nullable(); // URL del sprite para cache
            $table->timestamps();
            
            // Índices para mejor rendimiento
            $table->index(['team_id', 'position']);
            $table->index('pokemon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_pokemon');
    }
};
