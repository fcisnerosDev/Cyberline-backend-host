<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('sedes_cliente', function (Blueprint $table) {

            $table->id();

            // Relación con cliente
            $table->unsignedBigInteger('cliente_id');

            // Datos de la sede
            $table->string('nombre', 150);                 // Ej: Lima - San Isidro
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('pais', 100)->nullable();

            // Estado
            $table->boolean('estado')->default(true);

            $table->timestamps();

            // Índices
            $table->index('cliente_id');


            $table->foreign('cliente_id')
                ->references('id')
                ->on('clientes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('sedes_cliente');
    }
};
