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
        Schema::create('cyb_solicitud_itsm', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cyb_solicitud_id');
            $table->integer('ticket_id_itsm')->unique();

            $table->timestamps();

            $table->index('cyb_solicitud_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cyb_solicitud_itsm');
    }
};
