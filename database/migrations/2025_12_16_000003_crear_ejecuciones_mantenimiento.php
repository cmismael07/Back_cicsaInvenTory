<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ejecuciones_mantenimiento')) {
            Schema::create('ejecuciones_mantenimiento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('detail_id')->constrained('detalles_planes_mantenimiento')->cascadeOnDelete();
                $table->date('fecha')->nullable();
                $table->string('tecnico')->nullable();
                $table->text('observaciones')->nullable();
                $table->string('archivo')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ejecuciones_mantenimiento');
    }
};
