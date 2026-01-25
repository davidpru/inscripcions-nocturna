<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dateTime('fecha_inicio_inscripciones')->nullable()->after('anio');
        });
    }

    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn('fecha_inicio_inscripciones');
        });
    }
};
