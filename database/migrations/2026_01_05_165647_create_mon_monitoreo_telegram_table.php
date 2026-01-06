<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('mon_monitoreo_telegram', function (Blueprint $table) {
    $table->id();

    $table->unsignedInteger('idMonitoreo');
    $table->string('idMonitoreoNodo', 45);
    $table->string('idNodoPerspectiva', 45);

    $table->timestamp('last_notified_at')->nullable();
    $table->timestamps();

    // 👇 NOMBRE CORTO DEL ÍNDICE
    $table->unique(
        ['idMonitoreo', 'idMonitoreoNodo', 'idNodoPerspectiva'],
        'uk_mon_mon_telegram'
    );

    $table->index('idMonitoreo', 'idx_mon_mon_telegram_mon');
    $table->index('idNodoPerspectiva', 'idx_mon_mon_telegram_nodo');
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mon_monitoreo_telegram');
    }
};
