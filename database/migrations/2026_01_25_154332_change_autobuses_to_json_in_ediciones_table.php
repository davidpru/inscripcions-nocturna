<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero, obtener los datos actuales para migrarlos
        $ediciones = DB::table('ediciones')->get();
        
        Schema::table('ediciones', function (Blueprint $table) {
            $table->json('autobuses')->nullable()->after('estado');
        });

        // Migrar datos existentes
        foreach ($ediciones as $edicion) {
            $autobusesArray = [];
            $numAutobuses = $edicion->numero_autobuses ?? 2;
            $plazasPorAutobus = $edicion->plazas_por_autobus ?? 55;
            
            for ($i = 1; $i <= $numAutobuses; $i++) {
                $autobusesArray[] = [
                    'nombre' => "Autobús $i",
                    'plazas' => $plazasPorAutobus,
                ];
            }
            
            DB::table('ediciones')
                ->where('id', $edicion->id)
                ->update(['autobuses' => json_encode($autobusesArray)]);
        }

        // Eliminar columnas antiguas
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn(['numero_autobuses', 'plazas_por_autobus']);
        });
    }

    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->integer('numero_autobuses')->default(2)->after('estado');
            $table->integer('plazas_por_autobus')->default(55)->after('numero_autobuses');
        });

        // Migrar datos de vuelta
        $ediciones = DB::table('ediciones')->get();
        foreach ($ediciones as $edicion) {
            $autobuses = json_decode($edicion->autobuses, true) ?? [];
            $numAutobuses = count($autobuses);
            $plazasPorAutobus = $numAutobuses > 0 ? $autobuses[0]['plazas'] : 55;
            
            DB::table('ediciones')
                ->where('id', $edicion->id)
                ->update([
                    'numero_autobuses' => $numAutobuses,
                    'plazas_por_autobus' => $plazasPorAutobus,
                ]);
        }

        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn('autobuses');
        });
    }
};
