<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tipo_equipos') && ! Schema::hasColumn('tipo_equipos', 'frecuencia_anual')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                $table->integer('frecuencia_anual')->default(1)->after('descripcion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tipo_equipos') && Schema::hasColumn('tipo_equipos', 'frecuencia_anual')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                $table->dropColumn('frecuencia_anual');
            });
        }
    }
};
