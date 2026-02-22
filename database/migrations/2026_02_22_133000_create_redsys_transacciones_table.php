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
        Schema::create('redsys_transacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->nullable()->constrained('inscripciones')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('estado', 30);
            $table->string('numero_pedido', 50)->nullable();
            $table->string('numero_autorizacion', 50)->nullable();
            $table->decimal('importe', 10, 2)->nullable();
            $table->string('moneda', 3)->default('EUR');
            $table->string('response_code', 20)->nullable();
            $table->string('descripcion_error', 255)->nullable();
            $table->boolean('es_autobus')->default(false);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['estado', 'tipo']);
            $table->index(['numero_pedido']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redsys_transacciones');
    }
};
