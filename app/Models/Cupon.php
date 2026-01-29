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
     * - Si NO federado: descuenta la tarifa federado (paga solo 5€ de licencia)
     * - Si YA federado: descuenta toda la inscripción (paga 0€)
     */
    public function calcularDescuento(Edicion $edicion, bool $esSocioUEC, bool $estaFederado = false): float
    {
        $esTarifaTardia = $edicion->esTarifaTardia();

        // Si ya está federado, descuenta toda su tarifa (inscripción gratis)
        if ($estaFederado) {
            if ($esSocioUEC) {
                return $esTarifaTardia 
                    ? (float) $edicion->tarifa_socio_federado_tardia 
                    : (float) $edicion->tarifa_socio_federado_normal;
            } else {
                return $esTarifaTardia 
                    ? (float) $edicion->tarifa_publico_federado_tardia 
                    : (float) $edicion->tarifa_publico_federado_normal;
            }
        }

        // Si NO está federado, descuenta la tarifa federado (queda solo 5€ de licencia)
        if ($esSocioUEC) {
            return $esTarifaTardia 
                ? (float) $edicion->tarifa_socio_federado_tardia 
                : (float) $edicion->tarifa_socio_federado_normal;
        } else {
            return $esTarifaTardia 
                ? (float) $edicion->tarifa_publico_federado_tardia 
                : (float) $edicion->tarifa_publico_federado_normal;
        }
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
