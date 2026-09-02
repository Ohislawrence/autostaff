<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tools
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('identifier')->unique(); // e.g., search_products
            $table->string('name');
            $table->text('description');
            $table->json('input_schema');
            $table->json('output_schema')->nullable();
            $table->string('handler_class');
            $table->boolean('requires_confirmation')->default(false);
            $table->string('category')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('custom_config')->nullable(); // HTTP method, URL, auth for custom tools
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // AI Employee Tool Permissions
        Schema::create('ai_employee_tool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_allowed')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            $table->integer('max_per_conversation')->nullable();
            $table->timestamps();
            $table->unique(['ai_employee_id', 'tool_id']);
        });

        // Tool Executions
        Schema::create('tool_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->json('input_parameters');
            $table->json('output_result')->nullable();
            $table->string('status'); // pending, confirmed, executing, success, failed, denied
            $table->text('error_message')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->boolean('was_confirmed')->default(false);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('conversation_id');
        });

        // Knowledge Bases
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // Knowledge Sources
        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // pdf, docx, txt, csv, url, manual, faq
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->string('source_url')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('progress')->default(0);
            $table->text('error_message')->nullable();
            $table->integer('chunk_count')->default(0);
            $table->integer('version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });

        // Knowledge Documents (processed)
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('knowledge_source_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->integer('chunk_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Knowledge Chunks
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('knowledge_document_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->json('embedding')->nullable(); // For database-based vector storage
            $table->integer('chunk_index');
            $table->integer('token_count')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('knowledge_document_id');
        });

        // Knowledge Embeddings (separate for vector DB)
        Schema::create('knowledge_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_chunk_id')->constrained()->cascadeOnDelete();
            $table->string('vector_id')->unique();
            $table->string('embedding_model')->nullable();
            $table->integer('dimensions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_embeddings');
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('knowledge_sources');
        Schema::dropIfExists('knowledge_bases');
        Schema::dropIfExists('tool_executions');
        Schema::dropIfExists('ai_employee_tool');
        Schema::dropIfExists('tools');
    }
};