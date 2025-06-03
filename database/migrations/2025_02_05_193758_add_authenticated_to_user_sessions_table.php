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
            $table->boolean('authenticated')->default(false); // Marca si la sesión está autenticada
        });
    }

    public function down()
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn('authenticated');
        });
    }

};
