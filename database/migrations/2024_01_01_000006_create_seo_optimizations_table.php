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
        Schema::create('seo_optimizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('video_script_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('final_title');
            $table->string('original_title')->nullable();
            $table->text('title_reasoning')->nullable();
            
            $table->json('recommended_tags');
            $table->json('trending_tags')->nullable();
            $table->json('niche_tags')->nullable();
            
            $table->string('primary_tag')->nullable();
            $table->json('secondary_tags')->nullable();
            
            $table->integer('estimated_length')->nullable();
            $table->enum('best_posting_time', [
                'morning',
                'lunch',
                'evening',
                'late_night'
            ])->nullable();
            
            $table->json('competitor_analysis')->nullable();
            $table->json('keyword_difficulty')->nullable();
            
            $table->decimal('seo_score', 5, 2)->nullable();
            $table->json('recommendations')->nullable();
            
            $table->enum('status', ['pending', 'analyzing', 'completed', 'applied'])->default('pending');
            
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
        Schema::dropIfExists('seo_optimizations');
    }
};
