<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('cargo_persona', function (Blueprint $table) {

            $table->id();

            // Nombre del cargo
            $table->string('nombre', 100)->unique(); // Ej: Analista de Sistemas, Desarrollo, Soporte
            $table->string('descripcion', 255)->nullable();

            // Estado
            $table->boolean('estado')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('cargo_persona');
    }
};
