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
        Schema::table('cupones', function (Blueprint $table) {
            $table->decimal('descuento_porcentaje', 5, 2)
                ->default(100)
                ->after('edicion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cupones', function (Blueprint $table) {
            $table->dropColumn('descuento_porcentaje');
        });
    }
};
