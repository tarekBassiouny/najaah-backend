<?php

declare(strict_types=1);

namespace App\Support;

final class ErrorCodes
{
    public const NOT_FOUND = 'NOT_FOUND';

    public const CENTER_MISMATCH = 'CENTER_MISMATCH';

    public const UNAUTHORIZED = 'UNAUTHORIZED';

    public const FORBIDDEN = 'FORBIDDEN';

    public const UPLOAD_NOT_READY = 'UPLOAD_NOT_READY';

    public const UPLOAD_FAILED = 'UPLOAD_FAILED';

    public const VIDEO_NOT_READY = 'VIDEO_NOT_READY';

    public const PDF_NOT_READY = 'PDF_NOT_READY';

    public const ATTACHMENT_NOT_ALLOWED = 'ATTACHMENT_NOT_ALLOWED';

    public const COURSE_PUBLISH_BLOCKED = 'COURSE_PUBLISH_BLOCKED';

    public const CONCURRENT_DEVICE = 'CONCURRENT_DEVICE';

    public const ENROLLMENT_REQUIRED = 'ENROLLMENT_REQUIRED';

    public const SESSION_ENDED = 'SESSION_ENDED';

    public const SESSION_NOT_FOUND = 'SESSION_NOT_FOUND';

    public const NO_ACTIVE_DEVICE = 'NO_ACTIVE_DEVICE';

    public const VIEW_LIMIT_EXCEEDED = 'VIEW_LIMIT_EXCEEDED';

    public const DEVICE_MISMATCH = 'DEVICE_MISMATCH';

    public const INVALID_STATE = 'INVALID_STATE';

    public const INVALID_VIEWS = 'INVALID_VIEWS';

    public const VIDEO_NOT_IN_COURSE = 'VIDEO_NOT_IN_COURSE';

    public const PENDING_REQUEST_EXISTS = 'PENDING_REQUEST_EXISTS';

    public const VIEWS_AVAILABLE = 'VIEWS_AVAILABLE';

    public const ALREADY_ENROLLED = 'ALREADY_ENROLLED';

    public const NOT_ADMIN = 'NOT_ADMIN';

    public const NOT_STUDENT = 'NOT_STUDENT';

    private function __construct() {}
}
