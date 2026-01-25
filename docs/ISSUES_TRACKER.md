# XYZ LMS - Issues Tracker

This document tracks identified issues for refactoring and improvement.

**Created:** 2026-01-17
**Last Updated:** 2026-01-25

---

## EXECUTIVE SUMMARY

| Severity | Count | Categories |
|----------|-------|------------|
| **P0 Critical** | 15+ | Security, Bugs, Authorization |
| **P1 High** | 30+ | Race Conditions, Missing Tests, Validation |
| **P2 Medium** | 100+ | Interfaces, Scopes, Constants, Resources |
| **P3 Low** | 40+ | Inconsistencies, Performance, Naming |

**Total Issues Identified: 185+**

---

# PART 1: SECURITY & CRITICAL BUGS

## PHASE 1: CRITICAL SECURITY (P0)

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| SEC-001 | Hardcoded OTP (123456) - same OTP every time | `app/Services/Auth/OtpService.php` | 22 | HOLD (dev testing) |
| SEC-002 | No rate limiting on OTP send endpoint | `routes/api/v1/mobile.php` | - | DONE |
| SEC-003 | No rate limiting on OTP verify endpoint | `routes/api/v1/mobile.php` | - | DONE |
| SEC-004 | No rate limiting on admin login endpoint | `routes/api/v1/admin/auth.php` | - | DONE |
| SEC-005 | **ALL 76 Form Requests return `true` from authorize()** - No authorization checks | `app/Http/Requests/**/*.php` | - | TODO |

---

## PHASE 2: CRITICAL BUGS (P0)

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| BUG-001 | `ENROLLMENT_STATUS_PENDING = 3` is INVALID - Enrollment only has 0,1,2 | `app/Services/Requests/RequestService.php` | 23 | DONE |
| BUG-002 | OTP send errors silently swallowed - no logging | `app/Services/Auth/OtpService.php` | 42-45 | HOLD (WhatsApp not ready) |
| BUG-003 | DeviceChangeRequest sets `new_device_id` = `current_device_id` (same value) | `app/Services/Requests/RequestService.php` | 149-152 | DONE |
| BUG-004 | AdminAuthController returns raw array instead of Resource (CODEX violation) | `app/Http/Controllers/Admin/Auth/AdminAuthController.php` | 120 | DONE |

---

## PHASE 3: SECURITY ISSUES (P1)

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| SEC-006 | JWT access token stored as plain text | `app/Services/Auth/JwtService.php` | 23 | TODO |
| SEC-007 | Wrong HTTP status code (403 instead of 401) | `app/Http/Middleware/JwtMobileMiddleware.php` | 119 | TODO |
| SEC-008 | Missing refresh token rotation on refresh | `app/Services/Auth/JwtService.php` | 42-71 | TODO |
| SEC-009 | Missing try/catch in admin JWT middleware | `app/Http/Middleware/JwtAdminMiddleware.php` | 19 | TODO |
| SEC-010 | Inconsistent error response format in admin middleware | `app/Http/Middleware/JwtAdminMiddleware.php` | 22 | TODO |
| SEC-011 | CenterController missing center scope validation - privilege escalation | `app/Http/Controllers/Admin/Centers/CenterController.php` | - | TODO |

---

## PHASE 4: RACE CONDITIONS (P1)

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| RACE-001 | Enrollment request creation NOT in transaction | `app/Services/Requests/RequestService.php` | 104-120 | DONE |
| RACE-002 | Extra view request creation NOT in transaction | `app/Services/Requests/RequestService.php` | 55-83 | DONE |
| RACE-003 | Device change request creation NOT in transaction | `app/Services/Requests/RequestService.php` | 137-155 | DONE |
| RACE-004 | Playback session creation lacks pessimistic locking | `app/Services/Playback/PlaybackService.php` | 33-66 | TODO |
| RACE-005 | ExtraViewRequestService approve/reject NOT in transaction | `app/Services/Playback/ExtraViewRequestService.php` | 48-98 | TODO |

---

# PART 2: DATABASE & MIGRATIONS

## PHASE 5: MISSING FOREIGN KEY INDEXES (23+ migrations)

**NOTE:** All tables using `foreignId()->constrained()` automatically get indexes in MySQL/MariaDB.

| ID | Table | Column | File | Status |
|----|-------|--------|------|--------|
| IDX-001 | users | center_id | `2025_11_29_054156_create_users_table.php` | N/A (uses constrained) |
| IDX-002 | courses | center_id, category_id, created_by | `2025_11_29_061207_create_courses_table.php` | TODO |
| IDX-003 | sections | course_id | `2025_11_29_061335_create_sections_table.php` | TODO |
| IDX-004 | videos | created_by | `2025_11_29_061522_create_videos_table.php` | TODO |
| IDX-005 | pdfs | created_by | `2025_11_29_061634_create_pdfs_table.php` | TODO |
| IDX-006 | course_video | course_id, video_id, section_id | `2025_11_29_061723_create_course_video_table.php` | TODO |
| IDX-007 | course_pdf | course_id, pdf_id, section_id, video_id | `2025_11_29_061838_create_course_pdf_table.php` | TODO |
| IDX-008 | enrollments | user_id, course_id, center_id | `2025_11_29_062100_create_enrollments_table.php` | TODO |
| IDX-009 | playback_sessions | user_id, video_id, device_id | `2025_11_29_063518_create_playback_sessions_table.php` | TODO |
| IDX-010 | center_settings | center_id | `2025_11_29_064135_create_center_settings_table.php` | TODO |
| IDX-011 | course_settings | course_id | `2025_11_29_064136_create_course_settings_table.php` | TODO |
| IDX-012 | video_settings | video_id | `2025_11_29_064137_create_video_settings_table.php` | TODO |
| IDX-013 | student_settings | user_id | `2025_11_29_064138_create_student_settings_table.php` | TODO |
| IDX-014 | role_user | role_id, user_id | `2025_11_29_221537_create_role_user_table.php` | TODO |
| IDX-015 | instructors | center_id, created_by | `2025_12_05_000001_create_instructors_table.php` | TODO |
| IDX-016 | course_instructors | course_id, instructor_id | `2025_12_05_000002_create_course_instructors_table.php` | TODO |
| IDX-017 | courses | primary_instructor_id | `2025_12_05_000003_update_courses_table_for_instructors.php` | TODO |
| IDX-018 | role_permission | role_id, permission_id | `2025_12_23_000002_create_role_permission_table.php` | TODO |

---

## PHASE 6: MISSING SOFT DELETES

| ID | Table | File | Status |
|----|-------|------|--------|
| SD-001 | personal_access_tokens | `2025_11_29_145422_create_personal_access_tokens_table.php` | TODO |
| SD-002 | permissions | `2025_12_23_000001_create_permissions_table.php` | TODO |
| SD-003 | bunny_webhook_logs | `2025_12_15_000003_create_bunny_webhook_logs_table.php` | TODO |
| SD-004 | translations | `2026_01_01_000006_create_translations_table.php` | TODO |
| SD-005 | password_reset_tokens | `2025_12_22_120500_create_password_reset_tokens_table.php` | TODO |
| SD-006 | role_permission (inconsistent with role_user) | `2025_12_23_000002_create_role_permission_table.php` | TODO |

---

## PHASE 7: INCONSISTENT STATUS DATA TYPES

| ID | Issue | Table | File | Status |
|----|-------|-------|------|--------|
| DT-001 | status is STRING, should be TINYINT | extra_view_requests | `2025_12_20_000000_create_extra_view_requests_table.php` | TODO |
| DT-002 | status is STRING, should be TINYINT | device_change_requests | `2025_12_21_000000_create_device_change_requests_table.php` | TODO |

---

## PHASE 8: MISSING CASCADE RULES

| ID | Table | FK | Missing | File | Status |
|----|-------|----|---------| ------|--------|
| FK-001 | extra_view_requests | user_id, video_id, course_id, center_id | cascadeOnUpdate | `2025_12_20_000000_create_extra_view_requests_table.php` | TODO |
| FK-002 | device_change_requests | user_id, center_id | cascadeOnUpdate | `2025_12_21_000000_create_device_change_requests_table.php` | TODO |
| FK-003 | sessions | user_id | cascade rules | `2025_12_02_004846_create_sessions_table.php` | TODO |

---

# PART 3: SERVICES & CODE STRUCTURE

## PHASE 9: MISSING INTERFACES (37 services violate CODEX_DOMAIN_RULES.md)

| ID | Service | Status |
|----|---------|--------|
| INT-001 | `app/Services/Admin/DeviceChangeRequestQueryService.php` | TODO |
| INT-002 | `app/Services/Admin/StudentQueryService.php` | TODO |
| INT-003 | `app/Services/Admin/InstructorQueryService.php` | TODO |
| INT-004 | `app/Services/Admin/ExtraViewRequestQueryService.php` | TODO |
| INT-005 | `app/Services/Bunny/BunnyLibraryService.php` | TODO |
| INT-006 | `app/Services/Bunny/BunnyStreamService.php` | TODO |
| INT-007 | `app/Services/Bunny/BunnyEmbedTokenService.php` | TODO |
| INT-008 | `app/Services/Bunny/BunnyWebhookService.php` | TODO |
| INT-009 | `app/Services/Playback/ViewLimitService.php` | TODO |
| INT-010 | `app/Services/Playback/PlaybackAuthorizationService.php` | TODO |
| INT-011 | `app/Services/Playback/ExtraViewRequestService.php` | TODO |
| INT-012 | `app/Services/Playback/PlaybackService.php` | TODO |
| INT-013 | `app/Services/Devices/DeviceChangeService.php` | TODO |
| INT-014 | `app/Services/Settings/AdminSettingsPreviewService.php` | TODO |
| INT-015 | `app/Services/Branding/CenterLogoUrlResolver.php` | TODO |
| INT-016 | `app/Services/Centers/CenterScopeService.php` | TODO |
| INT-017 | `app/Services/Roles/RoleService.php` | TODO |
| INT-018 | `app/Services/Permissions/PermissionService.php` | TODO |
| INT-019 | `app/Services/Storage/StoragePathResolver.php` | TODO |
| INT-020 | `app/Services/Requests/RequestService.php` | TODO |
| INT-021 | `app/Services/Instructors/MobileInstructorService.php` | TODO |
| INT-022 | `app/Services/Students/StudentService.php` | TODO |
| INT-023 | `app/Services/Courses/CourseQueryService.php` | TODO |
| INT-024 | `app/Services/Courses/ExploreCourseService.php` | TODO |
| INT-025 | `app/Services/Videos/VideoUploadSessionQueryService.php` | TODO |
| INT-026 | `app/Services/Videos/AdminVideoQueryService.php` | TODO |
| INT-027 | `app/Services/Videos/VideoService.php` | TODO |
| INT-028 | `app/Services/Videos/VideoPublishingService.php` | TODO |
| INT-029 | `app/Services/Videos/VideoUploadService.php` | TODO |
| INT-030 | `app/Services/AdminUsers/AdminUserService.php` | TODO |
| INT-031 | `app/Services/Audit/AuditLogQueryService.php` | TODO |
| INT-032 | `app/Services/Pdfs/AdminPdfQueryService.php` | TODO |
| INT-033 | `app/Services/Pdfs/PdfUploadSessionService.php` | TODO |
| INT-034 | `app/Services/Pdfs/PdfAccessService.php` | TODO |
| INT-035 | `app/Services/Pdfs/PdfService.php` | TODO |
| INT-036 | `app/Services/Categories/MobileCategoryService.php` | TODO |
| INT-037 | `app/Services/Logging/LogContextResolver.php` | TODO |

---

## PHASE 10: MISSING MODEL SCOPES (Duplicated Queries)

| ID | Scope Needed | Model | Used In | Status |
|----|--------------|-------|---------|--------|
| SCOPE-001 | `readyForPlayback()` - encoding_status=3, lifecycle_status=2 | Video | PlaybackAuthorizationService, ExploreCourseService, SectionStructureService | DONE |
| SCOPE-002 | `published()` - status=3, is_published=true | Course | PlaybackAuthorizationService, ExploreCourseService, CourseWorkflowService | DONE |
| SCOPE-003 | `activeForUserAndCourse(User, Course)` | Enrollment | PlaybackAuthorizationService, ExtraViewRequestService, EnrollmentService, RequestService | DONE |
| SCOPE-004 | `active()`, `activeForUser(User)` | UserDevice | DeviceChangeService, PlaybackAuthorizationService, RequestService | DONE |
| SCOPE-005 | `pending()` | ExtraViewRequest | ExtraViewRequestService, RequestService | DONE |
| SCOPE-006 | `pending()` | DeviceChangeRequest | DeviceChangeService, RequestService | DONE |
| SCOPE-007 | `fullPlaysForUserAndVideo(User, Video)` | PlaybackSession | ViewLimitService | DONE |
| SCOPE-008 | `activeVideos()` - pivot deleted_at IS NULL | Course | PlaybackAuthorizationService, ExtraViewRequestService, RequestService | N/A (videos() already filters) |
| SCOPE-009 | `forUser(User)`, `active()`, `expired()` | PlaybackSession | PlaybackService | DONE |

---

## PHASE 11: MAGIC NUMBERS (Missing Constants)

| ID | Value | Meaning | Model | Locations | Status |
|----|-------|---------|-------|-----------|--------|
| CONST-001 | `status = 3` | Published | Course | CourseService, ExploreCourseService, CenterService | DONE |
| CONST-002 | `encoding_status = 3` | Encoding complete | Video | PlaybackAuthorizationService, ExploreCourseService | N/A (uses VideoUploadStatus enum) |
| CONST-003 | `lifecycle_status = 2` | Ready for playback | Video | PlaybackAuthorizationService, ExploreCourseService | DONE |
| CONST-004 | `upload_status = 3` | Upload complete | VideoUploadSession | Multiple services | N/A (uses VideoUploadStatus enum) |
| CONST-005 | `type = 0` | Unbranded center | Center | PlaybackAuthorizationService, JwtMobileMiddleware | DONE |
| CONST-006 | `type = 1` | Branded center | Center | CenterResource | DONE |

---

## PHASE 12: DUPLICATED AUTHORIZATION LOGIC

| ID | Pattern | Locations | Status |
|----|---------|-----------|--------|
| AUTH-001 | `assertStudent()` - identical implementation | RequestService:158, PlaybackAuthorizationService:107, ExtraViewRequestService:100, StudentService:78, EnrollmentService:174, DeviceChangeService:142 | TODO |
| AUTH-002 | `assertAdminScope()` | DeviceChangeService:149, ExtraViewRequestService:144 | TODO |
| AUTH-003 | Center scope filtering for super_admin | StudentQueryService:42, InstructorQueryService:42, ExtraViewRequestQueryService:46, DeviceChangeRequestQueryService:46 | TODO |
| AUTH-004 | `instanceof User` check in admin controllers | DeviceChangeRequestController (3x), ExtraViewRequestController (3x), StudentController (3x) | TODO |

---

## PHASE 13: CODE STANDARDS VIOLATIONS

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| STD-001 | Uses `app()` helper instead of DI | `app/Services/Devices/DeviceService.php` | 108 | TODO |
| STD-002 | Uses `app()` helper instead of DI | `app/Services/Bunny/BunnyLibraryService.php` | 84 | TODO |
| STD-003 | Uses `app()` helper instead of DI | `app/Services/Bunny/BunnyStreamService.php` | 163 | TODO |
| STD-004 | Business logic in User model (authorization methods) | `app/Models/User.php` | 106-151 | TODO |
| STD-005 | Business logic in Enrollment model (status labels) | `app/Models/Enrollment.php` | 53-65 | TODO |
| STD-006 | Business logic in OtpCode model (default values) | `app/Models/OtpCode.php` | 83-94 | TODO |
| STD-007 | `request()->user()` instead of `$request->user()` | `app/Http/Controllers/Admin/StudentController.php` | 96 | TODO |

---

# PART 4: API RESOURCES

## PHASE 14: MISSING @property PHPDoc (ALL 40 Resources)

**ALL resources missing @property annotations per CODEX rules:**

| ID | Resource | Status |
|----|----------|--------|
| RES-001 | `app/Http/Resources/Mobile/DeviceResource.php` | TODO |
| RES-002 | `app/Http/Resources/Mobile/StudentUserResource.php` | TODO |
| RES-003 | `app/Http/Resources/Mobile/TokenResource.php` | TODO |
| RES-004 | `app/Http/Resources/Mobile/CenterResource.php` | TODO |
| RES-005 | `app/Http/Resources/Mobile/CourseDetailsResource.php` | TODO |
| RES-006 | `app/Http/Resources/Mobile/ExploreCourseResource.php` | TODO |
| RES-007 | `app/Http/Resources/Admin/Centers/CenterResource.php` | TODO |
| RES-008 | `app/Http/Resources/Admin/Roles/RoleResource.php` | TODO |
| RES-009 | `app/Http/Resources/Admin/Users/AdminUserResource.php` | TODO |
| RES-010 | `app/Http/Resources/Admin/Courses/CourseResource.php` | TODO |
| RES-011 | (+ 30 more resources) | TODO |

---

## PHASE 15: RESOURCE N+1 QUERY RISKS

| ID | Resource | Relationship | Line | Status |
|----|----------|--------------|------|--------|
| N1-001 | RoleResource | permissions | 29 | TODO |
| N1-002 | AdminUserResource | roles | 26 | TODO |
| N1-003 | VideoUploadSessionResource | videos | 35 | TODO |

---

## PHASE 16: RESOURCE INCONSISTENCIES

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| RES-I-001 | Dynamic array access without type checking | `RoleResource.php` | 28 | TODO |
| RES-I-002 | Dynamic array access without type checking | `CenterResource.php` | 32 | TODO |
| RES-I-003 | Hardcoded default value (1800) | `TokenResource.php` | 26 | TODO |
| RES-I-004 | Hardcoded type mapping (1 = 'branded') | `CenterResource.php` | 48-54 | TODO |
| RES-I-005 | Raw array instead of Resource | `AdminVideoResource.php` | 39-44 | TODO |
| RES-I-006 | No date formatting - relies on auto-serialization | 20+ files | - | TODO |

---

# PART 5: FORM REQUESTS

## PHASE 17: FORM REQUEST AUTHORIZATION (CRITICAL)

**ALL 76 Form Requests return `authorize() => true` with NO actual authorization:**

| ID | Category | Count | Status |
|----|----------|-------|--------|
| FRM-001 | Admin Form Requests | 57 | TODO |
| FRM-002 | Mobile Form Requests | 18 | TODO |
| FRM-003 | Webhook Form Requests | 1 | TODO |

---

## PHASE 18: WEAK VALIDATION RULES

| ID | Issue | File | Line | Status |
|----|-------|------|------|--------|
| VAL-001 | Empty rules array - NO validation | `Mobile/ShowCourseRequest.php` | 19-22 | TODO |
| VAL-002 | Empty rules array - NO validation | `Mobile/RequestPlaybackRequest.php` | 19-22 | TODO |
| VAL-003 | OTP field missing min/max length | `Mobile/VerifyOtpRequest.php` | 22 | TODO |
| VAL-004 | Name field missing max:255 | `Mobile/UpdateProfileRequest.php` | 19-24 | TODO |
| VAL-005 | session_id missing exists validation | `Mobile/PlaybackProgressRequest.php` | 22 | TODO |
| VAL-006 | session_id missing exists validation | `Mobile/RefreshPlaybackTokenRequest.php` | 22 | TODO |
| VAL-007 | center_id missing exists validation | `Admin/Courses/ListCoursesRequest.php` | 24 | TODO |
| VAL-008 | category_id missing exists validation | `Admin/Courses/ListCoursesRequest.php` | 25 | TODO |
| VAL-009 | center_id missing exists validation | `Admin/Students/ListStudentsRequest.php` | 24 | TODO |
| VAL-010 | course_id missing exists validation | `Admin/Videos/ListVideosRequest.php` | 24 | TODO |

---

## PHASE 19: BUSINESS LOGIC IN FORM REQUESTS

| ID | Issue | File | Lines | Status |
|----|-------|------|-------|--------|
| BL-001 | Type/tier resolution in validated() | `Admin/Centers/StoreCenterRequest.php` | 54-74 | TODO |
| BL-002 | Type/tier resolution in validated() | `Admin/Centers/UpdateCenterRequest.php` | 37-52 | TODO |
| BL-003 | Difficulty mapping in prepareForValidation() | `Admin/Courses/CreateCourseRequest.php` | 16-29 | TODO |
| BL-004 | Difficulty mapping in prepareForValidation() | `Admin/Courses/UpdateCourseRequest.php` | 16-29 | TODO |
| BL-005 | Complex metadata validation | `Admin/Instructors/StoreInstructorRequest.php` | 39-64 | TODO |
| BL-006 | Complex metadata validation | `Admin/Instructors/UpdateInstructorRequest.php` | 39-64 | TODO |
| BL-007 | Course ID extraction from route | `Admin/Sections/StoreSectionRequest.php` | 16-40 | TODO |
| BL-008 | Default status setting | `Admin/Enrollments/StoreEnrollmentRequest.php` | 19-24 | TODO |

---

## PHASE 20: FORM REQUEST NAMING ISSUES

| ID | Issue | File | Status |
|----|-------|------|--------|
| NAME-001 | Double "Request" suffix | `Admin/ExtraViews/ApproveExtraViewRequestRequest.php` | TODO |
| NAME-002 | Double "Request" suffix | `Admin/ExtraViews/RejectExtraViewRequestRequest.php` | TODO |
| NAME-003 | Inconsistent Store/Create naming | Multiple files | TODO |

---

# PART 6: TESTS

## PHASE 21: MISSING CRITICAL TESTS

| ID | Test Area | Current | Required | Status |
|----|-----------|---------|----------|--------|
| TEST-001 | JwtServiceTest | 1 test | 10+ tests | TODO |
| TEST-002 | JWT expiration handling | 0 tests | 3+ tests | TODO |
| TEST-003 | JWT refresh token rotation | 0 tests | 2+ tests | TODO |
| TEST-004 | Invalid/malformed JWT | 0 tests | 3+ tests | TODO |
| TEST-005 | Rate limiting OTP | 0 tests | 2+ tests | TODO |
| TEST-006 | Rate limiting admin login | 0 tests | 2+ tests | TODO |
| TEST-007 | EnrollmentService unit tests | 0 tests | 5+ tests | TODO |
| TEST-008 | Device revocation scenarios | 0 tests | 3+ tests | TODO |

---

## PHASE 22: TEST QUALITY ISSUES

| ID | Issue | File | Status |
|----|-------|------|--------|
| TQ-001 | Minimal assertions - only checks type | `Unit/Services/Devices/DeviceServiceTest.php` | TODO |
| TQ-002 | Missing edge cases for OTP | `Unit/Services/Auth/OtpServiceTest.php` | TODO |
| TQ-003 | Hardcoded test data instead of factories | Multiple test files | TODO |
| TQ-004 | ApiTestHelper auto-creates device masking bugs | `tests/Helpers/ApiTestHelper.php` | TODO |
| TQ-005 | AdminTestHelper hard-codes permissions | `tests/Helpers/AdminTestHelper.php` | TODO |

---

# PART 7: MISSING AUDIT LOGGING

## PHASE 23: AUDIT LOGGING GAPS

| ID | Operation | Service/Controller | Status |
|----|-----------|-------------------|--------|
| AUDIT-001 | OTP send/verify | OtpService, AuthController | TODO |
| AUDIT-002 | Enrollment request creation | RequestService | TODO |
| AUDIT-003 | Extra view request creation | RequestService | TODO |
| AUDIT-004 | Device change request creation | RequestService | TODO |
| AUDIT-005 | Playback session creation | PlaybackService | TODO |
| AUDIT-006 | Playback progress updates | PlaybackService | TODO |
| AUDIT-007 | Student creation | StudentService | TODO |

---

# PART 8: INCONSISTENCIES

## PHASE 24: CROSS-CUTTING INCONSISTENCIES

| ID | Issue | Details | Status |
|----|-------|---------|--------|
| INCON-001 | Status constant types mixed | Enrollment uses int (0,1,2), ExtraViewRequest/DeviceChangeRequest use strings | TODO |
| INCON-002 | Two services for same flow | RequestService vs ExtraViewRequestService for extra view requests | TODO |
| INCON-003 | Two services for same flow | RequestService vs DeviceChangeService for device change requests | TODO |
| INCON-004 | Inconsistent transaction usage | EnrollmentService uses transactions, RequestService doesn't | TODO |
| INCON-005 | Inconsistent audit logging | Some services log, RequestService doesn't | TODO |
| INCON-006 | `$fillable` without docblock @property | Course, Video, Section, Pdf (is_demo field) | TODO |
| INCON-007 | SoftDeletes missing in some models | Permission, BunnyWebhookLog, SystemSetting | TODO |
| INCON-008 | Center type cast inconsistency | UserCenter casts as string, but checked as int | TODO |
| INCON-009 | Pivot table naming | course_instructors (plural) vs course_video (singular) | TODO |
| INCON-010 | sometimes + required validation combo | Multiple UpdateRequest files | TODO |

---

# PART 9: PERFORMANCE

## PHASE 25: PERFORMANCE ISSUES

| ID | Issue | File | Status |
|----|-------|------|--------|
| PERF-001 | Multiple DB queries per request in mobile middleware | `app/Http/Middleware/JwtMobileMiddleware.php` | TODO |
| PERF-002 | N+1 query risk in PlaybackAuthorizationService | `app/Services/Playback/PlaybackAuthorizationService.php` | TODO |
| PERF-003 | No caching for frequently accessed data (centers, settings) | - | TODO |
| PERF-004 | Duplicated enrollment queries across services | Multiple | TODO |

---

# PART 10: ARCHITECTURAL REFACTORING

## PHASE 26: ARCHITECTURE IMPROVEMENTS

| ID | Issue | Recommendation | Status |
|----|-------|----------------|--------|
| ARCH-001 | Complex multi-step operations in services | Extract to Action classes: EnrollStudentAction, ApproveDeviceChangeAction, RequestPlaybackAction | TODO |
| ARCH-002 | Scattered authorization logic | Create unified AuthorizationPipeline for student actions | TODO |
| ARCH-003 | Duplicated request creation logic | Consolidate RequestService with dedicated services | TODO |
| ARCH-004 | No base admin controller | Create BaseAdminController with requireAdmin() | TODO |
| ARCH-005 | No base mobile controller | Create BaseMobileController with requireStudent() | TODO |
| ARCH-006 | Audit logging scattered | Create centralized AuditLogService | TODO |

---

# RECOMMENDED REFACTORING SPRINTS

## Sprint 1: Critical Security & Bugs (Week 1) ✓ COMPLETE
1. ~~Fix hardcoded OTP (SEC-001)~~ (HOLD - dev testing)
2. ✓ Fix invalid enrollment status constant (BUG-001) - DONE
3. ✓ Add rate limiting to auth endpoints (SEC-002, SEC-003, SEC-004) - DONE
4. ~~Fix OTP error handling (BUG-002)~~ (HOLD - WhatsApp not ready)
5. ✓ BUG-003, BUG-004 - DONE

## Sprint 2: Data Integrity (Week 2) ✓ COMPLETE
1. ✓ Add transactions to RequestService (RACE-001, RACE-002, RACE-003) - DONE
2. ✓ FK indexes (IDX-001 through IDX-018) - N/A (constrained() creates indexes)
3. Soft deletes (SD-001 through SD-006) - Partially N/A (Laravel tables, logs)
4. Cascade rules (FK-001 through FK-003) - LOW PRIORITY (only needed if PKs change)

## Sprint 3: Model Scopes & Constants (Week 3) ✓ COMPLETE
1. ✓ Define all status constants (CONST-001 through CONST-006) - DONE
2. ✓ Create model scopes for duplicated queries (SCOPE-001 through SCOPE-009) - DONE
3. ✓ Refactor services to use new constants - DONE

## Sprint 4: Service Consolidation (Week 4)
1. Create service interfaces for all 37 services
2. Consolidate RequestService with dedicated services
3. Extract authorization logic to shared traits/services
4. Fix DI violations (STD-001 through STD-003)

## Sprint 5: Resources & Validation (Week 5)
1. Add @property annotations to all 40 Resources
2. Fix N+1 query risks in Resources
3. Add missing validation rules to Form Requests
4. Move business logic from Form Requests to Services

## Sprint 6: Testing & Architecture (Week 6)
1. Expand JwtServiceTest to 10+ tests
2. Add missing critical tests (TEST-001 through TEST-008)
3. Create base controllers (ARCH-004, ARCH-005)
4. Implement centralized audit logging

---

# APPENDIX: ISSUE COUNT BY CATEGORY

| Category | P0 | P1 | P2 | P3 | Total |
|----------|----|----|----|----|-------|
| Security | 5 | 6 | - | - | 11 |
| Bugs | 4 | - | - | - | 4 |
| Race Conditions | - | 5 | - | - | 5 |
| Database/Migrations | - | 3 | 24 | 6 | 33 |
| Missing Interfaces | - | - | 37 | - | 37 |
| Model Scopes | - | - | 9 | - | 9 |
| Constants | - | - | 6 | - | 6 |
| Authorization Duplication | - | - | 4 | - | 4 |
| Code Standards | - | - | 7 | - | 7 |
| Resources | - | - | 43 | 6 | 49 |
| Form Requests | - | 10 | 8 | 3 | 21 |
| Tests | - | 8 | 5 | - | 13 |
| Audit Logging | - | - | 7 | - | 7 |
| Inconsistencies | - | - | - | 10 | 10 |
| Performance | - | - | - | 4 | 4 |
| Architecture | - | - | - | 6 | 6 |
| **TOTAL** | **9** | **32** | **150** | **35** | **226** |
