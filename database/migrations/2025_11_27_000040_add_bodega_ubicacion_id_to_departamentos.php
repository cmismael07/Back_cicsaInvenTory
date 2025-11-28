<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('departamentos', 'bodega_ubicacion_id')) {
                $table->unsignedBigInteger('bodega_ubicacion_id')->nullable()->after('es_bodega');
            }
        });
    }

    public function down()
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (Schema::hasColumn('departamentos', 'bodega_ubicacion_id')) {
                $table->dropColumn('bodega_ubicacion_id');
            }
        });
    }
};
