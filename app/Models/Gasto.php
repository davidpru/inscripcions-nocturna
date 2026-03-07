<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gasto extends Model
{
    protected $fillable = [
        'edicion_id',
        'titulo',
        'descripcion',
        'base_imponible',
        'tipo_iva',
        'total',
        'presupuestado',
        'presupuestado_por',
        'aceptado',
        'aceptado_por',
        'pagado',
        'pagado_por',
    ];

    protected $casts = [
        'base_imponible' => 'decimal:2',
        'total' => 'decimal:2',
        'presupuestado' => 'boolean',
        'aceptado' => 'boolean',
        'pagado' => 'boolean',
    ];

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(Edicion::class);
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(CategoriaGasto::class, 'categoria_gasto_gasto');
    }

    public function presupuestadoPorAdmin(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'presupuestado_por');
    }

    public function aceptadoPorAdmin(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'aceptado_por');
    }

    public function pagadoPorAdmin(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'pagado_por');
    }
}
