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
    Schema::table('user_sessions', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->nullable(); // Agrega user_id como una clave foránea
    });
}

public function down()
{
    Schema::table('user_sessions', function (Blueprint $table) {
        $table->dropColumn('user_id');  // Elimina user_id si se revierte la migración
    });
}

};
