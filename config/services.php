<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // OpenAI-compatible chat completions endpoint. Credentials come from env
    // only and must never be logged or echoed. Tests fake the HTTP layer.
    'ai' => [
        'enabled' => (bool) env('AI_API_KEY'),
        'api_key' => env('AI_API_KEY'),
        'base_url' => rtrim(env('AI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('AI_TIMEOUT', 120),
        'max_attempts' => (int) env('AI_MAX_ATTEMPTS', 3),
    ],

    // Ollama LLM service configuration for article generation
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', env('AI_BASE_URL', 'http://localhost:11434')),
        'model' => env('OLLAMA_MODEL', env('AI_MODEL', 'llama3:8b')),
        'key' => env('OLLAMA_API_KEY', env('AI_API_KEY')),
        'timeout' => (int) env('OLLAMA_TIMEOUT', env('AI_TIMEOUT', 120)),
        'max_attempts' => (int) env('OLLAMA_MAX_ATTEMPTS', 3),
    ],

];
