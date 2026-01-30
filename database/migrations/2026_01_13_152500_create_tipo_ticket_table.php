<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('tipo_ticket', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50); // Ej: Incidente, Requerimiento, Consulta
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('tipo_ticket');
    }
};
