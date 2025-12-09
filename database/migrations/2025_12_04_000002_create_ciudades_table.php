<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ciudades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedBigInteger('pais_id')->nullable();
            $table->string('abreviatura')->nullable();
            $table->timestamps();

            // No forzar clave foránea para evitar problemas en esquemas existentes
            // pero mantener campo pais_id para relaciones lógicas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudades');
    }
};
