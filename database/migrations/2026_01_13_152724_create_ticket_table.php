<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('tickets', function (Blueprint $table) {
            $table->id();

            // Código único del ticket
            $table->string('numero_ticket', 50)->unique();

            // Relaciones
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('sede_id');
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('solicitado_por_id');   // persona que solicita
            $table->unsignedBigInteger('responsable_actual_id'); // persona que actualmente atiende
            $table->unsignedBigInteger('prioridad_id')->nullable();
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->unsignedBigInteger('tipo_ticket_id')->nullable(); // incidente, requerimiento, etc.

            // Datos del ticket
            $table->string('asunto', 200);
            $table->text('descripcion')->nullable();
            $table->text('nota')->nullable(); // notas internas
            $table->boolean('estado')->default(true); // activo/cerrado

            // Fechas específicas
            $table->dateTime('fecha_creacion')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('fecha_cierre')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('sede_id')->references('id')->on('sedes_cliente')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('areas_cliente')->onDelete('cascade');
            $table->foreign('solicitado_por_id')->references('id')->on('personas_cliente')->onDelete('cascade');
            $table->foreign('responsable_actual_id')->references('id')->on('personas_cliente')->onDelete('cascade');
            $table->foreign('prioridad_id')->references('id')->on('prioridades_ticket')->onDelete('set null');
            $table->foreign('estado_id')->references('id')->on('estados_ticket')->onDelete('set null');
            $table->foreign('origen_id')->references('id')->on('ticket_origen')->onDelete('set null');
            $table->foreign('tipo_ticket_id')->references('id')->on('tipo_ticket')->onDelete('set null');

            // Índices con nombre corto
            $table->index(
                ['cliente_id', 'sede_id', 'area_id', 'solicitado_por_id', 'responsable_actual_id', 'tipo_ticket_id'],
                'idx_ticket_relaciones'
            );
        });

    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('tickets');
    }
};
