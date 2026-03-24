# Admin Parent Management API Contract

> **Status**: All endpoints implemented
> **Base URL**: `/api/v1`
> **Auth**: Bearer token (Sanctum admin session)
> **Generated**: 2026-03-24

---

## Table of Contents

1. [List Parents (Center)](#1-list-parents-center)
2. [Show Parent Detail (Center)](#2-show-parent-detail-center)
3. [Pending Link Requests (Center)](#3-pending-link-requests-center)
4. [Create Parent-Student Link](#4-create-parent-student-link)
5. [Update Parent-Student Link (Approve / Reject / Revoke)](#5-update-parent-student-link-approve--reject--revoke)
6. [List Parent Links for Student](#6-list-parent-links-for-student)
7. [Enums Reference](#enums-reference)
8. [Error Codes Reference](#error-codes-reference)

---

## 1. List Parents (Center)

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **Path** | `/centers/{center}/parents` |
| **Permission** | `parents.view` |
| **Scope** | `scope.center` |
| **Status** | Implemented |

### Query Parameters

| Param | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `search` | string | No | -- | Filters by name or phone (LIKE match) |
| `per_page` | integer | No | `15` | Results per page |

### Success Response `200`

```jsonc
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Parent Name",
      "phone": "+96512345678",
      "country_code": "KW",
      "center_id": 1,
      "is_parent": true,
      "is_student": false,
      "active_links_count": 2,
      "created_at": "2026-01-15T10:30:00.000000Z"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

---

## 2. Show Parent Detail (Center)

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **Path** | `/centers/{center}/parents/{parent}` |
| **Permission** | `parents.view` |
| **Scope** | `scope.center` |
| **Status** | Implemented |

### URL Parameters

| Param | Type | Description |
|-------|------|-------------|
| `center` | integer | Center ID |
| `parent` | integer | Parent user ID |

### Success Response `200`

```jsonc
{
  "success": true,
  "data": {
    "parent": {
      "id": 1,
      "name": "Parent Name",
      "phone": "+96512345678",
      "country_code": "KW",
      "center_id": 1,
      "is_parent": true,
      "is_student": false,
      "active_links_count": 2,
      "created_at": "2026-01-15T10:30:00.000000Z"
    },
    "links": [
      {
        "id": 10,
        "parent": {
          "id": 1,
          "name": "Parent Name",
          "phone": "+96512345678"
        },
        "student": {
          "id": 5,
          "name": "Student Name",
          "phone": "+96598765432"
        },
        "center_id": 1,
        "status": "Active",           // enum name: Active | PendingApproval | Revoked
        "link_method": "AdminManaged", // enum name: AdminManaged | AutoMatched | ParentRequested
        "linked_by": {
          "id": 3,
          "name": "Admin Name"
        },
        "linked_at": "2026-01-15T10:30:00.000000Z",
        "created_at": "2026-01-15T10:30:00.000000Z"
      }
    ]
  }
}
```

**Notes**:
- The `links` array is filtered to the given center and sorted by `created_at` descending.
- The `linked_by` field is `null` when no admin was involved (e.g. `ParentRequested` links not yet approved).

---

## 3. Pending Link Requests (Center)

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **Path** | `/centers/{center}/parents/pending-requests` |
| **Permission** | `parents.view` |
| **Scope** | `scope.center` |
| **Status** | Implemented |

### URL Parameters

| Param | Type | Description |
|-------|------|-------------|
| `center` | integer | Center ID |

### Success Response `200`

```jsonc
{
  "success": true,
  "data": [
    {
      "id": 10,
      "parent": {
        "id": 1,
        "name": "Parent Name",
        "phone": "+96512345678"
      },
      "student": {
        "id": 5,
        "name": "Student Name",
        "phone": "+96598765432"
      },
      "center_id": 1,
      "status": "PendingApproval",
      "link_method": "ParentRequested",
      "linked_by": null,
      "linked_at": "2026-01-15T10:30:00.000000Z",
      "created_at": "2026-01-15T10:30:00.000000Z"
    }
  ]
}
```

**Notes**: Returns all links with status `PendingApproval` for the given center, sorted by `created_at` descending. This is **not paginated** -- it returns all pending requests as a flat array.

---

## 4. Create Parent-Student Link

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **Path** | `/centers/{center}/parent-links` |
| **Permission** | `parents.manage` |
| **Scope** | `scope.center` |
| **Status** | Implemented |

### Request Body

| Field | Type | Required | Rules | Description |
|-------|------|----------|-------|-------------|
| `parent_user_id` | integer | Yes | `exists:users,id` | The parent user ID |
| `student_user_id` | integer | Yes | `exists:users,id` | The student user ID |

### Success Response `201`

```jsonc
{
  "success": true,
  "data": {
    "id": 10,
    "parent": {
      "id": 1,
      "name": "Parent Name",
      "phone": "+96512345678"
    },
    "student": {
      "id": 5,
      "name": "Student Name",
      "phone": "+96598765432"
    },
    "center_id": 1,
    "status": "Active",
    "link_method": "AdminManaged",
    "linked_by": null,
    "linked_at": "2026-01-15T10:30:00.000000Z",
    "created_at": "2026-01-15T10:30:00.000000Z"
  }
}
```

**Notes**: Admin-created links are set to `Active` immediately with `link_method: AdminManaged`. The `linked_by` relation is not eagerly loaded on the create response (the admin's ID is stored in `linked_by` column but the nested object will be `null`).

### Error Responses

| HTTP | Code | Condition |
|------|------|-----------|
| 422 | `LINK_ALREADY_EXISTS` | An Active or PendingApproval link already exists for this parent-student pair in this center |
| 422 | Validation error | Missing or invalid `parent_user_id` / `student_user_id` |

---

## 5. Update Parent-Student Link (Approve / Reject / Revoke)

| Field | Value |
|-------|-------|
| **Method** | `PATCH` |
| **Path** | `/centers/{center}/parent-links/{link}` |
| **Permission** | `parents.manage` |
| **Scope** | `scope.center` |
| **Status** | Implemented |

### URL Parameters

| Param | Type | Description |
|-------|------|-------------|
| `center` | integer | Center ID |
| `link` | integer | ParentStudentLink ID |

### Request Body

| Field | Type | Required | Rules | Description |
|-------|------|----------|-------|-------------|
| `action` | string | Yes | `in:approve,reject,revoke` | The action to perform |

### Action Behavior

| Action | Valid Source Status | Resulting Status | Description |
|--------|-------------------|------------------|-------------|
| `approve` | `PendingApproval` | `Active` | Approves a pending parent-requested link |
| `reject` | `PendingApproval` | `Revoked` | Rejects a pending parent-requested link |
| `revoke` | `Active` or `PendingApproval` | `Revoked` | Revokes any non-revoked link |

### Success Response `200`

```jsonc
{
  "success": true,
  "data": {
    "id": 10,
    "parent": {
      "id": 1,
      "name": "Parent Name",
      "phone": "+96512345678"
    },
    "student": {
      "id": 5,
      "name": "Student Name",
      "phone": "+96598765432"
    },
    "center_id": 1,
    "status": "Active",              // updated status
    "link_method": "ParentRequested",
    "linked_by": null,
    "linked_at": "2026-01-15T10:30:00.000000Z",
    "created_at": "2026-01-15T10:30:00.000000Z"
  }
}
```

### Error Responses

| HTTP | Code | Condition |
|------|------|-----------|
| 422 | `PARENT_LINK_NOT_FOUND` | `approve` or `reject` called on a link that is not `PendingApproval` |
| 422 | `PARENT_LINK_NOT_FOUND` | `revoke` called on a link that is already `Revoked` |
| 404 | -- | Link ID does not exist (Laravel model binding) |
| 422 | Validation error | Missing or invalid `action` value |

---

## 6. List Parent Links for Student

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **Path** | `/students/{student}/parent-links` |
| **Permission** | `parents.view` |
| **Scope** | `scope.system` |
| **Status** | Implemented |

### URL Parameters

| Param | Type | Description |
|-------|------|-------------|
| `student` | integer | Student user ID |

### Success Response `200`

```jsonc
{
  "success": true,
  "data": [
    {
      "id": 10,
      "parent": {
        "id": 1,
        "name": "Parent Name",
        "phone": "+96512345678"
      },
      "student": null,
      "center_id": 1,
      "status": "Active",
      "link_method": "AdminManaged",
      "linked_by": {
        "id": 3,
        "name": "Admin Name"
      },
      "linked_at": "2026-01-15T10:30:00.000000Z",
      "created_at": "2026-01-15T10:30:00.000000Z"
    }
  ]
}
```

**Notes**: Returns all links for the student across all centers (system scope), sorted by `created_at` descending. Not paginated. Eager-loads `parent` and `linkedByUser` relations but **not** `student` (since the student is already known from the URL, the `student` field will be `null`).

---

## Enums Reference

### ParentLinkStatus

| Name | Value (DB) | Description |
|------|-----------|-------------|
| `Active` | `0` | Link is active, parent can view student data |
| `PendingApproval` | `1` | Parent requested link, awaiting admin approval |
| `Revoked` | `2` | Link has been revoked or rejected |

### ParentLinkMethod

| Name | Value (DB) | Description |
|------|-----------|-------------|
| `AdminManaged` | `0` | Created directly by an admin |
| `AutoMatched` | `1` | System auto-linked via phone match |
| `ParentRequested` | `2` | Parent initiated the link request |

The API returns enum **names** (e.g. `"Active"`, `"AdminManaged"`), not integer values.

---

## Error Codes Reference

| Code | HTTP Status | Description |
|------|------------|-------------|
| `LINK_ALREADY_EXISTS` | 422 | An Active or PendingApproval link already exists for this parent-student-center combination |
| `PARENT_LINK_NOT_FOUND` | 422 | The link is not in the expected status for the requested action |

---

## Events Dispatched

These events fire from the service layer and may be relevant for real-time features or notification integration:

| Event | Trigger |
|-------|---------|
| `ParentLinked` | Admin creates a link (status: Active) |
| `ParentLinkApproved` | Admin approves a pending link |
| `ParentLinkRevoked` | Admin rejects or revokes a link |

---

## Source Files

| Layer | Path |
|-------|------|
| Routes | `routes/api/v1/admin/parents.php` |
| Controller | `app/Http/Controllers/Admin/ParentController.php` |
| Controller | `app/Http/Controllers/Admin/ParentLinkController.php` |
| FormRequest | `app/Http/Requests/Admin/Parents/AdminCreateLinkRequest.php` |
| FormRequest | `app/Http/Requests/Admin/Parents/AdminUpdateLinkRequest.php` |
| Resource | `app/Http/Resources/Admin/AdminParentResource.php` |
| Resource | `app/Http/Resources/Admin/AdminParentStudentLinkResource.php` |
| Service | `app/Services/Parents/ParentService.php` |
| Enums | `app/Enums/ParentLinkStatus.php`, `app/Enums/ParentLinkMethod.php` |
