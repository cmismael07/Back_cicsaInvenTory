<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_licencia', function (Blueprint $table) {
            $table->string('proveedor')->nullable()->after('nombre');
            $table->text('descripcion')->nullable()->after('proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_licencia', function (Blueprint $table) {
            $table->dropColumn(['proveedor', 'descripcion']);
        });
    }
};