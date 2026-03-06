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
            $table->integer('limite_tarifa_tardia_inscritos')
                ->default(650)
                ->after('limite_inscritos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn('limite_tarifa_tardia_inscritos');
        });
    }
};
