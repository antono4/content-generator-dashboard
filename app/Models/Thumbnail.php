<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Thumbnail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'prompt_id',
        'title',
        'image_url',
        'local_path',
        'ai_provider',
        'style',
        'status',
        'generation_params',
        'ai_metadata',
        'width',
        'height',
        'file_format',
        'file_size',
        'is_selected',
        'version'
    ];

    protected $casts = [
        'generation_params' => 'array',
        'ai_metadata' => 'array',
        'is_selected' => 'boolean',
        'version' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Style constants
    const STYLE_MINIMALIST = 'minimalist';
    const STYLE_BOLD_TEXT = 'bold_text';
    const STYLE_CHARACTER_FOCUSED = 'character_focused';
    const STYLE_TEXT_OVERLAY = 'text_overlay';
    const STYLE_CONCEPT_ART = 'concept_art';
    const STYLE_PHOTOGRAPHIC = 'photographic';

    // Status constants
    const STATUS_GENERATING = 'generating';
    const STATUS_READY = 'ready';
    const STATUS_DOWNLOADING = 'downloading';
    const STATUS_DOWNLOADED = 'downloaded';
    const STATUS_FAILED = 'failed';

    /**
     * Get the project this thumbnail belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the prompt used to generate this thumbnail
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(GeneratedPrompt::class, 'prompt_id');
    }

    /**
     * Get scenes using this thumbnail
     */
    public function scenes()
    {
        return $this->hasMany(ScriptScene::class, 'thumbnail_id');
    }

    /**
     * Select this as the primary thumbnail
     */
    public function selectAsPrimary(): void
    {
        // Deselect all other thumbnails for this project
        self::where('project_id', $this->project_id)
            ->where('id', '!=', $this->id)
            ->update(['is_selected' => false]);

        $this->update(['is_selected' => true]);
    }

    /**
     * Get aspect ratio
     */
    public function getAspectRatioAttribute(): string
    {
        if ($this->width && $this->height) {
            $gcd = $this->gcd($this->width, $this->height);
            return $this->width / $gcd . ':' . $this->height / $gcd;
        }
        return '16:9';
    }

    /**
     * Calculate GCD for aspect ratio
     */
    private function gcd(int $a, int $b): int
    {
        return $b ? $this->gcd($b, $a % $b) : $a;
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $this->file_size;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Get style label
     */
    public function getStyleLabelAttribute(): string
    {
        return match($this->style) {
            self::STYLE_MINIMALIST => 'Minimalist',
            self::STYLE_BOLD_TEXT => 'Bold Text',
            self::STYLE_CHARACTER_FOCUSED => 'Character Focused',
            self::STYLE_TEXT_OVERLAY => 'Text Overlay',
            self::STYLE_CONCEPT_ART => 'Concept Art',
            self::STYLE_PHOTOGRAPHIC => 'Photographic',
            default => ucfirst(str_replace('_', ' ', $this->style))
        };
    }

    /**
     * Scope for selected thumbnails
     */
    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    /**
     * Scope for ready thumbnails
     */
    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }
}
