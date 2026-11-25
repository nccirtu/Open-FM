<?php

return [

    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),

    'default_per_page' => 25,

    'max_per_page' => 100,

    'token_max_days' => (int) env('API_TOKEN_MAX_DAYS', 365),

    'docs_memory_limit' => env('API_DOCS_MEMORY_LIMIT', '512M'),

];
