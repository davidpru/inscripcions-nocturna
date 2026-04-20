<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';

    protected $fillable = [
        'participante_id',
        'edicion_id',
        'hash_token',
        'cupon_id',
        'es_socio_uec',
        'esta_federado',
        'numero_licencia',
        'club',
        'necesita_autobus',
        'parada_autobus',
        'parada_autobus_pendiente',
        'seguro_anulacion',
        'talla_camiseta_caro',
        'talla_camiseta_pauls',
        'es_celiaco',
        'tarifa_aplicada',
        'precio_total',
        'descuento_cupon',
        'estado_pago',
        'numero_pedido',
        'numero_autorizacion',
        'fecha_pago',
        'fecha_devolucion',
        'importe_devolucion',
        'dorsal_recogido',
    ];

    protected $casts = [
        'es_socio_uec' => 'boolean',
        'esta_federado' => 'boolean',
        'necesita_autobus' => 'boolean',
        'seguro_anulacion' => 'boolean',
        'es_celiaco' => 'boolean',
        'dorsal_recogido' => 'boolean',
        'tarifa_aplicada' => 'string',
        'precio_total' => 'decimal:2',
        'descuento_cupon' => 'decimal:2',
        'importe_devolucion' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_devolucion' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Inscripcion $inscripcion) {
            if (empty($inscripcion->hash_token)) {
                $inscripcion->hash_token = Str::random(32);
            }
        });

        static::updating(function (Inscripcion $inscripcion) {
            // Si vuelve a pagado desde un estado que NO es de devolución, limpiar metadatos.
            // No limpiar si viene de devolucion_parcial/devuelto para no perder el histórico.
            if ($inscripcion->isDirty('estado_pago') && $inscripcion->estado_pago === 'pagado') {
                $estadoAnterior = $inscripcion->getOriginal('estado_pago');
                if (!in_array($estadoAnterior, ['devolucion_parcial', 'devuelto'])) {
                    $inscripcion->fecha_devolucion = null;
                    $inscripcion->importe_devolucion = null;
                }
            }
        });
    }

    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class);
    }

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(Edicion::class);
    }

    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class);
    }

    public function redsysTransacciones(): HasMany
    {
        return $this->hasMany(RedsysTransaccion::class);
    }

    public function cambioDorsalPendent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CambioDorsal::class)
            ->where('estado', 'pendiente')
            ->where('expires_at', '>', now())
            ->latest();
    }
}
