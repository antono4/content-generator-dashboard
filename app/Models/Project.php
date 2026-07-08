<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'primary_keyword',
        'status',
        'platform',
        'niche',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_IDEA_GENERATING = 'idea_generating';
    const STATUS_PROMPT_GENERATING = 'prompt_generating';
    const STATUS_THUMBNAIL_GENERATING = 'thumbnail_generating';
    const STATUS_SCRIPT_GENERATING = 'script_generating';
    const STATUS_SEO_OPTIMIZING = 'seo_optimizing';
    const STATUS_COMPLETED = 'completed';

    // Platform constants
    const PLATFORM_YOUTUBE = 'youtube';
    const PLATFORM_TIKTOK = 'tiktok';
    const PLATFORM_INSTAGRAM = 'instagram';
    const PLATFORM_ALL = 'all';

    /**
     * Get the user that owns the project
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all prompts for this project
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(GeneratedPrompt::class, 'project_id');
    }

    /**
     * Get all thumbnails for this project
     */
    public function thumbnails(): HasMany
    {
        return $this->hasMany(Thumbnail::class, 'project_id');
    }

    /**
     * Get the primary/selected thumbnail
     */
    public function primaryThumbnail(): HasOne
    {
        return $this->hasOne(Thumbnail::class, 'project_id')->where('is_selected', true);
    }

    /**
     * Get the video script for this project
     */
    public function videoScript(): HasOne
    {
        return $this->hasOne(VideoScript::class, 'project_id');
    }

    /**
     * Get the SEO optimization for this project
     */
    public function seoOptimization(): HasOne
    {
        return $this->hasOne(SeoOptimization::class, 'project_id');
    }

    /**
     * Get title prompts
     */
    public function titlePrompts(): HasMany
    {
        return $this->hasMany(GeneratedPrompt::class, 'project_id')
            ->where('prompt_type', 'title_idea');
    }

    /**
     * Get latest title prompt
     */
    public function latestTitlePrompt(): HasOne
    {
        return $this->hasOne(GeneratedPrompt::class, 'project_id')
            ->where('prompt_type', 'title_idea')
            ->latest();
    }

    /**
     * Get workflow progress percentage
     */
    public function getWorkflowProgressAttribute(): int
    {
        $completed = 0;
        $total = 5;

        if ($this->titlePrompts()->exists()) $completed++;
        if ($this->prompts()->where('prompt_type', '!=', 'title_idea')->exists()) $completed++;
        if ($this->thumbnails()->exists()) $completed++;
        if ($this->videoScript) $completed++;
        if ($this->seoOptimization) $completed++;

        return round(($completed / $total) * 100);
    }

    /**
     * Check if project is in active workflow
     */
    public function isInWorkflow(): bool
    {
        return !in_array($this->status, [self::STATUS_DRAFT, self::STATUS_COMPLETED]);
    }

    /**
     * Get current workflow step
     */
    public function getCurrentWorkflowStepAttribute(): int
    {
        return match($this->status) {
            self::STATUS_DRAFT => 0,
            self::STATUS_IDEA_GENERATING, self::STATUS_PROMPT_GENERATING => 1,
            self::STATUS_THUMBNAIL_GENERATING => 2,
            self::STATUS_SCRIPT_GENERATING => 3,
            self::STATUS_SEO_OPTIMIZING => 4,
            self::STATUS_COMPLETED => 5,
            default => 0
        };
    }

    /**
     * Scope for active projects
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DRAFT, self::STATUS_COMPLETED]);
    }

    /**
     * Scope for completed projects
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for platform filter
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Boot method to generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = \Illuminate\Support\Str::slug($project->title) . '-' . \Illuminate\Support\Str::random(6);
            }
        });
    }
}
