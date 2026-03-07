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
        'descuento_tipo',
        'descuento_valor',
        'descuento_porcentaje',
        'usos_maximos',
        'usos_actuales',
        'incluye_autobus',
        'incluye_federativa',
        'activo',
        'fecha_expiracion',
    ];

    protected $casts = [
        'descuento_tipo' => 'string',
        'descuento_valor' => 'float',
        'descuento_porcentaje' => 'float',
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
     * Descuenta solo el precio de inscripción, nunca la licencia federativa
     */
    public function calcularDescuento(Edicion $edicion, bool $esSocioUEC, bool $estaFederado = false): float
    {
        $esTarifaTardia = $edicion->esTarifaTardia();

        // El cupón descuenta solo la inscripción, no la licencia
        $precioInscripcion = 0.0;
        if ($esSocioUEC) {
            $precioInscripcion = $esTarifaTardia
                ? (float) $edicion->precio_inscripcion_socio_tardia 
                : (float) $edicion->precio_inscripcion_socio_normal;
        } else {
            $precioInscripcion = $esTarifaTardia
                ? (float) $edicion->precio_inscripcion_publico_tardia 
                : (float) $edicion->precio_inscripcion_publico_normal;
        }

        if ($this->descuento_tipo === 'fijo') {
            $descuentoFijo = max(0, (float) ($this->descuento_valor ?? 0));
            return round(min($precioInscripcion, $descuentoFijo), 2);
        }

        $porcentaje = min(100, max(0, (float) ($this->descuento_valor ?? $this->descuento_porcentaje ?? 100)));
        return round($precioInscripcion * ($porcentaje / 100), 2);
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
