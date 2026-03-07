<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->string('titulo', 150)->after('categoria_gasto_id');
            $table->boolean('presupuestado')->default(false)->after('total');
            $table->foreignId('presupuestado_por')->nullable()->after('presupuestado')->constrained('administradores')->nullOnDelete();
            $table->boolean('aceptado')->default(false)->after('presupuestado_por');
            $table->foreignId('aceptado_por')->nullable()->after('aceptado')->constrained('administradores')->nullOnDelete();
            $table->boolean('pagado')->default(false)->after('aceptado_por');
            $table->foreignId('pagado_por')->nullable()->after('pagado')->constrained('administradores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['presupuestado_por']);
            $table->dropForeign(['aceptado_por']);
            $table->dropForeign(['pagado_por']);
            $table->dropColumn(['titulo', 'presupuestado', 'presupuestado_por', 'aceptado', 'aceptado_por', 'pagado', 'pagado_por']);
        });
    }
};