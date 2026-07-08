<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generated_prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            
            $table->enum('prompt_type', [
                'title_idea',
                'text_content',
                'visual_thumbnail',
                'visual_storyboard',
                'seo_optimized_title',
                'seo_tags',
                'description'
            ]);
            
            $table->string('input_keywords')->nullable();
            $table->text('generated_prompt');
            $table->text('ai_response')->nullable();
            $table->json('variations')->nullable();
            $table->json('metadata')->nullable();
            
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('usage_count')->default(0);
            $table->decimal('processing_time_ms', 10, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'prompt_type']);
            $table->index(['prompt_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_prompts');
    }
};
