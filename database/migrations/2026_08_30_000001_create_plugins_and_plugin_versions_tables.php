<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('author')->nullable();
            $table->string('homepage_url')->nullable();
            $table->string('category')->nullable();
            $table->string('target_platform')->default('wordpress');
            $table->string('min_api_version')->nullable();
            $table->string('max_api_version')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->text('changelog')->nullable();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('sha256_checksum')->nullable();
            $table->string('status')->default('draft'); // draft, published, deprecated
            $table->boolean('is_current')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['plugin_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_versions');
        Schema::dropIfExists('plugins');
    }
};
