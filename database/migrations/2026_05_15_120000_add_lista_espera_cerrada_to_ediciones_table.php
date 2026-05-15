<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->boolean('lista_espera_cerrada')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('ediciones', function (Blueprint $table) {
            $table->dropColumn('lista_espera_cerrada');
        });
    }
};
