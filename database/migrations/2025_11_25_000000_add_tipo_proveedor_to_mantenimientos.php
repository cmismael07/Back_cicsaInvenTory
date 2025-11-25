<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (! Schema::hasColumn('mantenimientos', 'tipo')) {
                $table->string('tipo')->nullable()->after('fecha_inicio');
            }
            if (! Schema::hasColumn('mantenimientos', 'proveedor')) {
                $table->string('proveedor')->nullable()->after('tipo');
            }
        });
    }

    public function down()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimientos', 'proveedor')) {
                $table->dropColumn('proveedor');
            }
            if (Schema::hasColumn('mantenimientos', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
