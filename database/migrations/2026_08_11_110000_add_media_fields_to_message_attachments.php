<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('message_attachments', 'media_url')) {
                $table->string('media_url')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('message_attachments', 'media_type')) {
                $table->string('media_type')->nullable()->after('media_url');
            }
            if (! Schema::hasColumn('message_attachments', 'transcription')) {
                $table->text('transcription')->nullable()->after('media_type');
            }
            if (! Schema::hasColumn('message_attachments', 'ai_description')) {
                $table->text('ai_description')->nullable()->after('transcription');
            }
            if (! Schema::hasColumn('message_attachments', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('ai_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->dropColumn(['media_url', 'media_type', 'transcription', 'ai_description', 'mime_type']);
        });
    }
};