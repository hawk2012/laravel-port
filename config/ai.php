<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI-провайдеры
    |--------------------------------------------------------------------------
    |
    | default — драйвер, который используется по умолчанию. stub не требует
    | внешних ключей и генерирует валидные файлы локально; openai ходит
    | в любой OpenAI-совместимый шлюз (OpenAI, OpenRouter, YandexGPT-совместимый).
    |
    */

    'default' => env('AI_PROVIDER', 'stub'),

    'providers' => [

        'stub' => [
            'delay_ms' => (int) env('AI_STUB_DELAY_MS', 400),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
            'text_model' => env('OPENAI_TEXT_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Лимиты
    |--------------------------------------------------------------------------
    */

    'max_prompt_length' => (int) env('AI_MAX_PROMPT_LENGTH', 2000),

    'retry' => [
        'tries' => (int) env('AI_RETRY_TRIES', 3),
        'backoff' => [10, 60, 180],
    ],

];
