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
        if (!Schema::hasTable('historial_movimientos')) return;
        Schema::table('historial_movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('historial_movimientos', 'tipo_accion')) {
                $table->string('tipo_accion')->nullable()->after('nota');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('historial_movimientos')) return;
        Schema::table('historial_movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('historial_movimientos', 'tipo_accion')) {
                $table->dropColumn('tipo_accion');
            }
        });
    }
};
