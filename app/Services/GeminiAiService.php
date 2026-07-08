<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiAiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $model;
    
    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-pro');
    }
    
    /**
     * Generate content using Gemini AI
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $options['temperature'] ?? 0.7,
                        'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                        'topP' => $options['top_p'] ?? 0.95,
                        'topK' => $options['top_k'] ?? 40,
                    ],
                    'safetySettings' => $this->getSafetySettings()
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $processingTime = (microtime(true) - $startTime) * 1000;
                
                return [
                    'success' => true,
                    'content' => $this->extractContent($data),
                    'raw_response' => $data,
                    'processing_time_ms' => $processingTime,
                    'model' => $this->model
                ];
            }
            
            return $this->handleError($response);
            
        } catch (Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'API_EXCEPTION'
            ];
        }
    }
    
    /**
     * Generate title ideas based on keywords
     */
    public function generateTitleIdeas(string $keywords, int $count = 10, string $platform = 'youtube'): array
    {
        $prompt = $this->buildTitlePrompt($keywords, $count, $platform);
        return $this->generateContent($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 4096
        ]);
    }
    
    /**
     * Build title generation prompt
     */
    private function buildTitlePrompt(string $keywords, int $count, string $platform): string
    {
        $platformContext = match($platform) {
            'youtube' => 'YouTube video',
            'tiktok' => 'TikTok video',
            'instagram' => 'Instagram Reel',
            default => 'video content'
        };
        
        return <<<PROMPT
You are an expert {$platformContext} title writer with deep understanding of viral content patterns.

Generate {$count} highly engaging, click-worthy titles for content about: {$keywords}

Requirements:
1. Each title should be between 50-60 characters (optimal for SEO)
2. Use power words: Ultimate, Complete, Proven, Secret, Exclusive, etc.
3. Include numbers when appropriate (e.g., "5 Ways", "Top 10")
4. Create curiosity gaps
5. Promise specific value to viewers
6. Avoid clickbait but make them compelling

Return in JSON format:
{
    "titles": [
        {
            "title": "The Exact Title Here",
            "style": "educational|emotional|numeric|question|how-to",
            "viral_score_estimate": 85,
            "target_audience": "description of ideal viewer"
        }
    ],
    "primary_recommendation": "index of recommended title (0-{$count})",
    "reasoning": "why this title is recommended"
}

JSON output only, no additional text:
PROMPT;
    }
    
    /**
     * Generate text content prompt for scripts
     */
    public function generateTextPrompt(string $title, string $context = '', string $scriptType = 'tutorial'): array
    {
        $prompt = <<<PROMPT
Create a detailed text prompt that can be used to generate high-quality video script content.

Title: {$title}
Context: {$context}
Script Type: {$scriptType}

Generate a comprehensive prompt that includes:
1. Content outline and key points to cover
2. Tone and style guidelines
3. Target audience description
4. Key messages and takeaways
5. Hook and call-to-action suggestions
6. SEO keywords to incorporate

Return as structured JSON:
{
    "main_prompt": "detailed prompt for content generation",
    "content_structure": {
        "introduction": "what to include in intro",
        "main_body": ["key points array"],
        "conclusion": "what to include in outro"
    },
    "style_guidelines": "tone and style description",
    "seo_keywords": ["array", "of", "keywords"],
    "additional_notes": "any extra suggestions"
}

JSON output only:
PROMPT;
        
        return $this->generateContent($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 4096
        ]);
    }
    
    /**
     * Generate visual/thumbnail prompt
     */
    public function generateVisualPrompt(string $title, string $style = 'bold_text'): array
    {
        $prompt = <<<PROMPT
Create a detailed image generation prompt for a {$style} style YouTube thumbnail based on:

Title: {$title}

Requirements:
1. Include subject/main element description
2. Specify color palette (use vibrant, contrasting colors)
3. Include text overlay suggestions
4. Specify composition and layout
5. Add atmospheric details (lighting, background)
6. Make it eye-catching and click-worthy

Return as JSON:
{
    "image_prompt": "detailed Stable Diffusion/Midjourney style prompt",
    "style_modifiers": ["array of style keywords"],
    "color_palette": {
        "primary": "hex color",
        "secondary": "hex color",
        "accent": "hex color"
    },
    "composition": {
        "layout": "left_right|center|split",
        "focal_point": "description",
        "text_placement": "suggestion"
    },
    "mood": "emotional tone description",
    "technical_specs": {
        "resolution": "1280x720",
        "format": "16:9"
    }
}

JSON output only:
PROMPT;
        
        return $this->generateContent($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 2048
        ]);
    }
    
    /**
     * Generate storyboard/scene prompts
     */
    public function generateStoryboardPrompt(string $script, int $sceneCount = 6): array
    {
        $prompt = <<<PROMPT
Create a detailed storyboard/scene breakdown for video production.

Script/Content: {$script}

Generate {$sceneCount} distinct scenes with:
1. Scene title and number
2. Visual description (what to show)
3. Camera angle and movement
4. Audio/sound suggestions
5. Duration estimate
6. On-screen text or graphics

Return as JSON array:
{
    "scenes": [
        {
            "scene_number": 1,
            "title": "Scene Title",
            "visual_description": "detailed visual for this scene",
            "camera": {
                "angle": "wide|medium|close_up|etc",
                "movement": "static|panning|tracking|etc",
                "composition": "rule of thirds|centered|etc"
            },
            "audio": {
                "voiceover": "narration text",
                "background_music": "music mood/type",
                "sound_effects": ["array", "of", "sfx"]
            },
            "text_overlay": "any on-screen text",
            "duration_seconds": 15,
            "thumbnail_prompt": "image generation prompt for this scene"
        }
    ],
    "transitions": ["array of transition suggestions between scenes"],
    "total_estimated_duration": "X minutes Y seconds"
}

JSON output only:
PROMPT;
        
        return $this->generateContent($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 8192
        ]);
    }
    
    /**
     * Generate complete video script
     */
    public function generateVideoScript(string $title, string $context, string $scriptType = 'tutorial', string $tone = 'casual'): array
    {
        $scriptTypeGuide = $this->getScriptTypeGuide($scriptType);
        
        $prompt = <<<PROMPT
Write a complete, production-ready video script.

TITLE: {$title}
CONTEXT: {$context}
SCRIPT TYPE: {$scriptType}
TONE: {$tone}

{$scriptTypeGuide}

Requirements:
1. Include a powerful HOOK (first 3-5 seconds)
2. Structure with clear segments
3. Include B-Roll suggestions in brackets [B-ROLL: description]
4. Add on-screen text prompts {ON SCREEN: text}
5. Include pause points [...]
6. End with strong CTA

Return as JSON:
{
    "title": "finalized video title",
    "hook": "opening hook script",
    "segments": [
        {
            "title": "Segment Title",
            "start_time": "0:00",
            "script": "full narration text",
            "b_roll": ["array of B-roll suggestions"],
            "on_screen": ["array of text overlays"]
        }
    ],
    "conclusion": {
        "summary": "closing summary",
        "cta": "call to action text",
        "end_screen": "suggested end screen elements"
    },
    "technical": {
        "estimated_duration": "X minutes",
        "word_count": 1500,
        "reading_speed": "words per minute estimate"
    }
}

JSON output only:
PROMPT;
        
        return $this->generateContent($prompt, [
            'temperature' => 0.75,
            'max_tokens' => 8192
        ]);
    }
    
    /**
     * Generate SEO-optimized title and tags
     */
    public function generateSeoOptimization(string $originalTitle, string $content, string $platform = 'youtube'): array
    {
        $prompt = <<<PROMPT
Optimize the following content for maximum SEO performance on {$platform}.

Original Title: {$originalTitle}
Content/Script: {$content}

Generate:
1. SEO-optimized final title (max 100 characters)
2. Primary and secondary tags (15-20 tags)
3. Description (max 5000 characters for YouTube)
4. Posting recommendations
5. Competition analysis

Return as JSON:
{
    "final_title": "optimized title with keywords",
    "title_changes": {
        "original": "original title",
        "optimized": "optimized title",
        "changes_made": ["array of changes"]
    },
    "tags": {
        "primary": "main target keyword",
        "secondary": ["array of secondary keywords"],
        "all_tags": ["complete array of 15-20 tags"],
        "trending": ["optional trending tags"]
    },
    "description": {
        "full": "optimized description with keywords",
        "preview": "first 150 characters (for search results)"
    },
    "optimization": {
        "keyword_density": "percentage",
        "score": 85,
        "suggestions": ["array of improvement suggestions"]
    },
    "posting": {
        "best_time": "HH:MM timezone",
        "best_days": ["array of days"],
        "seasonal_considerations": "any relevant timing"
    }
}

JSON output only:
PROMPT;
        
        return $this->generateContent($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 4096
        ]);
    }
    
    /**
     * Extract content from Gemini response
     */
    private function extractContent(array $response): string
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return '';
        }
        
        return $response['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Get default safety settings
     */
    private function getSafetySettings(): array
    {
        return [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ];
    }
    
    /**
     * Handle API errors
     */
    private function handleError($response): array
    {
        $error = $response->json();
        
        return [
            'success' => false,
            'error' => $error['error']['message'] ?? 'Unknown API error',
            'error_code' => $error['error']['code'] ?? 'UNKNOWN_ERROR',
            'status' => $response->status()
        ];
    }
    
    /**
     * Get script type guide
     */
    private function getScriptTypeGuide(string $type): string
    {
        return match($type) {
            'tutorial' => 'Focus on step-by-step instructions. Clear numbered steps. Include tips and common mistakes to avoid.',
            'review' => 'Structure: Introduction → Quick Verdict → Detailed Analysis (pros/cons) → Comparison → Final Rating → CTA.',
            'vlog' => 'Casual, conversational tone. Personal anecdotes. Real-time narration style. Natural transitions.',
            'educational' => 'Deep dive into concepts. Use analogies. Include examples. Maintain authority but be accessible.',
            'entertainment' => 'Fast-paced. Humor and hooks. Cliffhangers. Energy throughout. Memorable moments.',
            'shorts' => 'Ultra-concise. Immediate hook. Single core message. Punchy delivery. 30-60 seconds.',
            default => 'Standard video structure with clear beginning, middle, and end.'
        };
    }
    
    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $response = $this->generateContent('Say "Connection successful" in exactly those words.', [
                'temperature' => 0.1,
                'max_tokens' => 50
            ]);
            
            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Gemini API connected successfully' : 'Gemini API connection failed',
                'model' => $this->model,
                'response' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Gemini API connection error: ' . $e->getMessage()
            ];
        }
    }
}
