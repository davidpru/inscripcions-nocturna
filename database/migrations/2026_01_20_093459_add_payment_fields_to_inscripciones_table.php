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
            $table->string('numero_pedido')->nullable()->after('precio_total');
            $table->string('numero_autorizacion')->nullable()->after('numero_pedido');
            $table->timestamp('fecha_pago')->nullable()->after('numero_autorizacion');
            $table->string('estado_pago')->default('pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn(['numero_pedido', 'numero_autorizacion', 'fecha_pago']);
        });
    }
};
