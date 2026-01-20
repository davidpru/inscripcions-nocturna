<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->timestamp('fecha_devolucion')->nullable()->after('fecha_pago');
            $table->decimal('importe_devolucion', 8, 2)->nullable()->after('fecha_devolucion');
        });

        // Añadir 'devuelto' al enum de estado_pago
        DB::statement("ALTER TABLE inscripciones MODIFY estado_pago ENUM('pendiente', 'pagado', 'cancelado', 'devuelto') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn(['fecha_devolucion', 'importe_devolucion']);
        });

        DB::statement("ALTER TABLE inscripciones MODIFY estado_pago ENUM('pendiente', 'pagado', 'cancelado') DEFAULT 'pendiente'");
    }
};
