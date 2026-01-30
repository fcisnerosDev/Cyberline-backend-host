<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('personas_cliente', function (Blueprint $table) {

            $table->id();

            // Datos generales de la persona
            $table->string('nombres', 150);
            $table->string('apellidos', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telefono', 50)->nullable();


            // Configuración
            $table->boolean('estado')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('personas_cliente');
    }
};
