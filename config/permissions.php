<?php

declare(strict_types=1);

/**
 * Permission definitions - Single source of truth.
 *
 * Add new permissions here and run: php artisan permissions:sync
 *
 * Format: 'permission.name' => 'Description'
 */
return [
    // Admin Management
    'admin.manage' => 'Manage admin users',
    'role.manage' => 'Manage roles and permissions',
    'permission.view' => 'View permission catalog',

    // Content Management
    'course.manage' => 'Manage courses',
    'course.publish' => 'Publish courses',
    'quiz.manage' => 'Manage quizzes and quiz questions',
    'assignment.manage' => 'Manage assignments and grading',
    'section.manage' => 'Manage sections',
    'video.manage' => 'Manage videos',
    'video.upload' => 'Authorize video uploads',
    'video.playback.override' => 'Override playback restrictions',
    'pdf.manage' => 'Manage PDFs',

    // Analytics
    'analytics.manage' => 'Manage analytics dashboards',
    'audit.view' => 'View analytics and audit logs',

    // Operations
    'enrollment.manage' => 'Manage enrollments',
    'center.manage' => 'Manage centers',
    'settings.manage' => 'Manage settings',
    'settings.view' => 'View settings',
    'student.manage' => 'Manage student accounts',
    'instructor.manage' => 'Manage instructors',
    'notification.manage' => 'Manage admin notifications',

    // Request Management
    'device_change.manage' => 'Manage device change requests',
    'extra_view.manage' => 'Manage extra view requests',
    'video_access.manage' => 'Manage video access approvals and codes',

    // Surveys
    'survey.manage' => 'Manage surveys',

    // AI & Agents
    'agent.execute' => 'Run automated agents',
    'agent.content_publishing' => 'Execute content publishing agents',
    'agent.enrollment.bulk' => 'Execute bulk enrollment agents',
    'ai_content.generate' => 'Generate AI learning content drafts',
    'ai_content.review_publish' => 'Review and publish AI generated content',
    'learning_asset.manage' => 'Manage generated learning assets',

    // Landing Pages
    'landing_page.manage' => 'Manage center landing pages',
];
