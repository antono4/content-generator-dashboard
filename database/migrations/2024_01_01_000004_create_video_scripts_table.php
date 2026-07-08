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
        Schema::create('video_scripts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('title_prompt_id')->nullable()->constrained('generated_prompts')->nullOnDelete();
            $table->foreignUuid('description_prompt_id')->nullable()->constrained('generated_prompts')->nullOnDelete();
            
            $table->string('title');
            $table->text('hook')->nullable();
            $table->longText('script_content');
            $table->text('description')->nullable();
            $table->text('call_to_action')->nullable();
            
            $table->enum('script_type', [
                'tutorial',
                'vlog',
                'review',
                'educational',
                'entertainment',
                'shorts',
                'livestream'
            ])->default('tutorial');
            
            $table->enum('tone', [
                'professional',
                'casual',
                'humorous',
                'serious',
                'inspirational',
                'technical'
            ])->default('casual');
            
            $table->integer('estimated_duration_seconds')->nullable();
            $table->integer('word_count')->default(0);
            
            $table->json('seo_optimization')->nullable();
            $table->json('metadata')->nullable();
            
            $table->enum('status', ['draft', 'generating', 'completed', 'reviewing', 'approved', 'rejected'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_scripts');
    }
};
