<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->decimal('valor_compra', 10, 2)->nullable()->default(0)->after('fecha_compra');
            $table->text('observaciones')->nullable()->after('estado');
            $table->date('fecha_compra')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'valor_compra')) {
                $table->dropColumn('valor_compra');
            }
            if (Schema::hasColumn('equipos', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
            // Note: do not revert fecha_compra type change here
        });
    }
};
