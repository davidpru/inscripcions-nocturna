<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->unsignedInteger('numero_dorsal')->nullable()->after('dorsal_recogido');
            $table->unique(['edicion_id', 'numero_dorsal'], 'inscripciones_edicion_dorsal_unique');
        });

        Schema::table('ediciones', function (Blueprint $table) {
            $table->unsignedBigInteger('dorsal_primer_masculino_id')->nullable()->after('lista_espera_cerrada');
            $table->unsignedBigInteger('dorsal_primera_femenina_id')->nullable()->after('dorsal_primer_masculino_id');
            $table->foreign('dorsal_primer_masculino_id')->references('id')->on('inscripciones')->nullOnDelete();
            $table->foreign('dorsal_primera_femenina_id')->references('id')->on('inscripciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropForeign(['dorsal_primer_masculino_id']);
            $table->dropForeign(['dorsal_primera_femenina_id']);
            $table->dropColumn(['dorsal_primer_masculino_id', 'dorsal_primera_femenina_id']);
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropUnique('inscripciones_edicion_dorsal_unique');
            $table->dropColumn('numero_dorsal');
        });
    }
};
