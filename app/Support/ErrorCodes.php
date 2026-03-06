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

    public const SESSION_EXPIRED = 'SESSION_EXPIRED';

    public const SESSION_NOT_FOUND = 'SESSION_NOT_FOUND';

    public const NO_ACTIVE_DEVICE = 'NO_ACTIVE_DEVICE';

    public const VIEW_LIMIT_EXCEEDED = 'VIEW_LIMIT_EXCEEDED';

    public const DEVICE_MISMATCH = 'DEVICE_MISMATCH';

    public const DEVICE_REVOKED = 'DEVICE_REVOKED';

    public const INVALID_STATE = 'INVALID_STATE';

    public const INVALID_VIEWS = 'INVALID_VIEWS';

    public const VIDEO_NOT_IN_COURSE = 'VIDEO_NOT_IN_COURSE';

    public const PENDING_REQUEST_EXISTS = 'PENDING_REQUEST_EXISTS';

    public const VIEWS_AVAILABLE = 'VIEWS_AVAILABLE';

    public const VIDEO_ACCESS_DENIED = 'VIDEO_ACCESS_DENIED';

    public const VIDEO_ACCESS_REQUEST_EXISTS = 'VIDEO_ACCESS_REQUEST_EXISTS';

    public const VIDEO_ACCESS_ALREADY_GRANTED = 'VIDEO_ACCESS_ALREADY_GRANTED';

    public const VIDEO_CODE_INVALID = 'VIDEO_CODE_INVALID';

    public const VIDEO_CODE_EXPIRED = 'VIDEO_CODE_EXPIRED';

    public const VIDEO_CODE_USED = 'VIDEO_CODE_USED';

    public const VIDEO_CODE_REVOKED = 'VIDEO_CODE_REVOKED';

    public const VIDEO_CODE_WRONG_USER = 'VIDEO_CODE_WRONG_USER';

    public const VIDEO_CODE_ACTIVE_EXISTS = 'VIDEO_CODE_ACTIVE_EXISTS';

    public const STUDENT_NO_PHONE = 'STUDENT_NO_PHONE';

    public const WHATSAPP_SEND_FAILED = 'WHATSAPP_SEND_FAILED';

    public const ALREADY_ENROLLED = 'ALREADY_ENROLLED';

    public const NOT_ADMIN = 'NOT_ADMIN';

    public const NOT_STUDENT = 'NOT_STUDENT';

    public const OTP_INVALID = 'OTP_INVALID';

    public const USER_NOT_FOUND_FOR_OTP = 'USER_NOT_FOUND_FOR_OTP';

    private function __construct() {}
}
