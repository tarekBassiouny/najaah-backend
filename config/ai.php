<?php

declare(strict_types=1);

$csvToList = static function (string $key, string $default): array {
    $value = (string) env($key, $default);

    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value)
    ), static fn (string $item): bool => $item !== ''));
};

return [
    'providers' => [
        'openai' => [
            'label' => 'OpenAI',
            'default_model' => env('OPENAI_DEFAULT_MODEL', env('AI_MODEL', 'gpt-4o-mini')),
            'models' => $csvToList('OPENAI_MODELS', 'gpt-4o-mini,gpt-4.1-mini'),
            'api_key_env' => 'OPENAI_API_KEY',
        ],
        'anthropic' => [
            'label' => 'Anthropic',
            'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-sonnet-20241022'),
            'models' => $csvToList('ANTHROPIC_MODELS', 'claude-3-5-sonnet-20241022,claude-3-5-haiku-20241022'),
            'api_key_env' => 'ANTHROPIC_API_KEY',
        ],
        'gemini' => [
            'label' => 'Gemini',
            'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-1.5-flash'),
            'models' => $csvToList('GEMINI_MODELS', 'gemini-1.5-flash,gemini-1.5-pro'),
            'api_key_env' => 'GEMINI_API_KEY',
        ],
    ],
    'limits' => [
        'daily_job_limit' => (int) env('AI_LIMIT_DAILY_JOB', 200),
        'monthly_job_limit' => (int) env('AI_LIMIT_MONTHLY_JOB', 3000),
        'daily_token_limit' => (int) env('AI_LIMIT_DAILY_TOKEN', 1200000),
        'monthly_token_limit' => (int) env('AI_LIMIT_MONTHLY_TOKEN', 30000000),
        'max_input_chars' => (int) env('AI_LIMIT_MAX_INPUT_CHARS', 60000),
        'max_output_chars' => (int) env('AI_LIMIT_MAX_OUTPUT_CHARS', 30000),
        'max_concurrent_jobs' => (int) env('AI_LIMIT_MAX_CONCURRENT_JOBS', 10),
        'default_output_token_estimate' => (int) env('AI_DEFAULT_OUTPUT_TOKEN_ESTIMATE', 1200),
    ],
];
