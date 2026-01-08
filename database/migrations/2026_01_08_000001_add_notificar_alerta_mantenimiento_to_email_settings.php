<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('email_settings', 'notificar_alerta_mantenimiento')) {
                $table->boolean('notificar_alerta_mantenimiento')->default(true)->after('notificar_mantenimiento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            if (Schema::hasColumn('email_settings', 'notificar_alerta_mantenimiento')) {
                $table->dropColumn('notificar_alerta_mantenimiento');
            }
        });
    }
};
