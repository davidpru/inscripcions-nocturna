<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->unsignedInteger('plazas_autobus')->default(0)->after('autobuses');
        });

        // Rellenar plazas_autobus a partir del JSON autobuses existente
        $ediciones = DB::table('ediciones')->whereNotNull('autobuses')->get();
        foreach ($ediciones as $edicion) {
            $autobuses = json_decode($edicion->autobuses, true) ?? [];
            $totalPlazas = collect($autobuses)->sum('plazas');
            DB::table('ediciones')->where('id', $edicion->id)->update(['plazas_autobus' => $totalPlazas]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn('plazas_autobus');
        });
    }
};
