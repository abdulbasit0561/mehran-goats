<?php

return [

    'api_key' => env('GEMINI_API_KEY'),

    'operations_model' => env('GEMINI_OPERATIONS_MODEL', 'gemini-flash-lite-latest'),

    'research_model' => env('GEMINI_RESEARCH_MODEL', 'gemini-2.5-flash-lite'),

    'timeout' => (int) env('GEMINI_TIMEOUT', 90),

];
