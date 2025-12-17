<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop foreign keys before renaming
        if (Schema::hasTable('maintenance_plan_details')) {
            Schema::table('maintenance_plan_details', function (Blueprint $table) {
                $table->dropForeign(['plan_id']);
            });
        }
        if (Schema::hasTable('maintenance_executions')) {
            Schema::table('maintenance_executions', function (Blueprint $table) {
                $table->dropForeign(['detail_id']);
            });
        }

        if (Schema::hasTable('maintenance_plans')) {
            Schema::rename('maintenance_plans', 'planes_mantenimiento');
        }
        if (Schema::hasTable('maintenance_plan_details')) {
            Schema::rename('maintenance_plan_details', 'detalles_planes_mantenimiento');
        }
        if (Schema::hasTable('maintenance_executions')) {
            Schema::rename('maintenance_executions', 'ejecuciones_mantenimiento');
        }

        // Recreate foreign keys with new table names
        if (Schema::hasTable('detalles_planes_mantenimiento')) {
            Schema::table('detalles_planes_mantenimiento', function (Blueprint $table) {
                $table->foreign('plan_id')->references('id')->on('planes_mantenimiento')->cascadeOnDelete();
            });
        }
        if (Schema::hasTable('ejecuciones_mantenimiento')) {
            Schema::table('ejecuciones_mantenimiento', function (Blueprint $table) {
                $table->foreign('detail_id')->references('id')->on('detalles_planes_mantenimiento')->cascadeOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('detalles_planes_mantenimiento')) {
            Schema::table('detalles_planes_mantenimiento', function (Blueprint $table) {
                $table->dropForeign(['plan_id']);
            });
        }
        if (Schema::hasTable('ejecuciones_mantenimiento')) {
            Schema::table('ejecuciones_mantenimiento', function (Blueprint $table) {
                $table->dropForeign(['detail_id']);
            });
        }

        if (Schema::hasTable('planes_mantenimiento')) {
            Schema::rename('planes_mantenimiento', 'maintenance_plans');
        }
        if (Schema::hasTable('detalles_planes_mantenimiento')) {
            Schema::rename('detalles_planes_mantenimiento', 'maintenance_plan_details');
        }
        if (Schema::hasTable('ejecuciones_mantenimiento')) {
            Schema::rename('ejecuciones_mantenimiento', 'maintenance_executions');
        }

        if (Schema::hasTable('maintenance_plan_details')) {
            Schema::table('maintenance_plan_details', function (Blueprint $table) {
                $table->foreign('plan_id')->references('id')->on('maintenance_plans')->cascadeOnDelete();
            });
        }
        if (Schema::hasTable('maintenance_executions')) {
            Schema::table('maintenance_executions', function (Blueprint $table) {
                $table->foreign('detail_id')->references('id')->on('maintenance_plan_details')->cascadeOnDelete();
            });
        }
    }
};
