<?php

return [

    'api_key' => env('AI_API_KEY'),

    'base_url' => env('AI_BASE_URL', 'https://llm.drytis.ai'),

    'model' => env('AI_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('AI_TIMEOUT', 60),

    'max_retries' => (int) env('AI_MAX_RETRIES', 2),

];
