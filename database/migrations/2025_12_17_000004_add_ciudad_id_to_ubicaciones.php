<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ubicaciones') && ! Schema::hasColumn('ubicaciones', 'ciudad_id')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->unsignedBigInteger('ciudad_id')->nullable()->after('nombre');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('ubicaciones') && Schema::hasColumn('ubicaciones', 'ciudad_id')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->dropColumn('ciudad_id');
            });
        }
    }
};
