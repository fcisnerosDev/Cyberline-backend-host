<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'helpdesk_cyberline';

    public function up(): void
    {
        Schema::create('helpdesk_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('helpdesk_messages')
                ->onDelete('cascade');

            $table->string('filename');       // nombre del archivo original
            $table->string('mime_type')->nullable(); // tipo MIME
            $table->string('path');           // ruta en storage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_attachments');
    }
};
