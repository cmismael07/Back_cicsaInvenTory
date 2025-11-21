<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('historial_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('from_ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('to_ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->dateTime('fecha')->nullable();
            $table->text('nota')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('historial_movimientos');
    }
};
