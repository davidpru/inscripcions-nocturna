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
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participante_id')->constrained('participantes')->onDelete('cascade');
            $table->foreignId('edicion_id')->constrained('ediciones')->onDelete('cascade');
            $table->boolean('es_socio_uec')->default(false);
            $table->boolean('esta_federado')->default(false);
            $table->string('numero_licencia')->nullable();
            $table->string('club')->nullable();
            $table->boolean('necesita_autobus')->default(false);
            $table->boolean('seguro_anulacion')->default(false);
            $table->string('talla_camiseta_caro')->nullable();
            $table->string('talla_camiseta_pauls')->nullable();
            $table->decimal('tarifa_aplicada', 8, 2);
            $table->decimal('precio_total', 8, 2);
            $table->enum('estado_pago', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->timestamps();
            
            // Un participante solo puede inscribirse una vez por edición
            $table->unique(['participante_id', 'edicion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
