<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedPrompt extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'prompt_type',
        'input_keywords',
        'generated_prompt',
        'ai_response',
        'variations',
        'metadata',
        'status',
        'usage_count',
        'processing_time_ms'
    ];

    protected $casts = [
        'variations' => 'array',
        'metadata' => 'array',
        'processing_time_ms' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Prompt type constants
    const TYPE_TITLE_IDEA = 'title_idea';
    const TYPE_TEXT_CONTENT = 'text_content';
    const TYPE_VISUAL_THUMBNAIL = 'visual_thumbnail';
    const TYPE_VISUAL_STORYBOARD = 'visual_storyboard';
    const TYPE_SEO_OPTIMIZED_TITLE = 'seo_optimized_title';
    const TYPE_SEO_TAGS = 'seo_tags';
    const TYPE_DESCRIPTION = 'description';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the project this prompt belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Check if prompt is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->prompt_type) {
            self::TYPE_TITLE_IDEA => 'Title Idea',
            self::TYPE_TEXT_CONTENT => 'Text Content',
            self::TYPE_VISUAL_THUMBNAIL => 'Thumbnail Prompt',
            self::TYPE_VISUAL_STORYBOARD => 'Storyboard',
            self::TYPE_SEO_OPTIMIZED_TITLE => 'SEO Title',
            self::TYPE_SEO_TAGS => 'SEO Tags',
            self::TYPE_DESCRIPTION => 'Description',
            default => ucfirst(str_replace('_', ' ', $this->prompt_type))
        };
    }

    /**
     * Get first variation as title (for title prompts)
     */
    public function getFirstVariationAttribute(): ?string
    {
        if (empty($this->variations)) {
            return null;
        }

        if (is_array($this->variations)) {
            if (isset($this->variations[0]['title'])) {
                return $this->variations[0]['title'];
            }
            return $this->variations[0] ?? null;
        }

        return null;
    }

    /**
     * Scope for successful prompts
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for prompt type filter
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('prompt_type', $type);
    }
}
