<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_recambios', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->string('nombre');
            $table->string('creado_por')->nullable();
            $table->date('fecha_creacion')->nullable();
            $table->decimal('presupuesto_estimado', 12, 2)->default(0);
            $table->integer('total_equipos')->default(0);
            $table->string('estado')->default('PROYECTO');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_recambios');
    }
};
