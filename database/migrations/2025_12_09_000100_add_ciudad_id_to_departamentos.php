<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('departamentos', 'ciudad_id')) {
                $table->unsignedBigInteger('ciudad_id')->nullable()->after('bodega_ubicacion_id');
            }
        });
    }

    public function down()
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (Schema::hasColumn('departamentos', 'ciudad_id')) {
                $table->dropColumn('ciudad_id');
            }
        });
    }
};
