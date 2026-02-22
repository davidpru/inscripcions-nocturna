<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE inscripciones MODIFY estado_pago ENUM('pendiente','pagado','cancelado','devuelto','invitado','devolucion_parcial') DEFAULT 'pendiente'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE inscripciones MODIFY estado_pago ENUM('pendiente','pagado','cancelado','devuelto') DEFAULT 'pendiente'"
        );
    }
};
