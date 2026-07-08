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
        Schema::create('thumbnails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('prompt_id')->nullable()->constrained('generated_prompts')->nullOnDelete();
            
            $table->string('title')->nullable();
            $table->string('image_url');
            $table->string('local_path')->nullable();
            $table->string('ai_provider')->default('gemini');
            
            $table->enum('style', [
                'minimalist',
                'bold_text',
                'character_focused',
                'text_overlay',
                'concept_art',
                'photographic'
            ])->default('bold_text');
            
            $table->enum('status', ['generating', 'ready', 'downloading', 'downloaded', 'failed'])->default('generating');
            $table->json('generation_params')->nullable();
            $table->json('ai_metadata')->nullable();
            
            $table->integer('width')->default(1280);
            $table->integer('height')->default(720);
            $table->string('file_format')->default('png');
            $table->bigInteger('file_size')->nullable();
            
            $table->boolean('is_selected')->default(false);
            $table->unsignedTinyInteger('version')->default(1);
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'is_selected']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thumbnails');
    }
};
