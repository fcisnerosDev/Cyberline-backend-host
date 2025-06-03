<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sysNodo', function (Blueprint $table) {
            $table->string('idNodo', 45)->primary();
            $table->string('urlWs', 150);
            $table->string('nombre', 40);
            $table->string('ip', 15)->nullable();
            $table->dateTime('fecha')->nullable();
            $table->enum('flgMonitoreo', ['0', '1', '2'])->nullable();
            $table->string('mensajeMonitoreo', 250)->nullable();
            $table->enum('flgMsjMonitoreo', ['0', '1'])->nullable();
            $table->dateTime('fechaVerificacionMonitoreo')->nullable();
            $table->enum('flgEstado', ['0', '1', '2'])->default('1');
            $table->string('idNodoPadre', 45)->nullable()->index();
            $table->dateTime('fechaRegistro')->nullable();
            $table->dateTime('fechaUltimoLogCorreo')->nullable();
            $table->dateTime('fechaConexion')->nullable();
            $table->enum('flgConexion', ['0', '1', '2'])->default('0');
            $table->integer('idUsuario')->nullable();
            $table->string('idUsuarioNodo', 45)->nullable();
            $table->enum('flgSyncHijo', ['0', '1'])->default('0');
            $table->enum('flgSyncPadre', ['0', '1'])->default('0');
            $table->dateTime('fechaSyncHijo')->nullable();
            $table->dateTime('fechaSyncPadre')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nodos');
    }
};
