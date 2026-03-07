<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoLog extends Model
{
    protected $table = 'gasto_logs';

    protected $fillable = [
        'gasto_id',
        'admin_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
    ];

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Administrador::class, 'admin_id');
    }
}
