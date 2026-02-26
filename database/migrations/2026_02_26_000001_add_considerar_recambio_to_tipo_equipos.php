<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tipo_equipos') && ! Schema::hasColumn('tipo_equipos', 'considerar_recambio')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                $table->boolean('considerar_recambio')->default(true)->after('frecuencia_anual');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tipo_equipos') && Schema::hasColumn('tipo_equipos', 'considerar_recambio')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                $table->dropColumn('considerar_recambio');
            });
        }
    }
};
