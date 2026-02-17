# Admin Routing Scope Contract

This document defines the canonical admin routing and scope rules for Najaah LMS.

## Scope Identity

- `super_admin` + `center_id = null` => **system super admin**
- `super_admin` + `center_id != null` => **center-scoped super admin**
- `center_id != null` (non-super-admin) => **center-scoped admin**

## Routing Rules

- Global/system modules use `/api/v1/admin/*`
- Center-owned modules use `/api/v1/admin/centers/{center}/*`
- No mixed duplicates for center-owned modules
- Route `{center}` is authoritative for center-owned endpoints

## Security Rules

- Center-scoped admins can access only `/centers/{their_center_id}/...`
- Center mismatch returns `403 CENTER_MISMATCH`
- Entity mismatch inside a center route returns `404 NOT_FOUND`
- Client-provided `center_id` is ignored for center-scoped writes where route center exists
- System super admin can access global modules and any center route
- API key scope is enforced:
  - system modules require system API key (`resolved_center_id = null`)
  - center API key can access only `/centers/{same_center_id}/...`
  - center API key cannot access system modules
  - login/session endpoints also validate admin-vs-api-key center compatibility

## Global Modules (System Scope)

- `/api/v1/admin/analytics/*`
- `/api/v1/admin/audit-logs`
- `/api/v1/admin/centers*` (registry/CRUD)
- `/api/v1/admin/users*` (system-scope admin user management)
- `/api/v1/admin/settings/preview`
- `/api/v1/admin/agents/*`

## Roles and Admins

- Roles and permissions stay globally defined.
- Center-scoped super admin is allowed to:
  - read roles and permissions
  - assign roles to admins in the same center scope
- Role/permission mutations are system-scope only.
- Admin user management is dual-scoped:
  - system scope:
    - `GET /api/v1/admin/users`
    - `POST /api/v1/admin/users`
    - `PUT /api/v1/admin/users/{user}`
    - `DELETE /api/v1/admin/users/{user}`
    - `PUT /api/v1/admin/users/{user}/roles`
  - center scope:
    - `GET /api/v1/admin/centers/{center}/users`
    - `POST /api/v1/admin/centers/{center}/users`
    - `PUT /api/v1/admin/centers/{center}/users/{user}`
    - `DELETE /api/v1/admin/centers/{center}/users/{user}`
    - `PUT /api/v1/admin/centers/{center}/users/{user}/roles`

## Students

### Global (system super admin only)

- `GET /api/v1/admin/students`
- `POST /api/v1/admin/students`
- `PUT /api/v1/admin/students/{user}`
- `DELETE /api/v1/admin/students/{user}`
- `POST /api/v1/admin/students/bulk-status`
- `GET /api/v1/admin/students/{user}/profile`

`center_id` is nullable on global create/update semantics:
- `center_id = null` => Najaah App (shared/unbranded student pool)
- `center_id = X` => branded center student

### Center Path

- `GET /api/v1/admin/centers/{center}/students`
- `POST /api/v1/admin/centers/{center}/students`
- `PUT /api/v1/admin/centers/{center}/students/{user}`
- `DELETE /api/v1/admin/centers/{center}/students/{user}`
- `POST /api/v1/admin/centers/{center}/students/bulk-status`
- `GET /api/v1/admin/centers/{center}/students/{user}/profile`

## Surveys

Surveys are split by route scope:

### System Scope (Najaah App)

- `GET /api/v1/admin/surveys`
- `POST /api/v1/admin/surveys`
- `POST /api/v1/admin/surveys/bulk-close`
- `POST /api/v1/admin/surveys/bulk-delete`
- `POST /api/v1/admin/surveys/bulk-status`
- `GET /api/v1/admin/surveys/{survey}`
- `PUT /api/v1/admin/surveys/{survey}`
- `PUT /api/v1/admin/surveys/{survey}/status`
- `DELETE /api/v1/admin/surveys/{survey}`
- `POST /api/v1/admin/surveys/{survey}/assign`
- `POST /api/v1/admin/surveys/{survey}/close`
- `GET /api/v1/admin/surveys/{survey}/analytics`
- `GET /api/v1/admin/surveys/target-students`

Rules:
- system routes manage **system surveys only** (`scope_type=system`, `center_id=null`)
- system target students include only Najaah App students (`center_id=null`)
- `center_id` filter is not allowed on system target-students (must be null/omitted)
- system assignments allow `all`, `user` (student `center_id=null`), and `course` (course in unbranded center)
- system assignments reject `center` and `video`

### Center Scope (Branded Center App)

- `GET /api/v1/admin/centers/{center}/surveys`
- `POST /api/v1/admin/centers/{center}/surveys`
- `POST /api/v1/admin/centers/{center}/surveys/bulk-close`
- `POST /api/v1/admin/centers/{center}/surveys/bulk-delete`
- `POST /api/v1/admin/centers/{center}/surveys/bulk-status`
- `GET /api/v1/admin/centers/{center}/surveys/{survey}`
- `PUT /api/v1/admin/centers/{center}/surveys/{survey}`
- `PUT /api/v1/admin/centers/{center}/surveys/{survey}/status`
- `DELETE /api/v1/admin/centers/{center}/surveys/{survey}`
- `POST /api/v1/admin/centers/{center}/surveys/{survey}/assign`
- `POST /api/v1/admin/centers/{center}/surveys/{survey}/close`
- `GET /api/v1/admin/centers/{center}/surveys/{survey}/analytics`
- `GET /api/v1/admin/centers/{center}/surveys/target-students`

Rules:
- center routes manage **center surveys only** (`scope_type=center`, `center_id={route center}`)
- any `scope_type`/`center_id` payload that conflicts with route scope is rejected
- survey entity mismatch with route center returns `404 NOT_FOUND`

## Center-Owned Canonical Modules

- Courses:
  - `/api/v1/admin/centers/{center}/courses*`
  - includes clone/publish and attachments (videos/pdfs)
- Sections:
  - `/api/v1/admin/centers/{center}/courses/{course}/sections*`
- Videos:
  - `/api/v1/admin/centers/{center}/videos*`
- PDFs:
  - `/api/v1/admin/centers/{center}/pdfs*`
- Categories:
  - `/api/v1/admin/centers/{center}/categories*`
- Instructors:
  - `/api/v1/admin/centers/{center}/instructors*`
  - `/api/v1/admin/centers/{center}/courses/{course}/instructors*`
- Enrollments:
  - `/api/v1/admin/centers/{center}/enrollments*`
- Device change requests:
  - `/api/v1/admin/centers/{center}/device-change-requests*`
  - `/api/v1/admin/centers/{center}/students/{student}/device-change-requests`
- Extra view requests:
  - `/api/v1/admin/centers/{center}/extra-view-requests*`
- Surveys:
  - `/api/v1/admin/centers/{center}/surveys*`
- Audit logs:
  - `/api/v1/admin/centers/{center}/audit-logs`
- Admin users:
  - `/api/v1/admin/centers/{center}/users*`

## Auth Contract

`GET /api/v1/admin/auth/me` response includes:

- `scope_type`: `system | center`
- `scope_center_id`: `number | null`
- `is_system_super_admin`: `boolean`
- `is_center_super_admin`: `boolean`

## Migration Policy

- Hard cut policy is active.
- Legacy mixed admin endpoints for center-owned modules are removed.
