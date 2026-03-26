<?php

declare(strict_types=1);

return [
    'center_admin_summaries' => [
        'ai_content_disabled' => [
            'title' => 'AI content is disabled',
            'message' => 'AI tools are managed by platform admin for this center.',
        ],
        'guest_browsing_disabled' => [
            'title' => 'Guest browsing is disabled',
            'message' => 'Guest browsing is managed by platform policy for this center.',
        ],
        'pdf_downloads_disabled' => [
            'title' => 'PDF downloads are disabled',
            'message' => 'PDF download availability is managed by platform admin.',
        ],
        'provider_managed' => [
            'title' => 'AI provider managed by platform',
            'message' => ':provider is configured for your center. Provider availability and limits are managed by platform admin.',
        ],
    ],
    'ai' => [
        'providers' => [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'gemini' => 'Gemini',
        ],
        'fallback_provider' => 'AI provider',
    ],
    'validation' => [
        'unsupported_settings' => 'Unsupported settings: :keys',
        'unsupported_nested_settings' => 'Unsupported :setting settings: :keys',
        'unsupported_feature_flags' => 'Unsupported feature flags: :keys',
        'system_admin_only_feature_flags' => 'Only system admin can manage feature flags.',
        'unsupported_ai_providers' => 'Unsupported AI providers: :keys',
        'unsupported_ai_provider_settings' => 'Unsupported AI provider settings: :keys',
        'unsupported_ai_limit_keys' => 'Unsupported AI limits keys: :keys',
        'system_admin_only_ai_limits' => 'Only system admin can manage AI limits.',
        'settings_payload_required' => 'At least one of settings, features, or ai.providers is required.',
    ],
];
