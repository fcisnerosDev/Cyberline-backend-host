<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'helpdesk_cyberline';

    public function up(): void
    {
        Schema::table('helpdesk_attachments', function (Blueprint $table) {
            $table->string('content_id')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_attachments', function (Blueprint $table) {
            $table->dropColumn('content_id');
        });
    }
};
