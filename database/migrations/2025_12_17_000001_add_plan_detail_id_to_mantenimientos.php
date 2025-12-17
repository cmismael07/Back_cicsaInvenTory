<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_detail_id')->nullable()->after('equipo_id');
            $table->foreign('plan_detail_id')->references('id')->on('detalles_planes_mantenimiento')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['plan_detail_id']);
            $table->dropColumn('plan_detail_id');
        });
    }
};
