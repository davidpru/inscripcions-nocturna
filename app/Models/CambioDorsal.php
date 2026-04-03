<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioDorsal extends Model
{
    protected $table = 'cambios_dorsal';

    protected $fillable = [
        'inscripcion_id',
        'token',
        'estado',
        'precio',
        'expires_at',
        'datos_nuevo_participante',
        'nuevo_participante_id',
        'numero_pedido',
        'email_participante_original',
        'nombre_participante_original',
    ];

    protected $casts = [
        'datos_nuevo_participante' => 'array',
        'expires_at' => 'datetime',
        'precio' => 'decimal:2',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function nuevoParticipante(): BelongsTo
    {
        return $this->belongsTo(Participante::class, 'nuevo_participante_id');
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'pendiente' && $this->expires_at->isFuture();
    }

    public function estaCompletado(): bool
    {
        return $this->estado === 'completado';
    }
}
