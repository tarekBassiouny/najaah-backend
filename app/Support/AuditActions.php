<?php

declare(strict_types=1);

namespace App\Support;

final class AuditActions
{
    public const ADMIN_USER_CREATED = 'admin_user_created';

    public const ADMIN_USER_DELETED = 'admin_user_deleted';

    public const ADMIN_USER_ROLES_SYNCED = 'admin_user_roles_synced';

    public const ADMIN_USER_UPDATED = 'admin_user_updated';

    public const CENTER_CREATED = 'center_created';

    public const CENTER_DELETED = 'center_deleted';

    public const CENTER_RESTORED = 'center_restored';

    public const CENTER_UPDATED = 'center_updated';

    public const CENTER_SETTINGS_CREATED = 'center_settings_created';

    public const CENTER_SETTINGS_UPDATED = 'center_settings_updated';

    public const COURSE_CREATED = 'course_created';

    public const COURSE_DELETED = 'course_deleted';

    public const COURSE_PUBLISHED = 'course_published';

    public const COURSE_CLONED = 'course_cloned';

    public const COURSE_UPDATED = 'course_updated';

    public const COURSE_PDF_ATTACHED = 'course_pdf_attached';

    public const COURSE_PDF_REMOVED = 'course_pdf_removed';

    public const COURSE_VIDEO_ATTACHED = 'course_video_attached';

    public const COURSE_VIDEO_REMOVED = 'course_video_removed';

    public const COURSE_INSTRUCTOR_ASSIGNED = 'course_instructor_assigned';

    public const COURSE_INSTRUCTOR_REMOVED = 'course_instructor_removed';

    public const COURSE_SECTION_ADDED = 'course_section_added';

    public const COURSE_SECTIONS_REORDERED = 'course_sections_reordered';

    public const DEVICE_CHANGE_REQUEST_APPROVED = 'device_change_request_approved';

    public const DEVICE_CHANGE_REQUEST_COMPLETED_VIA_LOGIN = 'device_change_request_completed_via_login';

    public const DEVICE_CHANGE_REQUEST_CREATED = 'device_change_request_created';

    public const DEVICE_CHANGE_REQUEST_CREATED_BY_ADMIN = 'device_change_request_created_by_admin';

    public const DEVICE_CHANGE_REQUEST_CREATED_VIA_OTP = 'device_change_request_created_via_otp';

    public const DEVICE_CHANGE_REQUEST_PRE_APPROVED = 'device_change_request_pre_approved';

    public const DEVICE_CHANGE_REQUEST_REJECTED = 'device_change_request_rejected';

    public const DEVICE_UUID_UPDATED = 'device_uuid_updated';

    public const ENROLLMENT_CREATED = 'enrollment_created';

    public const ENROLLMENT_DELETED = 'enrollment_deleted';

    public const ENROLLMENT_REQUEST_CREATED = 'enrollment_request_created';

    public const ENROLLMENT_STATUS_UPDATED = 'enrollment_status_updated';

    public const EXTRA_VIEW_REQUEST_APPROVED = 'extra_view_request_approved';

    public const EXTRA_VIEW_REQUEST_CREATED = 'extra_view_request_created';

    public const EXTRA_VIEW_REQUEST_REJECTED = 'extra_view_request_rejected';

    public const PDF_CREATED = 'pdf_created';

    public const PDF_DELETED = 'pdf_deleted';

    public const PDF_UPDATED = 'pdf_updated';

    public const PDF_UPLOAD_SESSION_CREATED = 'pdf_upload_session_created';

    public const PDF_UPLOAD_SESSION_FAILED = 'pdf_upload_session_failed';

    public const PDF_UPLOAD_SESSION_FINALIZED = 'pdf_upload_session_finalized';

    public const ROLE_CREATED = 'role_created';

    public const ROLE_DELETED = 'role_deleted';

    public const ROLE_PERMISSIONS_SYNCED = 'role_permissions_synced';

    public const ROLE_UPDATED = 'role_updated';

    public const SECTION_CREATED = 'section_created';

    public const SECTION_DELETED = 'section_deleted';

    public const SECTION_PDF_ATTACHED = 'section_pdf_attached';

    public const SECTION_PDF_DETACHED = 'section_pdf_detached';

    public const SECTION_REORDERED = 'section_reordered';

    public const SECTION_RESTORED = 'section_restored';

    public const SECTION_UPDATED = 'section_updated';

    public const SECTION_VIDEO_ATTACHED = 'section_video_attached';

    public const SECTION_VIDEO_DETACHED = 'section_video_detached';

    public const SECTION_VISIBILITY_TOGGLED = 'section_visibility_toggled';

    public const VIDEO_CREATED = 'video_created';

    public const VIDEO_DELETED = 'video_deleted';

    public const VIDEO_UPDATED = 'video_updated';

    public const VIDEO_UPLOAD_SESSION_CREATED = 'video_upload_session_created';

    public const VIDEO_UPLOAD_SESSION_TRANSITIONED = 'video_upload_session_transitioned';
}
