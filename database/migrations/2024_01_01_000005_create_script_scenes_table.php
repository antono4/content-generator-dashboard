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
        Schema::create('script_scenes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('video_script_id')->constrained()->onDelete('cascade');
            
            $table->unsignedSmallInteger('scene_number');
            $table->string('scene_title');
            $table->text('narration');
            $table->text('visual_direction');
            
            $table->enum('shot_type', [
                'wide_shot',
                'medium_shot',
                'close_up',
                'extreme_close_up',
                'pov',
                'over_shoulder',
                'aerial',
                'tracking',
                'static'
            ])->default('medium_shot');
            
            $table->string('camera_movement')->nullable();
            $table->string('lighting')->nullable();
            $table->string('location')->nullable();
            
            $table->integer('duration_seconds')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->uuid('thumbnail_id')->nullable();
            
            $table->json('visual_prompt')->nullable();
            $table->json('transition')->nullable();
            
            $table->text('notes')->nullable();
            $table->boolean('is_generated')->default(false);
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreignUuid('thumbnail_id')->nullable()
                  ->references('id')->on('thumbnails')->nullOnDelete();
            $table->index(['video_script_id', 'scene_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('script_scenes');
    }
};
