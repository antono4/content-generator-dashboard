<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoOptimization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'video_script_id',
        'final_title',
        'original_title',
        'title_reasoning',
        'recommended_tags',
        'trending_tags',
        'niche_tags',
        'primary_tag',
        'secondary_tags',
        'estimated_length',
        'best_posting_time',
        'competitor_analysis',
        'keyword_difficulty',
        'seo_score',
        'recommendations',
        'status'
    ];

    protected $casts = [
        'recommended_tags' => 'array',
        'trending_tags' => 'array',
        'niche_tags' => 'array',
        'secondary_tags' => 'array',
        'competitor_analysis' => 'array',
        'keyword_difficulty' => 'array',
        'recommendations' => 'array',
        'seo_score' => 'decimal:2',
        'estimated_length' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ANALYZING = 'analyzing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_APPLIED = 'applied';

    // Posting time constants
    const TIME_MORNING = 'morning';
    const TIME_LUNCH = 'lunch';
    const TIME_EVENING = 'evening';
    const TIME_LATE_NIGHT = 'late_night';

    /**
     * Get the project this SEO belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the video script this SEO belongs to
     */
    public function videoScript(): BelongsTo
    {
        return $this->belongsTo(VideoScript::class, 'video_script_id');
    }

    /**
     * Get all tags combined
     */
    public function getAllTagsAttribute(): array
    {
        $tags = [];

        if ($this->primary_tag) {
            $tags[] = $this->primary_tag;
        }

        $tags = array_merge(
            $tags,
            $this->secondary_tags ?? [],
            $this->recommended_tags ?? [],
            $this->trending_tags ?? [],
            $this->niche_tags ?? []
        );

        return array_unique($tags);
    }

    /**
     * Get tags as comma-separated string
     */
    public function getTagsStringAttribute(): string
    {
        return implode(', ', $this->all_tags);
    }

    /**
     * Get formatted posting time
     */
    public function getPostingTimeLabelAttribute(): string
    {
        return match($this->best_posting_time) {
            self::TIME_MORNING => 'Morning (6AM - 9AM)',
            self::TIME_LUNCH => 'Lunch (12PM - 2PM)',
            self::TIME_EVENING => 'Evening (5PM - 8PM)',
            self::TIME_LATE_NIGHT => 'Late Night (9PM - 12AM)',
            default => $this->best_posting_time ?? 'N/A'
        };
    }

    /**
     * Get SEO score label
     */
    public function getScoreLabelAttribute(): string
    {
        if (!$this->seo_score) {
            return 'Not Analyzed';
        }

        return match(true) {
            $this->seo_score >= 90 => 'Excellent',
            $this->seo_score >= 75 => 'Good',
            $this->seo_score >= 50 => 'Average',
            $this->seo_score >= 25 => 'Poor',
            default => 'Needs Work'
        };
    }

    /**
     * Get score color class
     */
    public function getScoreColorAttribute(): string
    {
        if (!$this->seo_score) {
            return 'gray';
        }

        return match(true) {
            $this->seo_score >= 90 => 'green',
            $this->seo_score >= 75 => 'cyan',
            $this->seo_score >= 50 => 'yellow',
            $this->seo_score >= 25 => 'orange',
            default => 'red'
        };
    }

    /**
     * Get difficulty level
     */
    public function getDifficultyLabelAttribute(): string
    {
        $difficulty = $this->keyword_difficulty['score'] ?? null;

        if ($difficulty === null) {
            return 'N/A';
        }

        return match(true) {
            $difficulty <= 30 => 'Easy - Low Competition',
            $difficulty <= 60 => 'Medium - Moderate Competition',
            $difficulty <= 80 => 'Hard - High Competition',
            default => 'Very Hard - Very High Competition'
        };
    }

    /**
     * Apply SEO to project
     */
    public function applyToProject(): void
    {
        $this->update(['status' => self::STATUS_APPLIED]);

        if ($this->project) {
            $this->project->update(['status' => Project::STATUS_COMPLETED]);
        }
    }

    /**
     * Scope for completed optimizations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for applied optimizations
     */
    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }
}
