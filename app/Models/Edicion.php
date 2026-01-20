<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edicion extends Model
{
    protected $table = 'ediciones';

    protected $fillable = [
        'anio',
        'fecha_evento',
        'limite_inscritos',
        'fecha_limite_tarifa_normal',
        'estado',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
        'fecha_limite_tarifa_normal' => 'date',
    ];

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function getNumeroInscritosAttribute(): int
    {
        return $this->inscripciones()->count();
    }

    public function esTarifaTardia(): bool
    {
        // Tarifa tardía si ya pasó la fecha límite O si ya hay 650 inscritos
        return now()->isAfter($this->fecha_limite_tarifa_normal) 
            || $this->getNumeroInscritosAttribute() >= $this->limite_inscritos;
    }
}
