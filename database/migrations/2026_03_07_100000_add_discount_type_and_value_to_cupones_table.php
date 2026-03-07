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
        Schema::table('cupones', function (Blueprint $table) {
            $table->string('descuento_tipo', 20)->default('porcentaje')->after('edicion_id');
            $table->decimal('descuento_valor', 8, 2)->default(100)->after('descuento_tipo');
        });

        // Migrate existing percentage coupons to the new generic fields.
        DB::table('cupones')->update([
            'descuento_tipo' => 'porcentaje',
            'descuento_valor' => DB::raw('COALESCE(descuento_porcentaje, 100)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cupones', function (Blueprint $table) {
            $table->dropColumn(['descuento_tipo', 'descuento_valor']);
        });
    }
};
