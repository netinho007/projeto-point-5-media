<?php

return [
    'url' => env('RESUME_AI_URL', 'http://127.0.0.1:11434'),
    'model' => env('RESUME_AI_MODEL', 'qwen3:4b'),
    'timeout' => (int) env('RESUME_AI_TIMEOUT', 480),
    'max_characters' => 24000,
];
