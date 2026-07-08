<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Gemini AI API integration.
    | Get your API key from: https://makersuite.google.com/app/apikey
    |
    */

    'gemini' => [
        /*
        |--------------------------------------------------------------------------
        | API Key
        |--------------------------------------------------------------------------
        |
        | Your Google AI API key. Keep this secret!
        | In production, use environment variables.
        |
        */
        'api_key' => env('GEMINI_API_KEY', ''),

        /*
        |--------------------------------------------------------------------------
        | Model Selection
        |--------------------------------------------------------------------------
        |
        | Choose the Gemini model to use:
        | - gemini-pro: Standard text generation
        | - gemini-pro-vision: For image understanding
        | - gemini-1.5-pro: Latest with larger context
        | - gemini-1.5-flash: Fast and efficient
        |
        */
        'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),

        /*
        |--------------------------------------------------------------------------
        | Generation Settings
        |--------------------------------------------------------------------------
        |
        | Default parameters for text generation.
        |
        */
        'temperature' => env('GEMINI_TEMPERATURE', 0.7),
        'max_tokens' => env('GEMINI_MAX_TOKENS', 8192),
        'top_p' => env('GEMINI_TOP_P', 0.95),
        'top_k' => env('GEMINI_TOP_K', 40),

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Rate limit settings for API calls.
        |
        */
        'rate_limit' => [
            'requests_per_minute' => env('GEMINI_RPM', 60),
            'requests_per_day' => env('GEMINI_RPD', 1500),
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache Settings
        |--------------------------------------------------------------------------
        |
        | Cache AI responses to reduce API calls and costs.
        |
        */
        'cache' => [
            'enabled' => env('GEMINI_CACHE_ENABLED', true),
            'ttl' => env('GEMINI_CACHE_TTL', 86400), // 24 hours in seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Generation Services
    |--------------------------------------------------------------------------
    |
    | Configuration for AI image generation services.
    |
    */

    'image_generation' => [
        /*
        |--------------------------------------------------------------------------
        | Primary Provider
        |--------------------------------------------------------------------------
        |
        | Choose the primary image generation service:
        | - stable_diffusion: Stable Diffusion via API
        | - dalle: OpenAI DALL-E
        | - midjourney: Via API (if available)
        | - gemini: Google Gemini (if vision is supported)
        |
        */
        'provider' => env('IMAGE_PROVIDER', 'gemini'),

        /*
        |--------------------------------------------------------------------------
        | Stable Diffusion Settings
        |--------------------------------------------------------------------------
        |
        | Configuration for Stable Diffusion API.
        |
        */
        'stable_diffusion' => [
            'api_url' => env('SD_API_URL', 'https://api.stability.ai/v1/generation'),
            'api_key' => env('STABILITY_API_KEY', ''),
            'engine' => env('SD_ENGINE', 'stable-diffusion-xl-1024-v1-0'),
            'default_steps' => 30,
            'default_cfg_scale' => 7.5,
        ],

        /*
        |--------------------------------------------------------------------------
        | DALL-E Settings
        |--------------------------------------------------------------------------
        |
        | Configuration for OpenAI DALL-E.
        |
        */
        'dalle' => [
            'api_key' => env('OPENAI_API_KEY', ''),
            'model' => env('DALLE_MODEL', 'dall-e-3'),
            'size' => env('DALLE_SIZE', '1024x1024'),
            'quality' => env('DALLE_QUALITY', 'standard'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Thumbnail Settings
        |--------------------------------------------------------------------------
        |
        | Default settings for thumbnail generation.
        |
        */
        'thumbnail' => [
            'default_width' => 1280,
            'default_height' => 720,
            'format' => 'png',
            'max_batch' => 5,
        ],
    ],
];
