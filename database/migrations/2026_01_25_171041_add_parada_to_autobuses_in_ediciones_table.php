<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar todos los registros existentes en autobuses JSON para agregar parada 'pauls' por defecto
        DB::table('ediciones')->get()->each(function ($edicion) {
            if ($edicion->autobuses) {
                $autobuses = json_decode($edicion->autobuses, true);
                
                if (is_array($autobuses)) {
                    $autobusesActualizados = array_map(function ($bus) {
                        // Si no tiene el campo parada, añadirlo con valor 'pauls'
                        if (!isset($bus['parada'])) {
                            $bus['parada'] = 'pauls';
                        }
                        return $bus;
                    }, $autobuses);
                    
                    DB::table('ediciones')
                        ->where('id', $edicion->id)
                        ->update(['autobuses' => json_encode($autobusesActualizados)]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opcional: eliminar el campo parada de todos los autobuses
        DB::table('ediciones')->get()->each(function ($edicion) {
            if ($edicion->autobuses) {
                $autobuses = json_decode($edicion->autobuses, true);
                
                if (is_array($autobuses)) {
                    $autobusesActualizados = array_map(function ($bus) {
                        unset($bus['parada']);
                        return $bus;
                    }, $autobuses);
                    
                    DB::table('ediciones')
                        ->where('id', $edicion->id)
                        ->update(['autobuses' => json_encode($autobusesActualizados)]);
                }
            }
        });
    }
};