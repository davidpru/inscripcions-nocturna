<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE inscripciones MODIFY estado_pago ENUM('pendiente','pagado','cancelado','devuelto','invitado','devolucion_parcial','lista_espera','compromiso') DEFAULT 'pendiente'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE inscripciones MODIFY estado_pago ENUM('pendiente','pagado','cancelado','devuelto','invitado','devolucion_parcial','lista_espera') DEFAULT 'pendiente'"
        );
    }
};
