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
    // Schema::table('maePersona', function (Blueprint $table) {
    //     $table->integer('intentos_fallidos')->default(0);
    //     $table->boolean('bloqueado')->default(false);
    // });
}

public function down()
{
    // Schema::table('maePersona', function (Blueprint $table) {
    //     $table->dropColumn(['intentos_fallidos', 'bloqueado']);
    // });
}
};
