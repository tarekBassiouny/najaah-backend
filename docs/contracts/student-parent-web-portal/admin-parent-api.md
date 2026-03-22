# Student & Parent Web Portal — Admin Parent API Contract

## Status: Draft
## Source Phase: 5A — Admin Parent Management API
## Audience: Admin frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the admin API surface for listing parents, reviewing pending link requests, and creating or revoking parent-student links.

---

## Permissions

Expected permission split:
- `parents.view`
- `parents.manage`

---

## Expected Endpoints

| Method | Path | Purpose | Status |
|--------|------|---------|--------|
| `GET` | `/api/v1/admin/centers/{center}/parents` | List parents in a center | planned |
| `GET` | `/api/v1/admin/centers/{center}/parents/{parent}` | Parent detail | planned |
| `POST` | `/api/v1/admin/centers/{center}/parent-links` | Create a parent-student link | planned |
| `PATCH` | `/api/v1/admin/centers/{center}/parent-links/{link}` | Approve, reject, or revoke a link | planned |
| `GET` | `/api/v1/admin/centers/{center}/parent-links/pending` | List pending link requests | planned |
| `GET` | `/api/v1/admin/students/{student}/parent-links` | List links for one student | planned |

---

## Business Rules

- Admin-created links become active immediately.
- Pending parent requests can be approved, rejected, or revoked.
- Updating a student's `parent_phone` does not break existing links automatically.
- A student detail response should expose parent link data when loaded.

---

## Frontend Needs

- list view filters
- parent detail summary
- link status values
- bulk or inline actions for approve/reject/revoke
- clear authorization failures when a role lacks `parents.view` or `parents.manage`

---

## Open Items

- Confirm exact resource shapes for parent list rows and student detail link summaries.
- Confirm whether pending requests need pagination separate from main parent list.
- Confirm whether admin UI needs link history in MVP or only current state.
