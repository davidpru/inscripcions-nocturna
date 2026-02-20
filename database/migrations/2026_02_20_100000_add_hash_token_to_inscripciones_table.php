<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('hash_token', 64)->nullable()->unique()->after('id');
        });

        // Generar hash para inscripciones existentes
        $inscripciones = DB::table('inscripciones')->whereNull('hash_token')->get();
        foreach ($inscripciones as $inscripcion) {
            DB::table('inscripciones')
                ->where('id', $inscripcion->id)
                ->update(['hash_token' => Str::random(32)]);
        }

        // Hacer el campo NOT NULL después de rellenar (sin re-añadir unique)
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('hash_token', 64)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn('hash_token');
        });
    }
};
