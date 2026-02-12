<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk_cyberline';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                  ->constrained('helpdesk_messages', 'id')
                  ->cascadeOnDelete();
            $table->enum('type', ['to', 'cc']);
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('full');
            $table->timestamps();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_recipients');
    }
};
