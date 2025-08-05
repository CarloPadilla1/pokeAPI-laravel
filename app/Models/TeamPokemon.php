<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPokemon extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'pokemon_id',
        'pokemon_name',
        'nickname',
        'level',
        'position',
        'ability',
        'nature',
        'held_item',
        'moves',
        'stats',
        'sprite_url'
    ];

    protected $casts = [
        'moves' => 'array',
        'stats' => 'array',
        'level' => 'integer',
        'position' => 'integer',
        'pokemon_id' => 'integer'
    ];

    /**
     * Relación con el equipo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Accessor para obtener el nombre a mostrar (nickname o nombre original)
     */
    public function getDisplayNameAttribute()
    {
        return $this->nickname ?: $this->pokemon_name;
    }

    /**
     * Validar que no se agreguen más de 4 movimientos
     */
    public function setMovesAttribute($value)
    {
        $moves = is_array($value) ? $value : json_decode($value, true);
        $this->attributes['moves'] = json_encode(array_slice($moves ?? [], 0, 4));
    }

    /**
     * Validar que la posición esté entre 1 y 6
     */
    public function setPositionAttribute($value)
    {
        $this->attributes['position'] = max(1, min(6, intval($value)));
    }

    /**
     * Validar que el nivel esté entre 1 y 100
     */
    public function setLevelAttribute($value)
    {
        $this->attributes['level'] = max(1, min(100, intval($value)));
    }

    /**
     * Scope para ordenar por posición
     */
    public function scopeOrderedByPosition($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Verificar si el Pokémon tiene movimientos asignados
     */
    public function hasMoves()
    {
        $moves = $this->moves ?? [];
        return count(array_filter($moves)) > 0;
    }

    /**
     * Obtener los movimientos filtrados (sin valores nulos)
     */
    public function getValidMovesAttribute()
    {
        $moves = $this->moves ?? [];
        return array_values(array_filter($moves, function($move) {
            return !empty($move);
        }));
    }
}
