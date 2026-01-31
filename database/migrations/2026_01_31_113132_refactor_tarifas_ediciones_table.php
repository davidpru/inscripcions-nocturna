<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            // Añadir nuevas columnas después de autobuses
            $table->decimal('precio_inscripcion_socio_normal', 8, 2)->default(30)->after('autobuses');
            $table->decimal('precio_inscripcion_publico_normal', 8, 2)->default(35)->after('precio_inscripcion_socio_normal');
            $table->decimal('precio_inscripcion_socio_tardia', 8, 2)->default(35)->after('precio_inscripcion_publico_normal');
            $table->decimal('precio_inscripcion_publico_tardia', 8, 2)->default(40)->after('precio_inscripcion_socio_tardia');
            $table->decimal('precio_licencia_federativa_socio', 8, 2)->default(5)->after('precio_inscripcion_publico_tardia');
            $table->decimal('precio_licencia_federativa_publico', 8, 2)->default(5)->after('precio_licencia_federativa_socio');
        });

        // Migrar datos: calcular nuevos valores desde los antiguos
        // tarifa_no_federado = inscripcion + licencia
        // tarifa_federado = inscripcion
        DB::statement('UPDATE ediciones SET 
            precio_inscripcion_socio_normal = tarifa_socio_federado_normal,
            precio_inscripcion_publico_normal = tarifa_publico_federado_normal,
            precio_inscripcion_socio_tardia = tarifa_socio_federado_tardia,
            precio_inscripcion_publico_tardia = tarifa_publico_federado_tardia,
            precio_licencia_federativa_socio = tarifa_socio_no_federado_normal - tarifa_socio_federado_normal,
            precio_licencia_federativa_publico = tarifa_publico_no_federado_normal - tarifa_publico_federado_normal
        ');

        Schema::table('ediciones', function (Blueprint $table) {
            // Eliminar columnas antiguas
            $table->dropColumn([
                'tarifa_publico_federado_normal',
                'tarifa_publico_no_federado_normal',
                'tarifa_socio_federado_normal',
                'tarifa_socio_no_federado_normal',
                'tarifa_publico_federado_tardia',
                'tarifa_publico_no_federado_tardia',
                'tarifa_socio_federado_tardia',
                'tarifa_socio_no_federado_tardia',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            // Restaurar columnas antiguas después de autobuses
            $table->decimal('tarifa_publico_federado_normal', 8, 2)->default(35)->after('autobuses');
            $table->decimal('tarifa_publico_no_federado_normal', 8, 2)->default(40)->after('tarifa_publico_federado_normal');
            $table->decimal('tarifa_socio_federado_normal', 8, 2)->default(30)->after('tarifa_publico_no_federado_normal');
            $table->decimal('tarifa_socio_no_federado_normal', 8, 2)->default(35)->after('tarifa_socio_federado_normal');
            $table->decimal('tarifa_publico_federado_tardia', 8, 2)->default(40)->after('tarifa_socio_no_federado_normal');
            $table->decimal('tarifa_publico_no_federado_tardia', 8, 2)->default(45)->after('tarifa_publico_federado_tardia');
            $table->decimal('tarifa_socio_federado_tardia', 8, 2)->default(35)->after('tarifa_publico_no_federado_tardia');
            $table->decimal('tarifa_socio_no_federado_tardia', 8, 2)->default(40)->after('tarifa_socio_federado_tardia');
        });

        // Migrar datos de vuelta
        DB::statement('UPDATE ediciones SET 
            tarifa_socio_federado_normal = precio_inscripcion_socio_normal,
            tarifa_socio_no_federado_normal = precio_inscripcion_socio_normal + precio_licencia_federativa_socio,
            tarifa_publico_federado_normal = precio_inscripcion_publico_normal,
            tarifa_publico_no_federado_normal = precio_inscripcion_publico_normal + precio_licencia_federativa_publico,
            tarifa_socio_federado_tardia = precio_inscripcion_socio_tardia,
            tarifa_socio_no_federado_tardia = precio_inscripcion_socio_tardia + precio_licencia_federativa_socio,
            tarifa_publico_federado_tardia = precio_inscripcion_publico_tardia,
            tarifa_publico_no_federado_tardia = precio_inscripcion_publico_tardia + precio_licencia_federativa_publico
        ');

        Schema::table('ediciones', function (Blueprint $table) {
            // Eliminar columnas nuevas
            $table->dropColumn([
                'precio_inscripcion_socio_normal',
                'precio_inscripcion_publico_normal',
                'precio_inscripcion_socio_tardia',
                'precio_inscripcion_publico_tardia',
                'precio_licencia_federativa_socio',
                'precio_licencia_federativa_publico',
            ]);
        });
    }
};
