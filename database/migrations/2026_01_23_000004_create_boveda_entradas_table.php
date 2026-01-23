<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boveda_entradas', function (Blueprint $table) {
            $table->id();
            $table->string('servicio');
            $table->string('usuario');
            $table->text('password_hash');
            $table->string('url')->nullable();
            $table->string('categoria')->default('Otros');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boveda_entradas');
    }
};
