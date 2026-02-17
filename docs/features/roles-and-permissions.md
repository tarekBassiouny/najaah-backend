---
title: Roles & Permissions (Admin)
---

# Roles & Permissions (Admin)

This page summarizes the backend contract for role/permission management so the frontend can validate its forms and keep the admin console aligned with the platform rules.

## Key principles

- **Permissions are shared across system and center scopes** — there is no separate permission set per scope. Every role exposes the same permission identifiers regardless of whether the admin is a system super admin (`center_id = null`) or a center-scoped admin (`center_id` numeric).
- **Role writes are system-scoped only.** Creating, updating, deleting, or syncing permissions requires the `role.manage` permission plus the `scope.system_admin` route scope. Reads (`GET /roles`, `GET /roles/{role}`) only require `role.manage`.
- **Permissions themselves are free-floating** and only need `require.permission:permission.view`.

## Endpoints & scopes

| Purpose | Method | Path | Scope/Middleware |
|---------|--------|------|------------------|
| List roles | `GET` | `/api/v1/admin/roles` | `require.permission:role.manage` |
| Show role | `GET` | `/api/v1/admin/roles/{role}` | `require.permission:role.manage` |
| Create role | `POST` | `/api/v1/admin/roles` | `require.permission:role.manage`, `scope.system_admin` |
| Update role | `PUT` | `/api/v1/admin/roles/{role}` | `require.permission:role.manage`, `scope.system_admin` |
| Delete role | `DELETE` | `/api/v1/admin/roles/{role}` | `require.permission:role.manage`, `scope.system_admin` |
| Sync permissions | `PUT` | `/api/v1/admin/roles/{role}/permissions` | `require.permission:role.manage`, `scope.system_admin` |
| Bulk sync permissions | `POST` | `/api/v1/admin/roles/permissions/bulk` | `require.permission:role.manage`, `scope.system_admin` |
| List permissions | `GET` | `/api/v1/admin/permissions` | `require.permission:permission.view` |

Each write endpoint must pass the system API key (`X-Api-Key`) tied to the system scope; center API keys are invalid because `scope.system_admin` enforces global access.

## Permission catalog

All modules share the same permission catalog whether the admin is system scoped or center scoped. These names are seeded via `PermissionSeeder` so you can seed/update them consistently:

| Permission | Module | Description |
|------------|--------|-------------|
| `admin.manage` | Admin users | Manage creation/deletion of admin accounts |
| `role.manage` | Roles | Create/update/delete roles and assign permissions |
| `permission.view` | Roles | Read the full permission catalog |
| `course.manage` | Courses | Manage course CRUD |
| `course.publish` | Courses | Publish ready courses |
| `section.manage` | Sections | CRUD sections inside a course |
| `analytics.manage` | Analytics | Manage analytics dashboards |
| `video.manage` | Videos | Manage videos |
| `video.upload` | Videos | Authorize uploads |
| `video.playback.override` | Playback | Override playback constraints (debug) |
| `pdf.manage` | PDFs | Manage PDFs attached to courses/sections |
| `enrollment.manage` | Enrollments | Manage student enrollments |
| `center.manage` | Centers | Create and configure centers |
| `settings.manage` | Settings | Manage system or center settings |
| `settings.view` | Settings | View configured settings |
| `student.manage` | Students | Manage student accounts |
| `survey.manage` | Surveys | CRUD surveys and related metadata |
| `audit.view` | Analytics | Read audit logs and analytics dashboards |
| `notification.manage` | Notifications | Manage admin notifications |
| `device_change.manage` | Device change requests | Approve/reject device-change flows |
| `extra_view.manage` | Extra views | Manage extra view requests |
| `instructor.manage` | Instructors | Manage instructor profiles |
| `agent.execute` | Agents | Execute general agents |
| `agent.content_publishing` | Agents | Execute content publishing agents |
| `agent.enrollment.bulk` | Agents | Execute bulk enrollment agents |

Because the permissions behave identically for `scope.system_admin` and `scope.center_route`, reuse the same checkbox/grid for both contexts. Controls that mutate roles or permissions should remain hidden unless the system API key grants `scope.system_admin`.

## Request validation (frontend checklist)

### 1. Role create (`POST /roles`)

- **`name_translations`**: required object, must include `en` string ≤ 100 chars; `ar` optional. Provide both keys when available.
- **`slug`**: required, unique string up to 100 chars. Frontend should warn if the slug already exists (the API responds with `VALIDATION_ERROR` otherwise).
- **`description_translations`**: optional object with `en`/`ar` strings of max 255 chars.

### 2. Role update (`PUT /roles/{role}`)

- Fields are optional but must follow the same format as creation. Use `slug` only when updating the identifier; the backend ignores falsy slug changes if missing.
- Provide at least one translation when updating `name_translations`; removing both `en` and `ar` results in validation errors.

### 3. Sync permissions (`PUT /roles/{role}/permissions`)

- **`permission_ids`**: must be present (send `[]` when clearing permissions).
- Each entry must be a distinct integer matching an existing `permissions.id`. The API will throw `VALIDATION_ERROR` if the permission is missing or duplicate.

### 4. Bulk sync permissions (`POST /roles/permissions/bulk`)

- **`role_ids`**: required array with at least one entry. Each role ID must exist and be unique.
- **`permission_ids`**: required array; each entry must be a distinct existing `permissions.id`.
- The endpoint applies the same permission set to every role in one transaction. The response includes `{ roles: [ids], permission_ids: [ids] }`.

### 4. List permissions (`GET /permissions`)

- Returns `{ id, name, description }` for every permission. Use this list to render checkboxes or toggles when syncing permissions to avoid invalid IDs.

## Response contracts (use for UI)

- `RoleResource` returns:
  - `id`, `name` (localized via `name_translations.en`/`ar`), `slug`, `description`
  - `name_translations`, `description_translations`
  - `permissions`: array of permission `name`s (not IDs) — ideal for display.
- `PermissionResource` returns `{ id, name, description }` for populating the permission grid.

## Frontend guidance

1. **Validation flow**
   - Enforce the same rules client-side before submitting: required English name, slug uniqueness (cross-check via existing list), and proper translation structure.
   - When submitting permissions, only send IDs returned from `/permissions`. Send `permission_ids` as `present` even if empty (e.g., `{ permission_ids: [] }`).
   - Use `POST /roles/permissions/bulk` whenever you need to assign the same permission set to multiple roles; send `role_ids` and `permission_ids` together and show validation errors if either array is invalid.

2. **Permissions are identical for system vs. center UI**
   - The backend does not split permissions between scopes, so reuse the same permission grid for both system and center admin flows. Just honor `scope.system_admin` for writes (the frontend should hide create/update/delete buttons when the current API key lacks that scope).

3. **Error handling**
   - `VALIDATION_ERROR` responses include `details` keyed by the input (`name_translations.en`, `slug`, `permission_ids.*`). Surface those messages next to each field.
   - The API wraps every error in `{ success: false, error: { code, message, details } }`.

4. **Previewing role data**
   - Use `GET /roles` (paginated) to populate lists. The `meta` object includes pagination info; there is no separate center filter (all roles are system-level `is_admin_role = true`).

## Next steps

1. If you need field-level hints in the UI (tooltip guidance on translations or slug), base them on the validation rules above.
2. Align permission toggles with `/permissions` output; the backend enforces `distinct`+`exists`, so preventing duplicate IDs avoids extra API errors.
3. Any additional front-end validation should focus on translation completeness and slug uniqueness; the backend already enforces permissions presence/enumeration.
4. When verifying permissions for a module, cross-reference the catalog above (seeded in `PermissionSeeder`). The same names are valid for both system and center admins, so the frontend can re-use the same permission grid—just hide mutation buttons when the API key lacks `scope.system_admin`.
