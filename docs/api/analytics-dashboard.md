# Analytics Dashboard APIs

This document defines the request filters and response schemas for the analytics dashboard endpoints.

## Shared Query Filters

All endpoints accept the same query parameters.
All counts are computed within the requested date range (default last 30 days).

### Query Parameters
- `center_id` (optional): Center scope ID.
- `from` (optional): Start date (YYYY-MM-DD).
- `to` (optional): End date (YYYY-MM-DD).
- `timezone` (optional): IANA timezone string (default: UTC).

### Validation Rules
- `center_id`: integer, exists:centers,id
- `from`: date
- `to`: date, after_or_equal:from
- `timezone`: string

## Responses

### GET /analytics/overview
```json
{
  "meta": {
    "range": { "from": "2026-01-01", "to": "2026-01-31" },
    "center_id": 12,
    "timezone": "UTC",
    "generated_at": "2026-01-31T10:30:00Z"
  },
  "overview": {
    "total_centers": 120,
    "active_centers": 95,
    "centers_by_type": { "unbranded": 50, "branded": 70 },
    "total_courses": 640,
    "published_courses": 410,
    "total_enrollments": 24800,
    "active_enrollments": 17320,
    "daily_active_learners": 842
  }
}
```

### GET /analytics/courses-media
```json
{
  "meta": { "range": { "from": "2026-01-01", "to": "2026-01-31" }, "center_id": 12, "timezone": "UTC", "generated_at": "2026-01-31T10:30:00Z" },
  "courses": {
    "by_status": { "draft": 120, "uploading": 40, "ready": 70, "published": 410, "archived": 110 },
    "ready_to_publish": 350,
    "blocked_by_media": 60,
    "top_by_enrollments": [
      { "course_id": 91, "title": "Course X", "enrollments": 420 }
    ]
  },
  "media": {
    "videos": {
      "total": 5200,
      "by_upload_status": { "pending": 40, "uploading": 30, "processing": 90, "ready": 4980, "failed": 60 },
      "by_lifecycle_status": { "pending": 80, "processing": 70, "ready": 5050 }
    },
    "pdfs": {
      "total": 900,
      "by_upload_status": { "pending": 10, "processing": 20, "ready": 870 }
    }
  }
}
```

### GET /analytics/learners-enrollments
```json
{
  "meta": { "range": { "from": "2026-01-01", "to": "2026-01-31" }, "center_id": 12, "timezone": "UTC", "generated_at": "2026-01-31T10:30:00Z" },
  "learners": {
    "total_students": 21000,
    "active_students": 15400,
    "new_students": 320,
    "by_center": [
      { "center_id": 12, "students": 840 }
    ]
  },
  "enrollments": {
    "by_status": { "active": 17320, "pending": 120, "deactivated": 920, "cancelled": 440 },
    "top_courses": [
      { "course_id": 91, "title": "Course X", "enrollments": 420 }
    ]
  }
}
```

### GET /analytics/devices-requests
```json
{
  "meta": { "range": { "from": "2026-01-01", "to": "2026-01-31" }, "center_id": 12, "timezone": "UTC", "generated_at": "2026-01-31T10:30:00Z" },
  "devices": {
    "total": 19000,
    "active": 15400,
    "revoked": 3200,
    "pending": 400,
    "changes": {
      "pending": 45,
      "approved": 210,
      "rejected": 30,
      "pre_approved": 12,
      "by_source": { "mobile": 160, "otp": 70, "admin": 55 }
    }
  },
  "requests": {
    "extra_views": {
      "pending": 40,
      "approved": 300,
      "rejected": 25,
      "approval_rate": 0.88,
      "avg_decision_hours": 6.4
    },
    "enrollment": {
      "pending": 120,
      "approved": 980,
      "rejected": 45
    }
  }
}
```
