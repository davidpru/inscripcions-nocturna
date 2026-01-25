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
        Schema::table('ediciones', function (Blueprint $table) {
            // Configuración de autobuses
            $table->integer('numero_autobuses')->default(2)->after('estado');
            $table->integer('plazas_por_autobus')->default(55)->after('numero_autobuses');
            
            // Tarifas de inscripción - Normal
            $table->decimal('tarifa_publico_federado_normal', 8, 2)->default(35)->after('plazas_por_autobus');
            $table->decimal('tarifa_publico_no_federado_normal', 8, 2)->default(40)->after('tarifa_publico_federado_normal');
            $table->decimal('tarifa_socio_federado_normal', 8, 2)->default(30)->after('tarifa_publico_no_federado_normal');
            $table->decimal('tarifa_socio_no_federado_normal', 8, 2)->default(35)->after('tarifa_socio_federado_normal');
            
            // Tarifas de inscripción - Tardía
            $table->decimal('tarifa_publico_federado_tardia', 8, 2)->default(40)->after('tarifa_socio_no_federado_normal');
            $table->decimal('tarifa_publico_no_federado_tardia', 8, 2)->default(45)->after('tarifa_publico_federado_tardia');
            $table->decimal('tarifa_socio_federado_tardia', 8, 2)->default(35)->after('tarifa_publico_no_federado_tardia');
            $table->decimal('tarifa_socio_no_federado_tardia', 8, 2)->default(40)->after('tarifa_socio_federado_tardia');
            
            // Tarifas adicionales
            $table->decimal('precio_autobus_normal', 8, 2)->default(12)->after('tarifa_socio_no_federado_tardia');
            $table->decimal('precio_autobus_tardia', 8, 2)->default(14)->after('precio_autobus_normal');
            $table->decimal('precio_seguro', 8, 2)->default(9)->after('precio_autobus_tardia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn([
                'numero_autobuses',
                'plazas_por_autobus',
                'tarifa_publico_federado_normal',
                'tarifa_publico_no_federado_normal',
                'tarifa_socio_federado_normal',
                'tarifa_socio_no_federado_normal',
                'tarifa_publico_federado_tardia',
                'tarifa_publico_no_federado_tardia',
                'tarifa_socio_federado_tardia',
                'tarifa_socio_no_federado_tardia',
                'precio_autobus_normal',
                'precio_autobus_tardia',
                'precio_seguro',
            ]);
        });
    }
};
