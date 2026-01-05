<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('remitente')->nullable();
            $table->json('correos_copia')->nullable();
            $table->boolean('notificar_asignacion')->default(true);
            $table->boolean('notificar_mantenimiento')->default(true);
            $table->integer('dias_anticipacion_alerta')->default(15);
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // encrypted
            $table->string('smtp_encryption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
