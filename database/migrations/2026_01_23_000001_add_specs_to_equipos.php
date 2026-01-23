<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('equipos', 'procesador')) {
                $table->string('procesador')->nullable()->after('serie_cargador');
            }
            if (!Schema::hasColumn('equipos', 'ram')) {
                $table->string('ram')->nullable()->after('procesador');
            }
            if (!Schema::hasColumn('equipos', 'disco_capacidad')) {
                $table->string('disco_capacidad')->nullable()->after('ram');
            }
            if (!Schema::hasColumn('equipos', 'disco_tipo')) {
                $table->string('disco_tipo')->nullable()->after('disco_capacidad');
            }
            if (!Schema::hasColumn('equipos', 'sistema_operativo')) {
                $table->string('sistema_operativo')->nullable()->after('disco_tipo');
            }
            if (!Schema::hasColumn('equipos', 'plan_recambio_id')) {
                $table->unsignedBigInteger('plan_recambio_id')->nullable()->after('sistema_operativo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'plan_recambio_id')) {
                $table->dropColumn('plan_recambio_id');
            }
            if (Schema::hasColumn('equipos', 'sistema_operativo')) {
                $table->dropColumn('sistema_operativo');
            }
            if (Schema::hasColumn('equipos', 'disco_tipo')) {
                $table->dropColumn('disco_tipo');
            }
            if (Schema::hasColumn('equipos', 'disco_capacidad')) {
                $table->dropColumn('disco_capacidad');
            }
            if (Schema::hasColumn('equipos', 'ram')) {
                $table->dropColumn('ram');
            }
            if (Schema::hasColumn('equipos', 'procesador')) {
                $table->dropColumn('procesador');
            }
        });
    }
};
