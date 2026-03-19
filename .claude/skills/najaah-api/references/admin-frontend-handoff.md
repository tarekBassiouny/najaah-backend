# Admin Frontend Handoff

Use this reference when the frontend needs exact backend contracts. Always separate `system` endpoints from `center` endpoints and call out missing backend support directly.

## Scope Model
- System scope routes live under `/api/v1/admin/...`
- Center scope routes live under `/api/v1/admin/centers/{center}/...`
- System super admin: `super_admin` role with `center_id = null`
- Center-scoped admin: tied to a specific `center_id`
- For section APIs, center context comes from the route path only
- Center type enum: `0 = unbranded`, `1 = branded`
- Survey scope enum: `1 = system`, `2 = center`

## Response Format For Frontend Answers
1. Module and scope
2. Endpoint list
3. Required params and optional filters
4. Response fields needed by the UI
5. Permission or middleware constraints
6. Gaps or backend TODOs

## Module Map

### Dashboard
- Primary APIs:
  - `GET /api/v1/admin/analytics/overview`
  - `GET /api/v1/admin/analytics/learners-enrollments`
  - `GET /api/v1/admin/analytics/courses-media`
  - `GET /api/v1/admin/analytics/devices-requests`
- Shared filters: `center_id`, `from`, `to`, `timezone`
- Key output: branded and unbranded split in `overview.centers_by_type`

### Analysis
- Primary APIs:
  - `GET /api/v1/admin/analytics/overview`
  - `GET /api/v1/admin/analytics/courses-media`
  - `GET /api/v1/admin/analytics/learners-enrollments`
  - `GET /api/v1/admin/analytics/devices-requests`
  - `GET /api/v1/admin/analytics/students`
- Shared filters: `center_id`, `from`, `to`, `timezone`
- Student analysis requires `student_id` and enforces center match

### Centers
- `GET /api/v1/admin/centers`
- `POST /api/v1/admin/centers`
- `GET /api/v1/admin/centers/{center}`
- `PUT /api/v1/admin/centers/{center}`
- `DELETE /api/v1/admin/centers/{center}`
- `POST /api/v1/admin/centers/{center}/restore`
- Filters: `slug`, `type`, `tier`, `is_featured`, `onboarding_status`, `search`, `created_from`, `created_to`, `page`, `per_page`

### Sections
- `GET /api/v1/admin/centers/{center}/courses/{course}/sections`
- `POST /api/v1/admin/centers/{center}/courses/{course}/sections`
- `PUT /api/v1/admin/centers/{center}/courses/{course}/sections/reorder`
- `GET /api/v1/admin/centers/{center}/courses/{course}/sections/{section}`
- `PUT /api/v1/admin/centers/{center}/courses/{course}/sections/{section}`
- `DELETE /api/v1/admin/centers/{center}/courses/{course}/sections/{section}`
- `POST /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/restore`
- `PATCH /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/visibility`
- `POST /api/v1/admin/centers/{center}/courses/{course}/sections/structure`
- `PUT /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/structure`
- `DELETE /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/structure`
- Videos:
  - `GET /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/videos`
  - `POST /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/videos`
  - `DELETE /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/videos/{video}`
- PDFs:
  - `GET /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/pdfs`
  - `POST /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/pdfs`
  - `DELETE /api/v1/admin/centers/{center}/courses/{course}/sections/{section}/pdfs/{pdf}`
- Middleware: `scope.center` plus `require.permission:section.manage`
- Do not pass `center_id` in body or query for section endpoints

### Surveys
- System scope: `/api/v1/admin/surveys...`
- Center scope: `/api/v1/admin/centers/{center}/surveys...`
- Includes CRUD, assign, close, analytics, target-students, and bulk actions
- Filters: `is_active`, `is_mandatory`, `type`, `search`, `start_from`, `start_to`, `end_from`, `end_to`, `page`, `per_page`
- System survey targeting supports Najaah app students only

### Agents
- `GET /api/v1/admin/agents/available`
- `GET /api/v1/admin/agents/executions`
- `GET /api/v1/admin/agents/executions/{agentExecution}`
- `POST /api/v1/admin/agents/execute`
- `POST /api/v1/admin/agents/content-publishing/execute`
- `POST /api/v1/admin/agents/enrollment/bulk`
- Execution requires `center_id` and optional `context`

### Roles and Permissions
- Roles:
  - `GET /api/v1/admin/roles`
  - `GET /api/v1/admin/roles/{role}`
  - `POST /api/v1/admin/roles`
  - `PUT /api/v1/admin/roles/{role}`
  - `DELETE /api/v1/admin/roles/{role}`
  - `PUT /api/v1/admin/roles/{role}/permissions`
- Permissions:
  - `GET /api/v1/admin/permissions`
- Role writes are system-scope only

### Admin Users
- System scope:
  - `GET/POST/PUT/DELETE /api/v1/admin/users...`
  - `PUT /api/v1/admin/users/{user}/status`
  - `POST /api/v1/admin/users/bulk-status`
  - `PUT /api/v1/admin/users/{user}/roles`
  - `POST /api/v1/admin/users/roles/bulk`
  - `PUT /api/v1/admin/users/{user}/assign-center`
  - `POST /api/v1/admin/users/assign-center/bulk`
- Center scope:
  - `GET/POST/PUT/DELETE /api/v1/admin/centers/{center}/users...`
  - `PUT /api/v1/admin/centers/{center}/users/{user}/status`
  - `POST /api/v1/admin/centers/{center}/users/bulk-status`
  - `PUT /api/v1/admin/centers/{center}/users/{user}/roles`
  - `POST /api/v1/admin/centers/{center}/users/roles/bulk`
- Invite-only create flow:
  - no `password` on create
  - `force_password_reset = true`
  - reset or invitation email is queued
- List filters: `center_id`, `search`, `role_id`, `page`, `per_page`
- Profile endpoints:
  - `GET /api/v1/admin/auth/me`
  - `POST /api/v1/admin/auth/change-password`
  - `POST /api/v1/admin/auth/password/forgot`
  - `POST /api/v1/admin/auth/password/reset`

### Students
- System scope:
  - `GET/POST/PUT/DELETE /api/v1/admin/students...`
  - `GET /api/v1/admin/students/{user}/profile`
  - `POST /api/v1/admin/students/bulk-status`
- Center scope:
  - `GET/POST/PUT/DELETE /api/v1/admin/centers/{center}/students...`
  - `GET /api/v1/admin/centers/{center}/students/{user}/profile`
  - `POST /api/v1/admin/centers/{center}/students/bulk-status`
- List filters: `center_id`, `status`, `search`, `page`, `per_page`

### Student Requests
- Enrollments
  - System: `GET/POST/PUT/DELETE /api/v1/admin/enrollments...`, plus bulk create and bulk status
  - Center: `GET/POST/PUT/DELETE /api/v1/admin/centers/{center}/enrollments...`, plus bulk create and bulk status
  - Filters: `center_id`, `course_id`, `user_id`, `status`, `date_from`, `date_to`, `page`, `per_page`
- Extra view requests
  - System: list, approve, reject, bulk approve, bulk reject
  - Center: list, approve, reject, bulk approve, bulk reject
  - Filters: `center_id`, `status`, `user_id`, `course_id`, `video_id`, `decided_by`, `date_from`, `date_to`, `page`, `per_page`
- Device change requests
  - System and center scope variants for list, approve, reject, pre-approve, create-for-student, and bulk actions
  - Filters: `center_id`, `status`, `user_id`, `request_source`, `decided_by`, `current_device_id`, `new_device_id`, `date_from`, `date_to`, `page`, `per_page`

### Settings
- Implemented:
  - `GET /api/v1/admin/centers/{center}/settings`
  - `PATCH /api/v1/admin/centers/{center}/settings`
  - `GET /api/v1/admin/settings/preview`
- Current gap: platform-level settings CRUD is not implemented

### Audit Log
- `GET /api/v1/admin/audit-logs`
- `GET /api/v1/admin/centers/{center}/audit-logs`
- Filters: `center_id`, `course_id`, `entity_type`, `entity_id`, `action`, `user_id`, `date_from`, `date_to`, `page`, `per_page`
