<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sysNodo', function (Blueprint $table) {
            $table->boolean('alert_telegram')
                ->nullable()
                ->default(0)
                ; // columna que SÍ existe
        });

        // Asegura valor para registros existentes
        DB::table('sysNodo')->update(['alert_telegram' => 0]);
    }

    public function down(): void
    {
        Schema::table('sysNodo', function (Blueprint $table) {
            $table->dropColumn('alert_telegram');
        });
    }
};
