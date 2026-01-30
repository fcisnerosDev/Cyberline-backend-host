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
        Schema::connection('helpdesk_cyberline')->create('estados_ticket', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50); // Ej: Nuevo, En Proceso, Escalado, Cerrado
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_ticket');
    }
};
