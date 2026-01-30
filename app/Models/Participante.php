<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participante extends Model
{
    protected $fillable = [
        'dni',
        'nombre',
        'apellidos',
        'genero',
        'fecha_nacimiento',
        'telefono',
        'email',
        'direccion',
        'codigo_postal',
        'poblacion',
        'provincia',
        'club',
        'numero_licencia',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }
}
