<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;    

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function person()
    {
        return $this->hasOne('App\Models\Person', 'user_id');
    }

    /**
     * Relación con los equipos Pokémon del usuario
     */
    public function teams()
    {
        return $this->hasMany(Team::class)->orderBy('created_at', 'desc');
    }

    /**
     * Obtener el equipo activo del usuario
     */
    public function activeTeam()
    {
        return $this->hasOne(Team::class)->where('is_active', true);
    }

    /**
     * Obtener todos los Pokémon de todos los equipos del usuario
     */
    public function allPokemon()
    {
        return $this->hasManyThrough(TeamPokemon::class, Team::class);
    }
}
