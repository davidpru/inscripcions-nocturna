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
        'tarifa_aplicada',
        'precio_total',
        'estado_pago',
        'numero_pedido',
        'numero_autorizacion',
        'fecha_pago',
    ];

    protected $casts = [
        'es_socio_uec' => 'boolean',
        'esta_federado' => 'boolean',
        'necesita_autobus' => 'boolean',
        'seguro_anulacion' => 'boolean',
        'tarifa_aplicada' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class);
    }

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(Edicion::class);
    }
}
