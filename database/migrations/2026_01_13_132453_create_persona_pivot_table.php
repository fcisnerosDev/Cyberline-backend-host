<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('persona_pivot', function (Blueprint $table) {

    $table->id();

    // Relaciones
    $table->unsignedBigInteger('persona_id');   // FK → personas_cliente
    $table->unsignedBigInteger('cliente_id');   // FK → clientes
    $table->unsignedBigInteger('sede_id');      // FK → sedes_cliente
    $table->unsignedBigInteger('area_id');      // FK → areas_cliente
    $table->unsignedBigInteger('cargo_id');     // FK → cargo_persona

    $table->timestamps();

    // Índice con nombre corto
    $table->index(['persona_id', 'cliente_id', 'sede_id', 'area_id', 'cargo_id'], 'persona_pivot_idx');

    // Foreign Keys
    $table->foreign('persona_id')
        ->references('id')->on('personas_cliente')
        ->onDelete('cascade');

    $table->foreign('cliente_id')
        ->references('id')->on('clientes')
        ->onDelete('cascade');

    $table->foreign('sede_id')
        ->references('id')->on('sedes_cliente')
        ->onDelete('cascade');

    $table->foreign('area_id')
        ->references('id')->on('areas_cliente')
        ->onDelete('cascade');

    $table->foreign('cargo_id')
        ->references('id')->on('cargo_persona')
        ->onDelete('cascade');
});

    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('persona_pivot');
    }
};
