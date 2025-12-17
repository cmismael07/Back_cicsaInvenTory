<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('detalles_planes_mantenimiento')) {
            if (! Schema::hasColumn('detalles_planes_mantenimiento', 'fecha_ejecucion')) {
                Schema::table('detalles_planes_mantenimiento', function (Blueprint $table) {
                    $table->dateTime('fecha_ejecucion')->nullable()->after('estado');
                });
            }
            if (! Schema::hasColumn('detalles_planes_mantenimiento', 'tecnico_responsable')) {
                Schema::table('detalles_planes_mantenimiento', function (Blueprint $table) {
                    $table->string('tecnico_responsable')->nullable()->after('fecha_ejecucion');
                });
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('detalles_planes_mantenimiento')) {
            Schema::table('detalles_planes_mantenimiento', function (Blueprint $table) {
                if (Schema::hasColumn('detalles_planes_mantenimiento', 'tecnico_responsable')) {
                    $table->dropColumn('tecnico_responsable');
                }
                if (Schema::hasColumn('detalles_planes_mantenimiento', 'fecha_ejecucion')) {
                    $table->dropColumn('fecha_ejecucion');
                }
            });
        }
    }
};
