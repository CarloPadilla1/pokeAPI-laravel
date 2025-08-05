<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el usuario propietario del equipo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con los Pokémon del equipo
     */
    public function pokemon()
    {
        return $this->hasMany(TeamPokemon::class)->orderBy('position');
    }

    /**
     * Scope para obtener solo equipos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Método para activar este equipo y desactivar los demás del usuario
     */
    public function activate()
    {
        // Desactivar todos los equipos del usuario
        $this->user->teams()->update(['is_active' => false]);
        
        // Activar este equipo
        $this->update(['is_active' => true]);
        
        return $this;
    }

    /**
     * Verificar si el equipo está completo (tiene 6 Pokémon)
     */
    public function isComplete()
    {
        return $this->pokemon()->count() >= 6;
    }

    /**
     * Obtener la siguiente posición disponible en el equipo
     */
    public function getNextPosition()
    {
        $maxPosition = $this->pokemon()->max('position') ?? 0;
        return min($maxPosition + 1, 6);
    }
}
