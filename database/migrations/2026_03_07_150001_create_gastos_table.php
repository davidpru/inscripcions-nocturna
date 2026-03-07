<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edicion_id')->constrained('ediciones')->cascadeOnDelete();
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gasto')->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->decimal('base_imponible', 10, 2);
            $table->enum('tipo_iva', ['0', '4', '10', '21'])->default('21');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
