<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('prioridades_ticket', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);   // Ej: Alta, Media, Baja, Crítica
            $table->string('color', 7)->nullable(); // Código HEX para color (#FF0000)
            $table->boolean('estado')->default(true); // Activa o inactiva
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('prioridades_ticket');
    }
};
