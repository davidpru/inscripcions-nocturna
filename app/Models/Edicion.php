<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edicion extends Model
{
    protected $table = 'ediciones';

    protected $fillable = [
        'anio',
        'fecha_inicio_inscripciones',
        'fecha_evento',
        'distancia_km',
        'limite_inscritos',
        'limite_tarifa_tardia_inscritos',
        'fecha_limite_tarifa_normal',
        'estado',
        'lista_espera_cerrada',
        'activa',
        // Autobuses (JSON array)
        'autobuses',
        'plazas_autobus',
        // Nova estructura de preus
        'precio_inscripcion_socio_normal',
        'precio_inscripcion_publico_normal',
        'precio_inscripcion_socio_tardia',
        'precio_inscripcion_publico_tardia',
        'precio_licencia_federativa_socio',
        'precio_licencia_federativa_publico',
        // Extras
        'precio_autobus_normal',
        'precio_autobus_tardia',
        'precio_seguro',
    ];

    protected $casts = [
        'fecha_inicio_inscripciones' => 'datetime:Y-m-d\\TH:i',
        'fecha_evento' => 'date:Y-m-d',
        'limite_tarifa_tardia_inscritos' => 'integer',
        'fecha_limite_tarifa_normal' => 'date:Y-m-d',
        'distancia_km' => 'float',
        'activa' => 'boolean',
        'lista_espera_cerrada' => 'boolean',
        'autobuses' => 'array',
        'plazas_autobus' => 'integer',
        'precio_inscripcion_socio_normal' => 'float',
        'precio_inscripcion_publico_normal' => 'float',
        'precio_inscripcion_socio_tardia' => 'float',
        'precio_inscripcion_publico_tardia' => 'float',
        'precio_licencia_federativa_socio' => 'float',
        'precio_licencia_federativa_publico' => 'float',
        'precio_autobus_normal' => 'float',
        'precio_autobus_tardia' => 'float',
        'precio_seguro' => 'float',
    ];

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function getNumeroInscritosAttribute(): int
    {
        return $this->inscripciones()
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->count();
    }

    /**
     * Obtener el total de plazas de autobús disponibles
     */
    public function getTotalPlazasAutobusAttribute(): int
    {
        return $this->plazas_autobus ?? 0;
    }

    /**
     * Obtener el número de plazas de autobús vendidas
     */
    public function getPlazasAutobusVendidasAttribute(): int
    {
        return $this->inscripciones()
            ->where('necesita_autobus', true)
            ->whereIn('estado_pago', ['pagado', 'invitado', 'compromiso'])
            ->count();
    }

    /**
     * Obtener plazas de autobús disponibles (total - vendidas)
     */
    public function getPlazasAutobusDisponiblesAttribute(): int
    {
        return max(0, $this->total_plazas_autobus - $this->plazas_autobus_vendidas);
    }

    /**
     * Obtener el número de autobuses
     */
    public function getNumeroAutobusesAttribute(): int
    {
        return count($this->autobuses ?? []);
    }

    public function esTarifaTardia(): bool
    {
        $umbralTarifaTardia = $this->limite_tarifa_tardia_inscritos ?? $this->limite_inscritos;

        // Tarifa tardía si ya pasó la fecha límite o se alcanzó el umbral de tardía.
        return now()->isAfter($this->fecha_limite_tarifa_normal) 
            || $this->getNumeroInscritosAttribute() >= $umbralTarifaTardia;
    }

    /**
     * Verificar si las inscripciones están abiertas
     */
    public function inscripcionesAbiertas(): bool
    {
        // Si no hay fecha de inicio, las inscripciones están abiertas si el estado es 'abierta'
        if (!$this->fecha_inicio_inscripciones) {
            return $this->estado === 'abierta';
        }

        // Las inscripciones están abiertas si ya pasó la fecha de inicio y el estado es 'abierta'
        return now()->isAfter($this->fecha_inicio_inscripciones) && $this->estado === 'abierta';
    }

    /**
     * Obtener el tiempo restante hasta la apertura de inscripciones
     */
    public function getTiempoHastaAperturaAttribute(): ?array
    {
        if (!$this->fecha_inicio_inscripciones || now()->isAfter($this->fecha_inicio_inscripciones)) {
            return null;
        }

        $diff = now()->diff($this->fecha_inicio_inscripciones);
        return [
            'dias' => $diff->d + ($diff->m * 30) + ($diff->y * 365),
            'horas' => $diff->h,
            'minutos' => $diff->i,
            'segundos' => $diff->s,
        ];
    }
}