<?php

declare(strict_types=1);

return [
    'center_admin_summaries' => [
        'ai_content_disabled' => [
            'title' => 'تم تعطيل محتوى الذكاء الاصطناعي',
            'message' => 'تتم إدارة أدوات الذكاء الاصطناعي لهذا المركز من قبل مدير المنصة.',
        ],
        'guest_browsing_disabled' => [
            'title' => 'تم تعطيل تصفح الضيوف',
            'message' => 'يتم التحكم في تصفح الضيوف لهذا المركز وفق سياسة المنصة.',
        ],
        'pdf_downloads_disabled' => [
            'title' => 'تم تعطيل تنزيلات PDF',
            'message' => 'يتم التحكم في إتاحة تنزيل ملفات PDF من قبل مدير المنصة.',
        ],
        'provider_managed' => [
            'title' => 'موفر الذكاء الاصطناعي مُدار من المنصة',
            'message' => 'تم تهيئة :provider لهذا المركز. تتم إدارة إتاحة الموفر وحدوده من قبل مدير المنصة.',
        ],
    ],
    'ai' => [
        'providers' => [
            'openai' => 'أوبن أيه آي',
            'anthropic' => 'أنثروبيك',
            'gemini' => 'جيميني',
        ],
        'fallback_provider' => 'موفر الذكاء الاصطناعي',
    ],
    'validation' => [
        'unsupported_settings' => 'إعدادات غير مدعومة: :keys',
        'unsupported_nested_settings' => 'إعدادات غير مدعومة ضمن :setting: :keys',
        'unsupported_feature_flags' => 'أعلام ميزات غير مدعومة: :keys',
        'system_admin_only_feature_flags' => 'يمكن لمدير النظام فقط إدارة أعلام الميزات.',
        'unsupported_ai_providers' => 'موفرو ذكاء اصطناعي غير مدعومين: :keys',
        'unsupported_ai_provider_settings' => 'إعدادات موفر الذكاء الاصطناعي غير مدعومة: :keys',
        'unsupported_ai_limit_keys' => 'مفاتيح حدود الذكاء الاصطناعي غير مدعومة: :keys',
        'system_admin_only_ai_limits' => 'يمكن لمدير النظام فقط إدارة حدود الذكاء الاصطناعي.',
        'settings_payload_required' => 'يجب إرسال واحد على الأقل من settings أو features أو ai.providers.',
    ],
];
