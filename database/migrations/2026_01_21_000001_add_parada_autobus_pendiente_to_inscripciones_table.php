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
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('parada_autobus')->nullable()->after('necesita_autobus');
            $table->string('parada_autobus_pendiente')->nullable()->after('parada_autobus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn(['parada_autobus', 'parada_autobus_pendiente']);
        });
    }
};
