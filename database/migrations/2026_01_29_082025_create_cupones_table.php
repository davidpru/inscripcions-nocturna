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
        Schema::create('cupones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion')->nullable();
            $table->foreignId('edicion_id')->constrained('ediciones')->onDelete('cascade');
            $table->integer('usos_maximos')->default(1);
            $table->integer('usos_actuales')->default(0);
            $table->boolean('incluye_autobus')->default(false);
            $table->boolean('incluye_federativa')->default(false);
            $table->boolean('activo')->default(true);
            $table->date('fecha_expiracion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupones');
    }
};
