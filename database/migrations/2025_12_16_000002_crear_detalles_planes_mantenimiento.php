<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('detalles_planes_mantenimiento')) {
            Schema::create('detalles_planes_mantenimiento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('planes_mantenimiento')->cascadeOnDelete();
                $table->unsignedBigInteger('equipo_id')->nullable();
                $table->string('equipo_codigo')->nullable();
                $table->string('equipo_tipo')->nullable();
                $table->string('equipo_modelo')->nullable();
                $table->string('equipo_ubicacion')->nullable();
                $table->integer('mes_programado')->default(1);
                $table->string('estado')->default('PENDIENTE');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('detalles_planes_mantenimiento');
    }
};
