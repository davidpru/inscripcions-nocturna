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
        Schema::create('cambios_dorsal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->enum('estado', ['pendiente', 'completado', 'caducado'])->default('pendiente');
            $table->decimal('precio', 8, 2)->default(10.00);
            $table->timestamp('expires_at');
            $table->json('datos_nuevo_participante')->nullable();
            $table->foreignId('nuevo_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->string('numero_pedido')->nullable();
            // Email del participante original para enviarle notificación tras la transferencia
            $table->string('email_participante_original')->nullable();
            $table->string('nombre_participante_original')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cambios_dorsal');
    }
};
