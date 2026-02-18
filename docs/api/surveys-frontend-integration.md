# Surveys Frontend Integration (Backend-Aligned)

This document defines the survey behavior currently enforced by backend code and the required frontend flows to avoid invalid payloads.

## Scope and Roles

- System routes (`/api/v1/admin/surveys*`) are for Najaah App scope.
  - Can be created/managed by system super admin.
  - Survey is always system (`scope_type=1`, `center_id=null`) regardless of payload.
  - Targets only:
    - students with `center_id = null`
- Center routes (`/api/v1/admin/centers/{center}/surveys*`) are for branded center scope.
  - Survey is always center-scoped (`scope_type=2`, `center_id={route center}`) regardless of payload.
  - Targets only students/content within the selected center.

## Assignment Types

Supported enum values:
- `all`
- `course`
- `video`
- `user`

Removed:
- `section` (frontend must not send this value)

### Allowed Matrix

- `System` surveys allow:
  - `all`
  - `course` (course must belong to an unbranded center)
  - `user` (student with `center_id = null`)
- `System` surveys reject:
  - `center`
  - `video`
  - `section`

- `Center` surveys allow:
  - `all`
  - `course` (must belong to survey center)
  - `video` (must belong to survey center)
  - `user` (student must belong to survey center)
- `Center` surveys reject:
  - `center`
  - `section`

## API Endpoints

Admin base: `/api/v1/admin`

- System scope endpoints:
  - `GET /surveys`
  - `POST /surveys`
  - `POST /surveys/bulk-close`
  - `POST /surveys/bulk-delete`
  - `POST /surveys/bulk-status`
  - `GET /surveys/{survey}`
  - `PUT /surveys/{survey}`
  - `PUT /surveys/{survey}/status`
  - `DELETE /surveys/{survey}`
  - `POST /surveys/{survey}/assign`
  - `POST /surveys/{survey}/close`
  - `GET /surveys/{survey}/analytics`
  - `GET /surveys/target-students`
- Center scope endpoints:
  - `GET /centers/{center}/surveys`
  - `POST /centers/{center}/surveys`
  - `POST /centers/{center}/surveys/bulk-close`
  - `POST /centers/{center}/surveys/bulk-delete`
  - `POST /centers/{center}/surveys/bulk-status`
  - `GET /centers/{center}/surveys/{survey}`
  - `PUT /centers/{center}/surveys/{survey}`
  - `PUT /centers/{center}/surveys/{survey}/status`
  - `DELETE /centers/{center}/surveys/{survey}`
  - `POST /centers/{center}/surveys/{survey}/assign`
  - `POST /centers/{center}/surveys/{survey}/close`
  - `GET /centers/{center}/surveys/{survey}/analytics`
  - `GET /centers/{center}/surveys/target-students`

Mobile base: `/api/v1`

- `GET /surveys/assigned`
- `GET /surveys/{survey}`
- `POST /surveys/{survey}/submit`

## New Endpoint: Target Students

System route: `GET /api/v1/admin/surveys/target-students`  
Center route: `GET /api/v1/admin/centers/{center}/surveys/target-students`

Purpose:
- Fetch only students eligible for the selected survey scope before submitting assignments.

Optional query params:
- `center_id`
  - for system route: must be null/omitted
  - for center route: optional but must match route center
- `scope_type`
  - optional
  - if sent, must match route scope (`1` for system route, `2` for center route)
- `status` (`0,1,2`)
- `search`
- `page`
- `per_page` (max `50`, recommended `20`)

Authorization rules:
- system route: system super admin only
- center route: admins can query only centers they administrate

Response shape:
- `data[]` item:
  - `id`
  - `name`
  - `username`
  - `email`
  - `phone`
  - `center_id`
  - `center { id, name, slug }` (or null)
  - `status`
  - `status_key`
  - `status_label`
- `meta`: `page`, `per_page`, `total`, `last_page`

## Required Frontend Prefetch Flow

Use these endpoints before submit:

1. `GET /api/v1/admin/auth/me`
   - detect `super_admin` and available permissions.

2. For system survey assignment (`scope_type=1`):
   - centers: `GET /api/v1/admin/centers?type=0` (unbranded only)
   - students (user assignment): `GET /api/v1/admin/surveys/target-students`
   - courses:
     - select unbranded center first
     - then `GET /api/v1/admin/centers/{center}/courses`

3. For center survey assignment (`scope_type=2`):
   - students: `GET /api/v1/admin/centers/{centerId}/surveys/target-students`
   - courses: `GET /api/v1/admin/centers/{centerId}/courses`
   - videos: `GET /api/v1/admin/centers/{centerId}/videos`

## Survey Page Listing (Pagination / Infinite Scroll)

Use paginated loading for survey page lists. Do not request bulk pages like `per_page=100`.

- Endpoint:
  - system scope: `GET /api/v1/admin/surveys`
  - center scope: `GET /api/v1/admin/centers/{center}/surveys`
- Query:
  - `page` (start at `1`)
  - `per_page` (recommended `20`, maximum `50`)
  - optional filters:
    - `is_active` (`true|false` or `1|0`)
    - `is_mandatory` (`true|false` or `1|0`)
    - `type`
    - `search` (title in EN/AR)
    - `start_from` / `start_to` (YYYY-MM-DD)
    - `end_from` / `end_to` (YYYY-MM-DD)
- Continue loading while:
  - `page <= meta.last_page`
  - or loaded count `< meta.total`

Each survey item in `data[]` includes:
- `assignments`: assignment objects (`type`, `assignable_id`, `assignable_name`)
- `responses_count`: total response rows
- `submitted_users_count`: distinct students who submitted (deduplicated by `user_id`)

Suggested infinite scroll loop:
1. Initial request with `page=1&per_page=20`.
2. Append returned `data`.
3. On scroll threshold, request `page + 1`.
4. Stop when current page reaches `meta.last_page`.

## Create/Assign Payload Rules

Assignments payload item:
- `type` required
- `id` required except `type=all`

Valid:
- `{ "type": "all" }`
- `{ "type": "course", "id": 42 }`

Invalid:
- `{ "type": "all", "id": 42 }` (ignored by business intent, FE should avoid)
- `{ "type": "section", "id": 10 }`
- System + `{ "type": "center", "id": 10 }`
- System + `{ "type": "video", "id": 10 }`
- Center + `{ "type": "center", "id": 10 }`

## Bulk Actions

- Bulk status:
  - `POST /api/v1/admin/surveys/bulk-status`
  - `POST /api/v1/admin/centers/{center}/surveys/bulk-status`
  - body: `{ "is_active": true|false, "survey_ids": [1,2,3] }`
- Bulk close:
  - `POST /api/v1/admin/surveys/bulk-close`
  - `POST /api/v1/admin/centers/{center}/surveys/bulk-close`
  - body: `{ "survey_ids": [1,2,3] }`
- Bulk delete (with safety checks):
  - `POST /api/v1/admin/surveys/bulk-delete`
  - `POST /api/v1/admin/centers/{center}/surveys/bulk-delete`
  - body: `{ "survey_ids": [1,2,3] }`
  - Safety behavior:
    - active surveys are skipped
    - surveys with responses are skipped
    - wrong-scope IDs are failed as not found

- Single delete safety (same rules as bulk delete):
  - `DELETE /api/v1/admin/surveys/{survey}`
  - `DELETE /api/v1/admin/centers/{center}/surveys/{survey}`
  - returns `422 VALIDATION_ERROR` if:
    - survey is active
    - survey has responses

## Submission and Visibility Notes (Mobile)

- `GET /surveys/assigned` returns only one highest-priority survey.
- Priority order:
  - mandatory first
  - earliest deadline first
  - newest created first
- Video assignment eligibility requires playback with:
  - same `video_id`
  - `is_full_play=true`
  - course center matching student center
- Multiple submissions are still blocked by current response logic even if `allow_multiple_submissions=true`.

## Frontend Safety Checklist

- Never send `section` assignment type.
- Always coerce assignment IDs to integer.
- Do not render invalid type/scope combinations in UI.
- Always scope student picker by survey scope using `/surveys/target-students`.
- For system scope, only allow Najaah app students (`center_id=null`) in selectors.
- For center scope, force center-bound lists (`students`, `courses`, `videos`) by selected center.
- Use `/surveys/{id}/status` and `/surveys/bulk-status` to toggle `is_active` without editing other fields.
- Use paginated loading (`per_page=20`) for survey-page and target-student lists.
- Treat 422 as validation error and show field messages.
