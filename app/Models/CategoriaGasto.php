<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CategoriaGasto extends Model
{
    protected $table = 'categorias_gasto';

    protected $fillable = [
        'nombre',
        'color',
    ];

    public function gastos(): BelongsToMany
    {
        return $this->belongsToMany(Gasto::class, 'categoria_gasto_gasto');
    }
}
