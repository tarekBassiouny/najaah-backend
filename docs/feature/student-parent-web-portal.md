# Student & Parent Web Portal

## Status: Planning (2026-03-22)

---

## Objective

Build a web portal with two audiences:

1. **Student Web Portal** — full feature parity with the mobile app (courses, videos, quizzes, assignments, progress, requests)
2. **Parent Web Portal** — read-only dashboard to monitor linked student(s) progress, quiz results, assignment status, and activity

Both portals respect multi-tenancy (`center_id`), center-level feature flags, and the existing JWT auth infrastructure.

---

## Architecture Decisions

### AD-1: Auth — JWT for Everything

The entire system uses JWT guards. Web portals follow the same pattern:

| Portal | Guard | Login Method | Device Binding |
|--------|-------|-------------|----------------|
| Student Web | `jwt.web.student` | Phone + OTP | Yes (separate web device pool) |
| Parent Web | `jwt.web.parent` | Phone + OTP | No |
| Mobile (existing) | `jwt.mobile` | Phone + OTP | Yes (mobile device pool) |
| Admin (existing) | `jwt.admin` | Email + Password | No |

> **Naming convention:** Middleware aliases use dot notation (`jwt.web.student`,
> `jwt.web.parent`, `jwt.web.student.optional`) matching the existing
> `jwt.mobile`, `jwt.mobile.optional`, `jwt.admin` pattern.

- Web and mobile device pools are **independent** — a student can have 1 mobile device + 1 web browser bound simultaneously
- Concurrency rule still enforced: no simultaneous playback across web + mobile
- Parents have no device binding (read-only, no playback)

### AD-2: Parent Identity — Flag on User Table

- Parents are `User` records with `is_parent = true` (mirrors `is_student` pattern)
- New `parent_student_links` pivot table connects parent → student(s) per center (many-to-many)
- Reuses existing JWT + OTP auth infrastructure entirely
- A parent can have multiple students (siblings), a student can have multiple parents (mother + father)
- Identity resolution is **center-scoped by phone**:
  - If a user already exists in the same center with the same phone, parent registration reuses that row
    and sets `is_parent = true`
  - If that existing row is also a student, the same row becomes a dual-role user
  - Only when no matching row exists does registration create a new parent user

### AD-3: Parent-Student Linking — Dual Path

**Path A — Admin-Managed (Primary)**
- Admin sets `parent_phone` on student profile (already exists)
- Admin can explicitly create parent-student links from admin panel

**Path B — Parent Self-Registration with Auto-Link**
- Parent registers with phone + OTP → resolve existing same-center user by phone first, otherwise create a new User with `is_parent = true`
- System checks: any students in this center have `parent_phone` matching parent's phone?
  - YES → auto-create `parent_student_links` row (status=Active, method=AutoMatched)
  - NO → parent has no linked students yet
- Parent can request to link additional students → pending admin approval
- The `parent_student_links` pivot table is always the **source of truth**, phone matching is only a one-time auto-discovery convenience

### AD-4: Web Device Binding — Separate Pool (Option B)

- Web browser registers as a `UserDevice` with `device_type = web`
- Web pool is capped by `web_device_limit` (default: 1)
- Mobile pool is capped by the existing `device_limit`
- `user_devices` stays a shared table, but active-device checks become **platform-aware**
- Web login must never revoke mobile devices, and mobile login must never revoke web devices
- Each pool enforces its own max limit independently
- Concurrency enforcement: no two active playback sessions across any platform

### AD-5: Parent Portal — Read-Only

Parents can view:
- Linked student profiles & enrollment list
- Course progress per learning asset
- Quiz attempt results with full question/answer review
- Assignment submission status & grades
- Video watch progress & view usage
- Weekly activity summary

Parents **cannot**:
- Play videos or access PDF content
- Enroll/unenroll students
- Change student settings
- Submit quizzes/assignments on behalf of student

Future expansion possible.

### AD-6: Parent Quiz Visibility — Full Review

Parents see full question/answer detail on graded quiz attempts:
- Question text, selected answers, correct answers, explanation
- Score, points earned, pass/fail status
- Respects quiz `show_correct_answers` setting

### AD-7: Student Web Login — Same Identity Resolution as Mobile

- Student web login follows the existing mobile OTP identity resolution rules
- If OTP resolves an existing student in the scoped center, reuse that student
- If OTP resolves no student in the scoped center, create the same placeholder student that mobile would create
- Web login changes the delivery surface, not the student identity model

---

## Settings Governance

Full classification, catalog entries, resolution rules, and validation:
**[web-portal-settings-governance.md](web-portal-settings-governance.md)**

Summary — follows the existing 3-layer governance model:

| Layer | New Settings | Owner |
|-------|-------------|-------|
| Feature Flags | `web_access`, `web_playback`, `parent_portal` | System admin |
| Center Settings | `allow_web_access`, `allow_web_playback`, `web_device_limit`, `allow_parent_portal` | Center admin (gated by feature flags) |
| System Limits | `max_web_device_limit` | System admin |
| System Overrides | `force_disable_web_access`, `force_disable_web_playback`, `force_disable_parent_portal` | System admin |

Key rule: `web_playback` depends on `web_access` — disabling access cascades to playback.

---

## Schema Changes

### New Table: `parent_student_links`

```
id                  BIGINT UNSIGNED AUTO_INCREMENT PK
parent_user_id      BIGINT UNSIGNED FK(users) CASCADE
student_user_id     BIGINT UNSIGNED FK(users) CASCADE
center_id           BIGINT UNSIGNED FK(centers) CASCADE
status              TINYINT (0=Active, 1=PendingApproval, 2=Revoked)
linked_by           BIGINT UNSIGNED FK(users) NULLABLE — admin who created the link, null if auto-linked
link_method         TINYINT (0=AdminManaged, 1=AutoMatched, 2=ParentRequested)
linked_at           TIMESTAMP
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE (soft delete)

UNIQUE INDEX: (parent_user_id, student_user_id, center_id)
INDEX: (student_user_id)
INDEX: (center_id)
INDEX: (status)
INDEX: (deleted_at)
```

### Alter Table: `users`

```
ADD is_parent       BOOLEAN DEFAULT FALSE AFTER is_student
```

### Alter Table: `user_devices`

> **Note:** `device_type` already exists as a nullable string column (added in
> `2026_02_26_030000_add_device_name_and_type_to_user_devices_table.php`).
> Currently stores free-text values from mobile clients. No migration needed —
> the `DeviceType` enum (Mobile, Web) will use string-backed values that coexist
> with existing data. `DeviceService` will be updated to normalize incoming
> values and the model will cast to the new enum.

### Alter Table: `jwt_tokens`

```
ADD platform        TINYINT DEFAULT 0 AFTER device_id
-- 0 = mobile, 1 = web
-- Allows middleware to identify token origin and route to correct device pool
```

> **Note:** `jwt_tokens` has no `center_id` column. The `platform` column is
> placed after `device_id`.

---

## Phase Plan

### Phase 0 — Settings Refactor (Catalog-Driven Governance)

**Why:** Current settings services hardcode constraints, overrides, and feature flag checks in if-blocks
across 3 files. Adding 11 new settings would mean 8+ scattered if-blocks. Refactoring first makes
the services catalog-driven so new settings only require a catalog entry + seeder. Frontend also needs
Feature-as-Section-Header pattern so feature flags and their governed settings appear together.

**Priority:** Must complete before any web portal work. Standalone PR per sub-phase.

#### Phase 0A — Backend: Catalog-Driven Services

0A.1. Add `value_key` metadata to catalog for system settings
      - Standardize: `'value_key' => 'value'` for integers, `'value_key' => 'enabled'` for booleans
      - Backfill `value_key` on ALL existing system settings (not just new ones) —
        currently no catalog entries have this field
      - Enables `normalizeSystemValue()` to resolve any setting generically
      - Use the same metadata to validate expected `value` payload shape in system setting requests

0A.2. Add `feature_group` metadata to catalog entries
      - Links center settings, system limits, system overrides, and feature flags into logical groups
      - Example: `allow_guest_browsing`, `force_disable_guest_browsing`, and feature flag `guest_browsing`
        all get `'feature_group' => 'guest_browsing'`
      - Enables frontend to render Feature-as-Section-Header cards

0A.3. Make `PolicySettingsService.systemConstraints()` catalog-driven
      - Loop catalog where `group in ['limits', 'overrides']` and build constraints dynamically
      - Remove hardcoded key list

0A.4. Make `PolicySettingsService.applyCenterGovernance()` catalog-driven
      - Loop center settings catalog:
        - If `system_limit` → apply `min(resolved_value, system_limit_value)`
        - If `system_override` → force to disabled value when override is true
        - If `feature_flag` → force to disabled value when flag is off
      - Add support for `depends_on` metadata (web_playback depends on web_access)
      - Remove all hardcoded if-blocks

0A.5. Make `CenterSettingsService.enforceSystemConstraints()` catalog-driven
      - Loop catalog entries with `system_limit` → validate incoming value against ceiling
      - Single generic error: "X cannot exceed the system maximum of Y"

0A.6. Deduplicate `SettingsResolverService.applySystemConstraints()`
      - Delegate to `PolicySettingsService` instead of duplicating if-blocks

0A.7. Make `SettingsResolverService.$allowedKeys` catalog-driven
      - Derive from catalog entries where `scope = 'center'`

0A.8. Update API response: include `feature_group` in catalog and `sections.feature_groups`
      - New response field: `sections.feature_groups` built dynamically from catalog `feature_group` metadata
      - Replace remaining hardcoded settings-page grouping for feature-managed settings with catalog-derived output
      - Each feature group contains:
        ```json
        {
          "web_portal": {
            "feature_flag": "web_access",
            "flag_enabled": true,
            "center_settings": ["allow_web_access", "allow_web_playback", "web_device_limit"],
            "system_limits": ["max_web_device_limit"],
            "system_overrides": ["force_disable_web_access", "force_disable_web_playback"],
            "depends_on": null
          }
        }
        ```
      - Frontend reads this to render Feature-as-Section-Header cards dynamically

0A.9. Tests: Zero-regression on all existing settings behavior
      - Run existing tests
      - Add test: catalog entry with `system_limit` auto-enforced
      - Add test: catalog entry with `system_override` auto-enforced
      - Add test: catalog entry with `feature_flag` auto-gated

**Depends on:** Nothing
**Output:** Backend settings fully catalog-driven. API response includes feature grouping.

#### Phase 0B — Frontend: Dynamic Feature-as-Section-Header UI

**Key principle:** Frontend renders settings entirely from backend API response — no hardcoded
groups, fields, or layout. The backend `catalog` + `sections` response drives everything.

0B.1. New component: `FeatureSettingsCard`
      - NOTE: Backend API response with `sections.feature_groups` is already handled in 0A.8
      - Renders entirely from `feature_groups` API data — no hardcoded field names
      - Header: feature name (from translation or humanized key) + ON/OFF toggle (system admin only)
      - Body: dynamically renders center settings fields from `center_settings` list
      - System section: dynamically renders limits/overrides from `system_limits` + `system_overrides`
      - When flag is OFF: card body collapsed/grayed, center admin doesn't see card at all
      - Field types, validation, and hints all derived from `catalog` definitions

0B.2. Update `CenterSettingsEditor` — dynamic layout
      - Read `sections.feature_groups` from API response
      - For each feature group → render `FeatureSettingsCard`
      - For settings NOT in any feature group → render in regular `SettingsSectionCard` (grouped by `group`)
      - System admin (manage view): sees flag toggles + system limits/overrides inside cards
      - Center admin (workspace view): sees only center settings, no flag toggle, card hidden when flag off
      - Zero hardcoded group names or field names in frontend code

0B.3. Move feature-specific system settings out of platform `/settings` page
      - `force_disable_*` overrides appear inside their feature cards on manage view
      - Platform `/settings` page keeps only global settings:
        site_name, timezone, support_email, require_device_approval, attendance_required,
        whatsapp_bulk_settings, max_view_limit, max_device_limit

0B.4. Add translations for groups and fields
      - Group labels: keyed by feature_group name (fallback: humanized key)
      - Field labels: keyed by setting key (fallback: humanized key)
      - All translations optional — UI works with fallbacks for any new setting added later

0B.5. Update TypeScript types
      - Add `feature_group` to `DynamicSettingDefinition`
      - Add `FeatureGroupSection` type matching API response
      - Update `CenterSettingsSections` to include `feature_groups`

0B.6. Test: verify dynamic rendering
      - Adding a new catalog entry with `feature_group` on backend → auto-appears in frontend
      - Save flow works across grouped cards (settings + features + limits in one payload)
      - Diff detection works correctly

**Depends on:** Phase 0A (API response includes feature_group)
**Output:** Settings UI groups feature flags with their governed settings. Clean governance UX.

#### Phase 0C — Existing Feature Retroactive Cleanup

0C.1. Add `feature_group` to existing catalog entries:
      - `guest_browsing` group: `allow_guest_browsing`, `force_disable_guest_browsing`, feature `guest_browsing`
      - `pdf_downloads` group: `pdf_download_permission`, `force_disable_pdf_download`, feature `pdf_downloads`
      - `codes_access` group: `video_code_expiry_days`, `requires_video_approval`, feature `codes_access`
      - `whatsapp_bulk` group: feature `whatsapp_bulk` (no center settings yet)
      - `ai_content` group: feature `ai_content` (AI provider settings handled separately)

0C.2. Verify all existing settings render correctly in new card layout
0C.3. Verify center admin view unchanged (cards hidden when flags off)
0C.4. Verify system admin manage view shows flags + settings + limits in unified cards

**Depends on:** Phase 0A + 0B
**Output:** All existing features use the new pattern. Consistent UX across all feature groups.

---

### Phase 1 — Architecture (Schema & Models)

**Tasks:**

1.1. Migration: Add `is_parent` to `users` table
1.2. Model: Update `UserDevice` — cast existing `device_type` string to `DeviceType` enum,
     add `scopeWeb()` / `scopeMobile()` scopes. No migration needed (column already exists).
     Update `DeviceService.register()` to normalize incoming `device_type` to enum values.
     - Device registration becomes platform-aware: mobile writes only affect mobile devices,
       web writes only affect web devices
1.3. Migration: Add `platform` column to `jwt_tokens` table after `device_id`
     (TINYINT: 0=mobile, 1=web — so middleware knows which device pool)
1.4. Migration: Create `parent_student_links` table
1.5. Settings: Add to `config/settings_catalog.php` — 3 feature flags, 4 center settings, 4 system settings
     (see [settings governance doc](web-portal-settings-governance.md))
     - Include `feature_group` on all new entries (catalog-driven after Phase 0)
     - Add `depends_on: 'allow_web_access'` to `allow_web_playback` for cascade
1.5a. Seeder: Add system settings to `SystemSettingSeeder`, center defaults to `CenterSettingSeeder`
1.5b. Migration: Seed new feature flags + center settings into existing `center_settings` JSON for all centers
1.5c. Request: Validation auto-derived from catalog after Phase 0 — verify new keys are accepted
1.6. Model: `ParentStudentLink` with relationships and scopes
1.7. Model: Update `User` — add `is_parent` cast, `parentLinks()`, `childLinks()`, `linkedStudents()`, `linkedParents()` relations
1.8. (Merged into 1.2 — `UserDevice` enum cast and scopes handled there)
1.9. Model: Update `JwtToken` — add `platform` cast
1.10. Enum: `ParentLinkStatus` (Active, PendingApproval, Revoked)
1.11. Enum: `ParentLinkMethod` (AdminManaged, AutoMatched, ParentRequested)
1.12. Enum: `DeviceType` (Mobile='mobile', Web='web') — string-backed to coexist
      with existing free-text `device_type` values on `user_devices`
1.13. Enum: `TokenPlatform` (Mobile, Web)
1.14. Error codes: Add to `ErrorCodes` class:
      - `WEB_ACCESS_DISABLED`, `WEB_PLAYBACK_DISABLED`, `PARENT_PORTAL_DISABLED`
      - `PARENT_LINK_NOT_FOUND`, `STUDENT_NOT_LINKED`, `LINK_ALREADY_EXISTS`
      - `WEB_DEVICE_LIMIT_REACHED`, `PARENT_LINK_PENDING`
1.15. Factory: `ParentStudentLinkFactory`

**Depends on:** Phase 0 (catalog-driven settings)
**Output:** Schema ready, models with relationships, enums, error codes defined

---

### Phase 2 — Auth & Middleware

**Tasks:**

2.1. Guard: Register `web-student` and `web-parent` JWT guards in `config/auth.php`
2.1a. Middleware aliases: Register in `bootstrap/app.php` `$middleware->alias([...])`:
      - `'jwt.web.student' => JwtWebStudentMiddleware::class`
      - `'jwt.web.parent' => JwtWebParentMiddleware::class`
      - `'jwt.web.student.optional' => OptionalJwtWebStudentMiddleware::class`
2.1b. Route registration: Add web portal route group in `bootstrap/app.php` `withRouting` → `then`:
      ```php
      // Web Portal API (JWT)
      Route::prefix('api/v1/web')
          ->middleware('api')
          ->group(function (): void {
              require __DIR__.'/../routes/api/v1/web/auth.php';
              require __DIR__.'/../routes/api/v1/web/student.php';
              require __DIR__.'/../routes/api/v1/web/parent.php';
          });
      ```
2.2. Middleware: `JwtWebStudentMiddleware` — validates JWT, `is_student`, active status, center scope,
     web device binding, `allow_web_access` center setting check
2.3. Middleware: `JwtWebParentMiddleware` — validates JWT, `is_parent`, active status, center scope,
     `allow_parent_portal` center setting check
2.4. Middleware: `OptionalJwtWebStudentMiddleware` — permissive variant for guest browsing on web
2.5. Web device registration flow:
     - On first web login, backend generates a UUID as `device_id`
     - Returned in login response alongside JWT tokens
     - Frontend stores UUID in localStorage and sends it on subsequent requests via header (e.g., `X-Web-Device-Id`)
     - Backend registers it as `UserDevice` with `device_type = web`
     - On subsequent logins from same browser, frontend sends stored UUID → backend validates
     - If UUID missing (new browser), backend generates new one → checks `web_device_limit`
2.6. Update `UserDevice` service: handle web device registration with `device_type = web`
     - Must not revoke or invalidate mobile devices during web registration
2.7. Update device binding logic: respect `web_device_limit` separately from mobile `device_limit`
     - Mobile pool allows active devices up to `device_limit`
     - Web allows multiple active browsers up to `web_device_limit`
2.8. JWT token platform tracking:
     - On web login, set `platform = web` on `JwtToken` record
     - Middleware reads token platform to route to correct device pool
     - Same student can have active mobile + web tokens simultaneously
2.9. Service: `WebAuthService` (or extend existing `AuthService`) — OTP send/verify for web login,
     JWT token issuance with `platform = web`, web device binding (not mobile device binding)
     - Login sequence: (1) verify OTP, (2) resolve/generate web device UUID,
       (3) call `DeviceService.register(user, uuid, meta)` with `device_type = web`,
       (4) call `JwtService.create(user, device, platform)` — device is mandatory for token creation;
       extend `create()` signature to accept optional `TokenPlatform` param (defaults to Mobile
       for backward compat), sets `platform` on the `JwtToken` record
2.10. Service: `ParentRegistrationService` — resolve-or-register parent user (`is_parent = true`),
      auto-link by matching `parent_phone` in same center, handle additional link requests
      - Resolve existing same-center user by phone before creating a new row
      - If an existing student row matches, upgrade it to dual-role instead of creating a duplicate user
2.11. Controller: `Web/Auth/StudentAuthController` — send OTP, verify/login, refresh, logout
2.12. Controller: `Web/Auth/ParentAuthController` — register, send OTP, verify/login, refresh, logout
2.13. FormRequest: `WebLoginRequest`, `ParentRegisterRequest`
2.14. Routes: `/api/v1/web/auth/student/*`, `/api/v1/web/auth/parent/*`
2.15. Bindings: Add to `AppServiceProvider.php` (single provider, no domain-specific providers):
      `WebAuthServiceInterface` → `WebAuthService`,
      `ParentRegistrationServiceInterface` → `ParentRegistrationService`
2.16. CORS: Allow web portal origins
      - Frontend deploys at `{center-slug}.najaah.me` (separate domain from admin `.com`)
      - Frontend routing (`/portal/student/*`, `/portal/parent/*`) is handled by the SPA — not a backend concern
      - Add wildcard pattern to `config/cors.php`:
        ```php
        'allowed_origins_patterns' => [
            '#^https://[\w-]+\.najaah\.me$#',
        ],
        ```
      - Covers all current and future centers — no CORS update on center onboarding
      - JWT in Authorization header (not cookies), so CORS is the only cross-origin concern
2.17. Token revocation scoping:
      - Web logout → revoke only tokens with `platform = web` for that user
      - Mobile logout → revoke only tokens with `platform = mobile` for that user
      - Admin "revoke all sessions" → revokes both platforms
      - Note: `jwt_tokens` has no `center_id` — revocation is per-user per-platform,
        not per-center. For multi-center users this means logout revokes all tokens
        for that platform across centers. Acceptable for MVP.
2.18. Dual identity: A user can be both `is_student = true` AND `is_parent = true`
      - Parent registration should prefer upgrading the existing same-center user row when phones match
      - They log in as student via `web-student` guard (gets student token with platform=web)
      - They log in as parent via `web-parent` guard (gets separate parent token with platform=web)
      - Two separate JWT tokens, two separate sessions
      - Frontend handles role switching (or separate apps)

**Depends on:** Phase 1 (schema, models, enums)
**Output:** Working JWT auth for both portals, web device binding, CORS, platform-scoped tokens

---

### Phase 3 — Student Web Portal (Feature Parity)

Mobile controllers are thin service delegates with no platform-specific logic — reuse them
directly via a web route file that swaps the guard middleware. No duplicate controllers needed.

**Reuse strategy:**
- Same `Mobile\*Controller` classes, registered under `api/v1/web` with `jwt.web.student` guard
- Auth is the exception — mobile auth has device registration baked in, web needs its own flow
- `DeviceChangeRequestController` is mobile-only — excluded from web routes
- `MeController` has minor device awareness — needs a thin web variant for profile/logout

**Tasks:**

3.1. Route file: `routes/api/v1/web/student.php`
     - Imports existing `Mobile\*Controller` classes (no new controllers for most endpoints)
     - Guest-browsable routes (`jwt.web.student.optional` + `guest.browsing`):
       explore, search, centers, categories, instructors
       (mirrors mobile `jwt.mobile.optional` pattern)
     - Authenticated routes (`jwt.web.student`): all other student endpoints
     - Excludes: device-change routes (mobile-only)
3.2. Controller: `Web/Student/MeController` — thin web variant
     - Profile: same as mobile but excludes mobile-specific device info
     - Logout: revokes only `platform = web` tokens
     - Education update: delegates to same service
     - Reuses: `StudentProfileQueryService`, `StudentService`, `JwtService`
3.3. Auth handled in Phase 2 (`Web/Auth/StudentAuthController`) — separate from mobile auth
3.4. Education routes: re-declare education endpoints in `web/student.php` pointing to
     the same `Mobile\Education\*Controller` classes with `jwt.web.student` guard
     (cannot `require` mobile/education.php directly — it wraps routes in `jwt.mobile`)
3.5. Update `PlaybackService` — add `allow_web_playback` check, web device validation
     - When token `platform = web`, use `web_device_limit` instead of `device_limit`
     - Concurrency still enforced across platforms (no simultaneous web + mobile playback)
3.6. Resources: Reuse existing mobile resources (they are API resources, platform-agnostic)

**Controllers reused directly (no changes needed):**
- `ExploreController`, `SearchController`, `CentersController`, `CategoryController`
- `InstructorController`, `EnrolledCoursesController`, `WeeklyActivityController`
- `PlaybackController`, `PdfController`, `CourseAssetController`
- `QuizAttemptController`, `AssignmentSubmissionController`, `AssignmentGroupController`
- `ExtraViewRequestController`, `VideoAccessRequestController`, `VideoAccessCodeController`
- `EnrollmentRequestController`, `SurveyController`

**Controllers NOT reused:**
- `AuthController` — mobile device registration baked in (web auth in Phase 2)
- `MeController` — minor device awareness, web variant in 3.2
- `DeviceChangeRequestController` — mobile-only feature

**Depends on:** Phase 2 (auth guards, middleware)
**Output:** Full student web API, feature parity with mobile

---

### Phase 4 — Parent Web Portal (Read-Only Dashboard)

**Tasks:**

4.1. Route file: `routes/api/v1/web/parent.php`
4.2. Service: `ParentService` interface + implementation
    - `getLinkedStudents(parent, centerId)` — list linked students
    - `getStudentDetail(parent, studentId)` — student profile (authorized via link check)
    - `requestLink(parent, studentPhone, centerId)` — request to link new student (PendingApproval)
    - `autoLinkByPhone(parent, centerId)` — find students with matching parent_phone and auto-link
    - All write operations audit-logged via `AuditLogService`
4.3. Service: `ParentProgressService` interface + implementation
    - `getStudentEnrollments(parent, studentId)` — enrollment list (authorized)
    - `getCourseProgress(parent, studentId, courseId)` — learning asset progress per course
    - `getQuizAttempts(parent, studentId, courseId)` — quiz attempts with full review
    - `getQuizAttemptDetail(parent, attemptId)` — single attempt with questions/answers
    - `getAssignmentSubmissions(parent, studentId, courseId)` — assignment status
    - `getWeeklyActivity(parent, studentId, centerId)` — weekly summary
    - All methods verify active `parent_student_links` before returning data
4.4. Controller: `Web/Parent/StudentController` — list linked students, view student detail
4.5. Controller: `Web/Parent/LinkController` — request new link, view pending links
4.6. Controller: `Web/Parent/EnrollmentController` — student enrollment list
4.7. Controller: `Web/Parent/ProgressController` — course progress per student
4.8. Controller: `Web/Parent/QuizResultController` — quiz attempts, full attempt review
4.9. Controller: `Web/Parent/AssignmentController` — assignment submission status
4.10. Controller: `Web/Parent/ActivityController` — weekly activity per student
4.11. FormRequest: `RequestLinkRequest`
4.12. Resource: `ParentStudentResource` — student summary for parent view
4.13. Resource: `ParentQuizAttemptResource` — attempt with full question/answer detail
4.14. Resource: `ParentProgressResource` — course progress summary
4.15. Authorization: All parent controllers verify active `parent_student_links` before exposing data
4.16. Bindings: Add to `AppServiceProvider.php`:
      `ParentServiceInterface` → `ParentService`,
      `ParentProgressServiceInterface` → `ParentProgressService`
4.17. Audit actions: Add to `AuditActions` class:
      - `PARENT_LINK_CREATED`, `PARENT_LINK_AUTO_MATCHED`, `PARENT_LINK_REQUESTED`
      - `PARENT_LINK_APPROVED`, `PARENT_LINK_REVOKED`
4.18. Rate limiting: Throttle parent link requests (prevent phone enumeration abuse)
      - Apply `throttle:5,60` (5 attempts per 60 min) on `POST /parent/links` route
        (matches existing mobile auth throttle pattern in `routes/api/v1/mobile.php`)
4.19. Events (dispatch now, listeners as follow-up):
      - `ParentLinked` — fired on auto-match and admin-created links
      - `ParentLinkRequested` — fired when parent requests to link a student
      - `ParentLinkApproved` / `ParentLinkRevoked` — fired on admin actions
      - No listeners in MVP — events enable future notifications without service changes

**Depends on:** Phase 1 (schema), Phase 2 (auth)
**Can parallel with:** Phase 3 (different route groups, different controllers, disjoint files)
**Output:** Full parent read-only dashboard API with audit trail

---

### Phase 5 — Admin Integration

#### Phase 5A — Backend: Admin Parent Management API

5A.1. Service: Admin methods on `ParentService`:
      - `createLink(admin, parentUserId, studentUserId, centerId)` — admin creates link directly (Active)
      - `revokeLink(admin, linkId)` — admin revokes link
      - `approveLink(admin, linkId)` — approve PendingApproval → Active
      - `rejectLink(admin, linkId)` — reject PendingApproval → Revoked
      - `listParents(admin, centerId, filters)` — paginated parent list
      - `listLinksForStudent(admin, studentId)` — all parent links for a student
      - `listPendingRequests(admin, centerId)` — pending link requests queue
      - All operations audit-logged
5A.2. Controller: `Admin/ParentController` — list parents, view parent detail, manage links
5A.3. Controller: Update `Admin/StudentController` — show linked parents on student detail
5A.4. Resource: `AdminParentStudentLinkResource`
5A.5. Resource: `AdminParentResource` — parent with linked students summary
5A.6. FormRequest: `AdminCreateLinkRequest`, `AdminUpdateLinkRequest`
5A.7. Routes: Add parent management endpoints to admin routes:
      - `GET /admin/centers/{center}/parents` — list parents
      - `GET /admin/centers/{center}/parents/{parent}` — parent detail
      - `POST /admin/centers/{center}/parent-links` — create link
      - `PATCH /admin/centers/{center}/parent-links/{link}` — approve/reject/revoke
      - `GET /admin/centers/{center}/parent-links/pending` — pending requests
      - `GET /admin/students/{student}/parent-links` — student's parent links
5A.8. Handle `parent_phone` change on student update:
      - When admin updates student's `parent_phone`, do NOT auto-break existing links
      - Optionally trigger new auto-match for the new phone number (if a parent with that phone exists)
      - Log the change via audit
5A.9. Permissions: Add to the permissions system:
      - Add `'parents.view'` and `'parents.manage'` to `config/permissions.php` under
        a new "Parent Management" category
      - `PermissionSeeder` auto-syncs from config — no seeder changes needed
      - Update `RolePermissionSeeder`: assign both to `super_admin`, `center_owner`,
        `center_admin`; Content Manager does NOT get parent management access
      - Routes use `require.permission:parents.view` / `require.permission:parents.manage`
5A.10. Resource: Update `AdminStudentResource` to include `parent_links` relationship
       data when loaded (supports task 5A.3)

#### Phase 5B — Admin Frontend: Parent Management UI

5B.1. Page: Parent list page (`/centers/{id}/parents` or `/manage/centers/{id}/parents`)
      - Paginated table: parent name, phone, linked students count, status
      - Filter by: status, search by phone/name
5B.2. Page: Parent detail page — view linked students, link history
5B.3. Component: Parent-student link management on student detail page
      - Show linked parents
      - Add/remove parent links
5B.4. Component: Pending link requests queue
      - Approve/reject actions
      - Notification badge in sidebar (optional)
5B.5. Sidebar: Add "Parents" menu item under center admin section
5B.6. Route capability: `parents.view`, `parents.manage` (matches 5A.9 dot-notation)

**Depends on:** Phase 5A (backend API), Phase 4 (ParentService exists)
**Output:** Admins can manage parent-student relationships from the admin panel

---

### Phase 6 — Quality

**Tasks:**

6.1. Feature tests: Web student auth (login, refresh, logout, OTP flow, web device UUID generation)
6.2. Feature tests: Web parent auth (register, auto-link by phone, login, refresh)
6.3. Feature tests: Web device binding (register, limit enforcement, separate pool from mobile)
6.4. Feature tests: Token platform tracking (web token has platform=web, mobile has platform=mobile)
6.5. Feature tests: Student web endpoints (verify reused mobile controllers work under `jwt.web.student`
     guard — sample: explore, enrolled courses, quiz attempt, playback, asset progress)
6.6. Feature tests: Parent portal (linked student access, unauthorized student blocked, full quiz review)
6.7. Feature tests: Parent link requests (request, admin approval, auto-match, rate limiting)
6.8. Feature tests: Center feature flags (allow_web_access off → blocked, allow_parent_portal off → blocked)
6.9. Feature tests: Settings cascade (web_access off → web_playback forced off)
6.10. Feature tests: Concurrency (no simultaneous web + mobile playback)
6.11. Feature tests: Admin parent management (create link, approve, reject, revoke)
6.12. Feature tests: Unbranded center linking (auto-match across unbranded centers)
6.13. Feature tests: parent_phone change (existing links preserved, new auto-match triggered)
6.14. Feature tests: Audit logging (link creation, approval, revocation logged)
6.15. Unit tests: `WebAuthService`, `ParentService`, `ParentProgressService`, `ParentRegistrationService`
6.16. PHPStan + Pint pass on all new files
6.17. Factory updates for new models

**Depends on:** Phase 3 + 4 + 5
**Output:** Regression-safe implementation

---

### Phase 7 — Documentation & Postman

**Tasks:**

7.1. Verify Scribe auto-discovery picks up new web routes (project uses auto-discovery
     via `config/scribe.php` with `UseActionNameAsEndpointTitle` — no inline annotations needed)
7.2. Update `restructure.js` — add web portal folder structure to Postman collection:
    - `Web / Auth / Student` — student login, refresh, logout
    - `Web / Auth / Parent` — parent register, login, refresh, logout
    - `Web / Student / Courses` — enrolled, explore, assets
    - `Web / Student / Playback` — video playback, progress
    - `Web / Student / Quizzes` — attempts, answers, results
    - `Web / Student / Assignments` — submissions, files
    - `Web / Student / Progress` — learning asset progress
    - `Web / Student / Requests` — extra views, video access, enrollment
    - `Web / Parent / Students` — linked students
    - `Web / Parent / Links` — link requests, manage links
    - `Web / Parent / Progress` — student course progress
    - `Web / Parent / Quiz Results` — full quiz attempt review
    - `Web / Parent / Assignments` — submission status
    - `Web / Parent / Activity` — weekly activity
7.3. Generate updated Postman collection via `composer postman:generate`
7.4. Update `docs/AI_INSTRUCTIONS.md` with web portal rules (auth, device binding, parent model)
7.5. Frontend handoff doc: `docs/feature/student-parent-web-portal-api.md` covering:
    - Full endpoint list with methods, paths, and required params
    - JWT auth flow for student web (OTP → token pair → refresh cycle)
    - JWT auth flow for parent web (register → auto-link → token pair)
    - Web device binding flow (UUID generation, registration, limit handling)
    - Parent linking flows (auto-match, manual request, admin approval)
    - Response shapes for all new resources
    - Center settings that control portal availability
    - Error codes and edge cases (portal disabled, link not found, device limit reached)
7.6. Update `docs/codex/CODEX_DOMAIN_RULES.md` with parent-student relationship rules

**Depends on:** Phase 6 (all behavior verified)
**Output:** Complete API documentation, Postman collection, and frontend handoff guide

---

## Parallel Execution Map

```
Phase 0A (Backend: Catalog-Driven Services)
  │
  ├────────────────────────┐
  ▼                        ▼
Phase 0B                   Phase 0C.1
(Frontend: Feature         (Backend: Add feature_group
 Card UI)                   to existing catalog entries)
  │                        │
  ├────────────────────────┘
  ▼
Phase 0C.2-4 (Verify existing features in new card layout)
  │
  ▼
Phase 1 (Architecture: Schema & Models)
  │
  ▼
Phase 2 (Auth & Middleware)
  │
  ├──────────────────┐
  ▼                  ▼
Phase 3              Phase 4
(Student Web)        (Parent Web)
  │                  │
  ├──────────────────┘
  ▼
Phase 5A (Backend: Admin Parent API)
  │
  ▼
Phase 5B (Frontend: Admin Parent UI)
  │
  ▼
Phase 6 (Quality)
  │
  ▼
Phase 7 (Documentation & Postman)
```

- Phase 0A must complete first — backend catalog-driven services
- Phase 0B + 0C.1 (catalog entries only) can parallel after 0A
- Phase 0C.2-4 (frontend verification) needs both 0A + 0B complete
- Phase 0 is standalone PRs (pure refactor, zero feature changes)
- Phase 3 and Phase 4 can run in parallel — disjoint controllers, separate route files
- Phase 5B depends on 5A (backend API must exist before frontend consumes it)

---

## File Map (Expected New Files)

```
database/migrations/
  YYYY_MM_DD_HHMMSS_add_is_parent_to_users_table.php
  YYYY_MM_DD_HHMMSS_add_platform_to_jwt_tokens_table.php
  YYYY_MM_DD_HHMMSS_create_parent_student_links_table.php
  # No user_devices migration — device_type column already exists

app/Enums/
  ParentLinkStatus.php
  ParentLinkMethod.php
  DeviceType.php          # string-backed enum (coexists with existing device_type data)
  TokenPlatform.php

app/Events/
  ParentLinked.php
  ParentLinkRequested.php
  ParentLinkApproved.php
  ParentLinkRevoked.php

app/Models/
  ParentStudentLink.php

app/Http/Middleware/
  JwtWebStudentMiddleware.php
  JwtWebParentMiddleware.php
  OptionalJwtWebStudentMiddleware.php

app/Http/Controllers/Web/
  Auth/StudentAuthController.php
  Auth/ParentAuthController.php
  Student/MeController.php           # thin web variant (profile, logout, education)
  # All other student endpoints reuse Mobile\*Controller classes via route file
  Parent/StudentController.php
  Parent/LinkController.php
  Parent/EnrollmentController.php
  Parent/ProgressController.php
  Parent/QuizResultController.php
  Parent/AssignmentController.php
  Parent/ActivityController.php

app/Http/Requests/Web/
  WebLoginRequest.php
  ParentRegisterRequest.php
  RequestLinkRequest.php

app/Http/Resources/Web/
  ParentStudentResource.php
  ParentQuizAttemptResource.php
  ParentProgressResource.php

app/Services/
  Auth/WebAuthService.php
  Contracts/WebAuthServiceInterface.php
  Parents/ParentService.php
  Parents/ParentProgressService.php
  Parents/ParentRegistrationService.php
  Contracts/ParentServiceInterface.php
  Contracts/ParentProgressServiceInterface.php
  Contracts/ParentRegistrationServiceInterface.php

app/Http/Controllers/Admin/
  ParentController.php

app/Http/Requests/Admin/Parents/
  AdminCreateLinkRequest.php
  AdminUpdateLinkRequest.php

app/Http/Resources/Admin/
  AdminParentStudentLinkResource.php
  AdminParentResource.php

routes/api/v1/web/
  student.php
  parent.php
  auth.php

database/factories/
  ParentStudentLinkFactory.php

tests/Feature/Web/
  StudentAuthTest.php
  ParentAuthTest.php
  WebDeviceBindingTest.php
  StudentWebEndpointsTest.php     # verifies reused mobile controllers under web guard
  ParentPortalTest.php
  ParentLinkTest.php
  CenterFeatureFlagTest.php
  PlaybackConcurrencyTest.php

tests/Unit/
  WebAuthServiceTest.php
  ParentServiceTest.php
  ParentProgressServiceTest.php
  ParentRegistrationServiceTest.php
```

---

## Linking Flow Summary

```
1. Admin creates student, optionally sets parent_phone
2. Parent registers with phone + OTP → resolve existing same-center user by phone, otherwise create new User (is_parent=true)
3. System checks: any students in this center have parent_phone = parent's phone?
   → YES: auto-create parent_student_links row (status=Active, method=AutoMatched)
   → NO: parent has no linked students yet
4. Parent can request to link additional students → status=PendingApproval
5. Admin approves/rejects link requests from admin panel
6. Admin can always manually create/revoke links
7. parent_student_links pivot table is ALWAYS the source of truth
```

---

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Web device fingerprint unreliable | Server-generated UUID on first login, stored in localStorage, sent via `X-Web-Device-Id` header |
| Parent accessing unlinked student data | All parent services verify active link before query |
| Center toggles off mid-session | Middleware checks on every request, not just login |
| Quiz answer content leak via parent | Respect quiz `show_correct_answers` setting |
| Concurrent playback web + mobile | Server-side enforcement via `PlaybackSession` check across all device types |
| Parent phone collision across centers | `parent_student_links` is center-scoped, auto-match scoped to center |
| Breaking existing mobile API | Separate route group `/api/v1/web/*` reuses same controllers with guard swap — zero changes to mobile routes or controllers |
| Same student on mobile + web | `platform` field on `JwtToken` distinguishes token origin; device registration and validation are pool-specific, not global |
| Phone enumeration via link requests | Rate limiting on parent link request endpoint |
| `parent_phone` change on student | Existing links preserved; optionally trigger new auto-match for new phone |
| CORS for web portal | Configure allowed origins in `config/cors.php`; JWT in headers avoids cookie complexity |
| User is both student and parent | Reuse the same same-center user row when phones match, set both flags, then issue separate guard-specific tokens/sessions |

---

## Unbranded Center Rules

For **branded centers**: parent-student links are strictly center-scoped. One link per center.

For **unbranded centers** (shared Najaah org): parent linking is still center-scoped per link row,
but auto-matching searches across all unbranded centers where the student's `parent_phone` matches.
A parent with children in 3 unbranded centers gets 3 separate link rows (one per center).
This keeps authorization simple — each link is independently revocable per center.

---

## Frontend Portal Note

This plan covers the **backend API** for the student web portal and parent web portal.
The frontend is a separate project that consumes these APIs.

**Domain strategy:**
- Web portals live at `{center-slug}.najaah.me` (`.me` for portals, `.com` for admin)
- Frontend routing: `/portal/student/*` and `/portal/parent/*` (SPA handles this)
- Single SPA deployment per center subdomain — role split is frontend routing, not separate apps
- Unbranded centers: `najaah.me/portal/...` with center selection flow

**Deliverables from this plan that the frontend team needs:**
- Phase 7 produces the **frontend handoff doc** with endpoint list, auth flows, response shapes
- Phase 0B produces the **admin panel settings UI** changes (in najaah-frontend repo)
- Phase 5B produces the **admin panel parent management UI** (in najaah-frontend repo)
- Web portal SPA is a **separate frontend project** — not in scope here

---

## Notifications (Follow-Up)

Not blocking for MVP, but recommended follow-up:
- Parent link request → notify center admin (in-app or WhatsApp)
- Admin approves/rejects link → notify parent (in-app)
- Auto-link success → audit log entry (no notification needed)
