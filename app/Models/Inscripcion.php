<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscripcion extends Model
{
    protected $table = 'inscripciones';

    protected $fillable = [
        'participante_id',
        'edicion_id',
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
    ];

    protected $casts = [
        'es_socio_uec' => 'boolean',
        'esta_federado' => 'boolean',
        'necesita_autobus' => 'boolean',
        'seguro_anulacion' => 'boolean',
        'es_celiaco' => 'boolean',
        'tarifa_aplicada' => 'string',
        'precio_total' => 'decimal:2',
        'descuento_cupon' => 'decimal:2',
        'importe_devolucion' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_devolucion' => 'datetime',
    ];

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
}
