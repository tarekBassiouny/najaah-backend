# Admin API Response Contract

This document is the canonical frontend/backend contract for all `/api/v1/admin/*` endpoints.

## Contract Goals

- Every admin API response must have a predictable shape.
- Frontend can always read toast text from `message`.
- Frontend can always branch by `success` and `code`.

## Success Response Shape

All successful responses (`2xx`) include:

- `success: true`
- `message: string`
- `data: mixed | null`

Optional endpoint fields can still exist (example: `meta`), but the three fields above are always present.

Example:

```json
{
  "success": true,
  "message": "Created successfully.",
  "data": {
    "id": 2,
    "title": "Intro"
  }
}
```

## Error Response Shape

All failed responses (`4xx`/`5xx`) include:

- `success: false`
- `message: string`
- `code: string`
- `errors: object | null`
- `data: null`

For backward compatibility during migration, `error` is still present:

- `error.code`
- `error.message`
- `error.details` (same as `errors`)

Example (validation):

```json
{
  "success": false,
  "message": "Validation failed.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "title": [
      "The title field is required."
    ]
  },
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed.",
    "details": {
      "title": [
        "The title field is required."
      ]
    }
  }
}
```

## 204 Migration Policy

`204 No Content` is not used for admin actions anymore because it cannot carry toast messages.

Delete/approve/reject/bulk action style endpoints now return:

- HTTP `200`
- `success: true`
- `message: string`
- `data: null`

Example:

```json
{
  "success": true,
  "message": "Deleted successfully.",
  "data": null
}
```

## Frontend Integration Notes

- Always use `message` for toasts and user feedback.
- Success branch: `if (response.success)`.
- Error branch: use `code` for logic and `errors` for field-level form messages.
- Do not depend on `204` for admin endpoints.

