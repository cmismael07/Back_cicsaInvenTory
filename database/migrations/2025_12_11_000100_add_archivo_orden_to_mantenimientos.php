<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (! Schema::hasColumn('mantenimientos', 'archivo_orden')) {
                $table->string('archivo_orden')->nullable()->after('costo');
            }
        });
    }

    public function down()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimientos', 'archivo_orden')) {
                $table->dropColumn('archivo_orden');
            }
        });
    }
};
