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
        Schema::connection('helpdesk_cyberline')->create('ticket_origen', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50); // Ej: Correo, WhatsApp, Llamada, Web
            $table->boolean('estado')->default(true); // Activo o inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canales_ticket');
    }
};
