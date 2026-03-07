<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla pivote
        Schema::create('categoria_gasto_gasto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_id')->constrained('gastos')->onDelete('cascade');
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gasto')->onDelete('cascade');
            $table->unique(['gasto_id', 'categoria_gasto_id']);
        });

        // 2. Migrar datos existentes a la tabla pivote
        DB::statement('
            INSERT INTO categoria_gasto_gasto (gasto_id, categoria_gasto_id)
            SELECT id, categoria_gasto_id FROM gastos WHERE categoria_gasto_id IS NOT NULL
        ');

        // 3. Eliminar la columna FK antigua
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['categoria_gasto_id']);
            $table->dropColumn('categoria_gasto_id');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('categoria_gasto_id')->nullable()->after('edicion_id')->constrained('categorias_gasto')->nullOnDelete();
        });

        // Restaurar el primer registro de cada gasto
        DB::statement('
            UPDATE gastos g
            INNER JOIN (
                SELECT gasto_id, MIN(categoria_gasto_id) AS categoria_gasto_id
                FROM categoria_gasto_gasto
                GROUP BY gasto_id
            ) p ON g.id = p.gasto_id
            SET g.categoria_gasto_id = p.categoria_gasto_id
        ');

        Schema::dropIfExists('categoria_gasto_gasto');
    }
};
