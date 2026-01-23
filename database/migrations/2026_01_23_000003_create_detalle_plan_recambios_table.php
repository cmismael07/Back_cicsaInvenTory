<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_plan_recambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plan_recambios')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('equipo_codigo');
            $table->string('equipo_modelo')->nullable();
            $table->string('equipo_marca')->nullable();
            $table->integer('equipo_antiguedad')->default(0);
            $table->decimal('valor_reposicion', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_plan_recambios');
    }
};
