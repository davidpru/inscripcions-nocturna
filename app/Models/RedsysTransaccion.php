<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedsysTransaccion extends Model
{
    protected $table = 'redsys_transacciones';

    protected $fillable = [
        'inscripcion_id',
        'tipo',
        'estado',
        'numero_pedido',
        'numero_autorizacion',
        'importe',
        'moneda',
        'response_code',
        'descripcion_error',
        'es_autobus',
        'payload',
    ];

    protected $casts = [
        'es_autobus' => 'boolean',
        'importe' => 'decimal:2',
        'payload' => 'array',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }
}
