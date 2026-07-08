<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScriptScene extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'video_script_id',
        'scene_number',
        'scene_title',
        'narration',
        'visual_direction',
        'shot_type',
        'camera_movement',
        'lighting',
        'location',
        'duration_seconds',
        'thumbnail_url',
        'thumbnail_id',
        'visual_prompt',
        'transition',
        'notes',
        'is_generated'
    ];

    protected $casts = [
        'visual_prompt' => 'array',
        'transition' => 'array',
        'scene_number' => 'integer',
        'duration_seconds' => 'integer',
        'is_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Shot type constants
    const SHOT_WIDE = 'wide_shot';
    const SHOT_MEDIUM = 'medium_shot';
    const SHOT_CLOSE_UP = 'close_up';
    const SHOT_EXTREME_CLOSE_UP = 'extreme_close_up';
    const SHOT_POV = 'pov';
    const SHOT_OVER_SHOULDER = 'over_shoulder';
    const SHOT_AERIAL = 'aerial';
    const SHOT_TRACKING = 'tracking';
    const SHOT_STATIC = 'static';

    /**
     * Get the video script this scene belongs to
     */
    public function videoScript(): BelongsTo
    {
        return $this->belongsTo(VideoScript::class, 'video_script_id');
    }

    /**
     * Get the thumbnail for this scene
     */
    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Thumbnail::class, 'thumbnail_id');
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '0s';
        }

        if ($this->duration_seconds >= 60) {
            $minutes = floor($this->duration_seconds / 60);
            $seconds = $this->duration_seconds % 60;
            return $minutes . 'm ' . $seconds . 's';
        }

        return $this->duration_seconds . 's';
    }

    /**
     * Get shot type label
     */
    public function getShotTypeLabelAttribute(): string
    {
        return match($this->shot_type) {
            self::SHOT_WIDE => 'Wide Shot',
            self::SHOT_MEDIUM => 'Medium Shot',
            self::SHOT_CLOSE_UP => 'Close Up',
            self::SHOT_EXTREME_CLOSE_UP => 'Extreme Close Up',
            self::SHOT_POV => 'POV',
            self::SHOT_OVER_SHOULDER => 'Over Shoulder',
            self::SHOT_AERIAL => 'Aerial',
            self::SHOT_TRACKING => 'Tracking',
            self::SHOT_STATIC => 'Static',
            default => ucfirst(str_replace('_', ' ', $this->shot_type))
        };
    }

    /**
     * Get full visual direction with camera info
     */
    public function getFullVisualDirectionAttribute(): string
    {
        $parts = [];

        if ($this->visual_direction) {
            $parts[] = $this->visual_direction;
        }

        if ($this->shot_type) {
            $parts[] = "Shot: " . $this->shot_type_label;
        }

        if ($this->camera_movement) {
            $parts[] = "Camera: " . $this->camera_movement;
        }

        if ($this->lighting) {
            $parts[] = "Lighting: " . $this->lighting;
        }

        if ($this->location) {
            $parts[] = "Location: " . $this->location;
        }

        return implode(' | ', $parts);
    }

    /**
     * Get timestamp for this scene (cumulative)
     */
    public function getTimestampAttribute(): string
    {
        $previousScenes = self::where('video_script_id', $this->video_script_id)
            ->where('scene_number', '<', $this->scene_number)
            ->sum('duration_seconds');

        $minutes = floor($previousScenes / 60);
        $seconds = $previousScenes % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
