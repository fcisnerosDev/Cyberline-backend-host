<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('areas_cliente', function (Blueprint $table) {

            $table->id();

            // Relaciones
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('sede_id');

            // Datos del área
            $table->string('nombre', 150);             // Ej: Sistemas, RRHH, Contabilidad


            // Correos del área (para tickets por correo)
            $table->string('email_area', 150)->nullable();        // soporte@cliente.com


            // Configuración
          
            $table->boolean('estado')->default(true);

            $table->timestamps();

            // Índices
            $table->index(['cliente_id', 'sede_id']);

            // FKs
            $table->foreign('cliente_id')
                ->references('id')->on('clientes')
                ->onDelete('cascade');

            $table->foreign('sede_id')
                ->references('id')->on('sedes_cliente')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('areas_cliente');
    }
};
