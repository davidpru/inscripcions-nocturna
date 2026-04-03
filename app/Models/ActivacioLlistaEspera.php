<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivacioLlistaEspera extends Model
{
    protected $table = 'activacions_llista_espera';

    protected $fillable = [
        'inscripcion_id',
        'token',
        'estado',
        'numero_pedido',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
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
