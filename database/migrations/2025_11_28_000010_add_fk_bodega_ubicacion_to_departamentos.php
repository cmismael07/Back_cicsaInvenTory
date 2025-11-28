<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (Schema::hasColumn('departamentos', 'bodega_ubicacion_id') && ! app()->runningInConsole()) {
                // avoid running in some console contexts
            }

            if (Schema::hasTable('ubicaciones') && Schema::hasColumn('departamentos', 'bodega_ubicacion_id')) {
                // add foreign key if not exists (MySQL will error if duplicates; guard by try)
                try {
                    $table->foreign('bodega_ubicacion_id')->references('id')->on('ubicaciones')->onDelete('set null');
                } catch (\Throwable $e) {
                    // ignore - foreign key may already exist or DB not support
                }
            }
        });
    }

    public function down()
    {
        Schema::table('departamentos', function (Blueprint $table) {
            try {
                $table->dropForeign(['bodega_ubicacion_id']);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
