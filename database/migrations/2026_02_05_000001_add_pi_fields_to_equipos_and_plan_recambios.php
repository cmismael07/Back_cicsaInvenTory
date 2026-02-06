<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('equipos', 'pi_compra')) {
                $table->string('pi_compra')->nullable()->after('plan_recambio_id');
            }
            if (!Schema::hasColumn('equipos', 'pi_recambio')) {
                $table->string('pi_recambio')->nullable()->after('pi_compra');
            }
        });

        Schema::table('plan_recambios', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_recambios', 'pi_recambio')) {
                $table->string('pi_recambio')->nullable()->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'pi_recambio')) {
                $table->dropColumn('pi_recambio');
            }
            if (Schema::hasColumn('equipos', 'pi_compra')) {
                $table->dropColumn('pi_compra');
            }
        });

        Schema::table('plan_recambios', function (Blueprint $table) {
            if (Schema::hasColumn('plan_recambios', 'pi_recambio')) {
                $table->dropColumn('pi_recambio');
            }
        });
    }
};
