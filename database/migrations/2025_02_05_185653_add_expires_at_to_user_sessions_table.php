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
            $table->timestamp('expires_at')->nullable();  // Agrega la columna expires_at
        });
    }

    public function down()
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }

};
