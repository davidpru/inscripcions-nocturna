<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->decimal('distancia_km', 6, 2)->nullable()->after('fecha_evento');
        });
    }

    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn('distancia_km');
        });
    }
};
