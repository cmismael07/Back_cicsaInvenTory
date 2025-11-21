<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->date('fecha_compra')->nullable()->after('clave');
        });
    }

    public function down()
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropColumn('fecha_compra');
        });
    }
};
