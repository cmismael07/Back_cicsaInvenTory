<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('planes_mantenimiento')) {
            Schema::create('planes_mantenimiento', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->integer('anio');
                $table->string('creado_por')->nullable();
                $table->date('fecha_creacion')->nullable();
                $table->string('estado')->default('ACTIVO');
                $table->unsignedBigInteger('ciudad_id')->nullable();
                $table->string('ciudad_nombre')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('planes_mantenimiento');
    }
};
