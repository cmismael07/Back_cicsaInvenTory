<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasConstraint(string $table, string $constraint): bool
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $db = DB::getDatabaseName();
                $rows = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
                    [$db, $table, $constraint]
                );
                return !empty($rows);
            }
        } catch (\Throwable $e) {
            // Si falla introspección, asumir que no existe para no bloquear migración
        }
        return false;
    }

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
        if (Schema::hasTable('detalles_planes_mantenimiento')
            && ! $this->hasConstraint('detalles_planes_mantenimiento', 'detalles_planes_mantenimiento_plan_id_foreign')) {
            Schema::table('detalles_planes_mantenimiento', function (Blueprint $table) {
                $table->foreign('plan_id')->references('id')->on('planes_mantenimiento')->cascadeOnDelete();
            });
        }
        if (Schema::hasTable('ejecuciones_mantenimiento')
            && ! $this->hasConstraint('ejecuciones_mantenimiento', 'ejecuciones_mantenimiento_detail_id_foreign')) {
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
