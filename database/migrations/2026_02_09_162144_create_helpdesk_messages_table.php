<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk_cyberline'; // conexión específica

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('subject');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('seen')->default(false);
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_messages');
    }
};
