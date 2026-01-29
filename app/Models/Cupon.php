<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupon extends Model
{
    protected $table = 'cupones';

    protected $fillable = [
        'codigo',
        'descripcion',
        'edicion_id',
        'usos_maximos',
        'usos_actuales',
        'incluye_autobus',
        'incluye_federativa',
        'activo',
        'fecha_expiracion',
    ];

    protected $casts = [
        'incluye_autobus' => 'boolean',
        'incluye_federativa' => 'boolean',
        'activo' => 'boolean',
        'fecha_expiracion' => 'date',
    ];

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(Edicion::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    /**
     * Verificar si el cupón está disponible para usar
     */
    public function estaDisponible(): bool
    {
        // No activo
        if (!$this->activo) {
            return false;
        }

        // Usos agotados
        if ($this->usos_actuales >= $this->usos_maximos) {
            return false;
        }

        // Expirado
        if ($this->fecha_expiracion && now()->isAfter($this->fecha_expiracion)) {
            return false;
        }

        return true;
    }

    /**
     * Calcular el descuento que aplica el cupón
     * El cupón cubre la diferencia entre la tarifa de no federado y federado
     * (es decir, el coste de la licencia federativa)
     */
    public function calcularDescuento(Edicion $edicion, bool $esSocioUEC): float
    {
        $esTarifaTardia = $edicion->esTarifaTardia();

        // Calcular diferencia entre tarifa no federado y federado
        if ($esSocioUEC) {
            $tarifaNoFederado = $esTarifaTardia 
                ? (float) $edicion->tarifa_socio_no_federado_tardia 
                : (float) $edicion->tarifa_socio_no_federado_normal;
            $tarifaFederado = $esTarifaTardia 
                ? (float) $edicion->tarifa_socio_federado_tardia 
                : (float) $edicion->tarifa_socio_federado_normal;
        } else {
            $tarifaNoFederado = $esTarifaTardia 
                ? (float) $edicion->tarifa_publico_no_federado_tardia 
                : (float) $edicion->tarifa_publico_no_federado_normal;
            $tarifaFederado = $esTarifaTardia 
                ? (float) $edicion->tarifa_publico_federado_tardia 
                : (float) $edicion->tarifa_publico_federado_normal;
        }

        return $tarifaNoFederado - $tarifaFederado;
    }

    /**
     * Incrementar contador de usos
     */
    public function incrementarUso(): void
    {
        $this->increment('usos_actuales');
    }

    /**
     * Decrementar contador de usos (para devoluciones)
     */
    public function decrementarUso(): void
    {
        if ($this->usos_actuales > 0) {
            $this->decrement('usos_actuales');
        }
    }

    /**
     * Obtener usos restantes
     */
    public function getUsosRestantesAttribute(): int
    {
        return max(0, $this->usos_maximos - $this->usos_actuales);
    }
}
