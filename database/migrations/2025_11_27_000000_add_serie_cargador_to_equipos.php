<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (! Schema::hasColumn('equipos', 'serie_cargador')) {
                // existing column in DB is 'serial' (backend uses 'serial' instead of 'numero_serie')
                if (Schema::hasColumn('equipos', 'serial')) {
                    $table->string('serie_cargador')->nullable()->after('serial');
                } else {
                    $table->string('serie_cargador')->nullable();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'serie_cargador')) {
                $table->dropColumn('serie_cargador');
            }
        });
    }
};
