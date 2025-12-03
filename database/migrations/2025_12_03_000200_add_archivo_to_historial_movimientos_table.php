<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('historial_movimientos', function (Blueprint $table) {
            $table->string('archivo')->nullable()->after('nota');
        });
    }

    public function down()
    {
        Schema::table('historial_movimientos', function (Blueprint $table) {
            $table->dropColumn('archivo');
        });
    }
};
