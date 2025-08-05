<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $table = 'person';

    protected $fillable = [
        'user_id',
        'address',
        'phone',
        'cedula',
        'sexo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}