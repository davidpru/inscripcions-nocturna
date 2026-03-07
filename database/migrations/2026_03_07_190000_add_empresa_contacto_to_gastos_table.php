<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->string('empresa', 200)->nullable()->after('descripcion');
            $table->string('contacto_nombre', 150)->nullable()->after('empresa');
            $table->string('contacto_telefono', 30)->nullable()->after('contacto_nombre');
            $table->string('contacto_email', 200)->nullable()->after('contacto_telefono');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn(['empresa', 'contacto_nombre', 'contacto_telefono', 'contacto_email']);
        });
    }
};
