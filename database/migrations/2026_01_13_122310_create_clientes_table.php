<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('helpdesk_cyberline')->create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string('razon_social', 150);
            $table->string('nombre_comercial', 150)->nullable();

            // Identificador externo único
            $table->string('idCompaniaNodo', 50)->unique();

            $table->string('ruc', 15)->nullable()->unique();
            $table->string('email_principal', 150)->nullable();
            $table->string('telefono_principal', 50)->nullable();

            $table->boolean('estado')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk_cyberline')->dropIfExists('clientes');
    }
};

