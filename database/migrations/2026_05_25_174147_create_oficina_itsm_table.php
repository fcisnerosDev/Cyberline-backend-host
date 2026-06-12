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
        Schema::create('oficina_itsm', function (Blueprint $table) {
            $table->id();

            $table->integer('idOficina');
            $table->integer('idCompania');

            $table->integer('id_oficina_glpi')->unique();
            $table->string('nombre_glpi');

            $table->timestamps();

            $table->unique(['idOficina', 'idCompania']);
            $table->index(['idOficina', 'idCompania']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oficina_itsm');
    }
};
