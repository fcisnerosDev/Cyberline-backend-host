<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;



return new class extends Migration {
    public function up(): void
    {
        Schema::table('sysNodo', function (Blueprint $table) {
            $table->integer('SyncParche')->default(0)->after('fechaSyncPadre');
        });

        // Establecer el valor por defecto en los registros existentes
        DB::table('sysNodo')->update(['SyncParche' => 0]);
    }

    public function down(): void
    {
        Schema::table('sysNodo', function (Blueprint $table) {
            $table->dropColumn('SyncParche');
        });
    }
};



