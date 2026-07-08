<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoScript extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'title_prompt_id',
        'description_prompt_id',
        'title',
        'hook',
        'script_content',
        'description',
        'call_to_action',
        'script_type',
        'tone',
        'estimated_duration_seconds',
        'word_count',
        'seo_optimization',
        'metadata',
        'status'
    ];

    protected $casts = [
        'seo_optimization' => 'array',
        'metadata' => 'array',
        'estimated_duration_seconds' => 'integer',
        'word_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Script type constants
    const TYPE_TUTORIAL = 'tutorial';
    const TYPE_VLOG = 'vlog';
    const TYPE_REVIEW = 'review';
    const TYPE_EDUCATIONAL = 'educational';
    const TYPE_ENTERTAINMENT = 'entertainment';
    const TYPE_SHORTS = 'shorts';
    const TYPE_LIVESTREAM = 'livestream';

    // Tone constants
    const TONE_PROFESSIONAL = 'professional';
    const TONE_CASUAL = 'casual';
    const TONE_HUMOROUS = 'humorous';
    const TONE_SERIOUS = 'serious';
    const TONE_INSPIRATIONAL = 'inspirational';
    const TONE_TECHNICAL = 'technical';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_GENERATING = 'generating';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the project this script belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the title prompt used
     */
    public function titlePrompt(): BelongsTo
    {
        return $this->belongsTo(GeneratedPrompt::class, 'title_prompt_id');
    }

    /**
     * Get the description prompt used
     */
    public function descriptionPrompt(): BelongsTo
    {
        return $this->belongsTo(GeneratedPrompt::class, 'description_prompt_id');
    }

    /**
     * Get all scenes for this script
     */
    public function scenes(): HasMany
    {
        return $this->hasMany(ScriptScene::class, 'video_script_id')->orderBy('scene_number');
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->estimated_duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->estimated_duration_seconds / 60);
        $seconds = $this->estimated_duration_seconds % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get script type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->script_type) {
            self::TYPE_TUTORIAL => 'Tutorial',
            self::TYPE_VLOG => 'Vlog',
            self::TYPE_REVIEW => 'Review',
            self::TYPE_EDUCATIONAL => 'Educational',
            self::TYPE_ENTERTAINMENT => 'Entertainment',
            self::TYPE_SHORTS => 'Shorts',
            self::TYPE_LIVESTREAM => 'Livestream',
            default => ucfirst($this->script_type)
        };
    }

    /**
     * Get tone label
     */
    public function getToneLabelAttribute(): string
    {
        return match($this->tone) {
            self::TONE_PROFESSIONAL => 'Professional',
            self::TONE_CASUAL => 'Casual',
            self::TONE_HUMOROUS => 'Humorous',
            self::TONE_SERIOUS => 'Serious',
            self::TONE_INSPIRATIONAL => 'Inspirational',
            self::TONE_TECHNICAL => 'Technical',
            default => ucfirst($this->tone)
        };
    }

    /**
     * Get word count formatted
     */
    public function getWordCountFormattedAttribute(): string
    {
        return number_format($this->word_count) . ' words';
    }

    /**
     * Get estimated reading time
     */
    public function getReadingTimeAttribute(): string
    {
        if (!$this->word_count) {
            return 'N/A';
        }
        
        // Average reading speed: 150 words per minute
        $minutes = ceil($this->word_count / 150);
        
        return $minutes . ' min read';
    }

    /**
     * Get scene count
     */
    public function getSceneCountAttribute(): int
    {
        return $this->scenes()->count();
    }

    /**
     * Parse script into segments
     */
    public function getSegmentsAttribute(): array
    {
        $segments = [];
        preg_match_all('/📍\s*(.+?)\n(.+?)(?=\n📍|\n🎯|\Z)/s', $this->script_content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $segments[] = [
                'title' => trim($match[1]),
                'content' => trim($match[2])
            ];
        }
        
        return $segments;
    }

    /**
     * Scope for completed scripts
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for approved scripts
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
