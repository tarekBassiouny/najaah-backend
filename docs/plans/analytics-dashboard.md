# Feature Plan: Analytics Dashboard APIs

> Four endpoints powering the admin dashboard with cached, scoped analytics.

**Status:** Draft
**Author:** Codex
**Created:** 2026-01-31
**Last Updated:** 2026-01-31

---

## Overview

### Problem Statement
Admin users need a performant analytics dashboard. A single large endpoint risks timeouts and heavy database scans.

### Proposed Solution
Split analytics into four endpoints (overview, courses/media, learners/enrollments, devices/requests). Each endpoint:
- Accepts a shared filter set (center_id, date range, timezone).
- Uses a dedicated FormRequest + Filters DTO.
- Returns a dedicated Resource response.
- Can be cached independently.

### Success Criteria
- [ ] Each endpoint responds in < 300ms for typical center scope.
- [ ] Results are scoped by center and date range.
- [ ] Responses conform to documented schema.
- [ ] Caching reduces repeated load by > 80%.

---

## Requirements

### Functional Requirements
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-1 | Provide overview totals and active counts | Must Have |
| FR-2 | Provide center type breakdown (branded vs unbranded) | Must Have |
| FR-3 | Provide courses/media readiness and status breakdowns | Must Have |
| FR-4 | Provide learner and enrollment breakdowns | Must Have |
| FR-5 | Provide device and request breakdowns | Must Have |
| FR-6 | Support center and date-range filtering | Must Have |
| FR-7 | Use Resources for responses | Must Have |
| FR-8 | Use FormRequests + Filters DTOs for queries | Must Have |

### Non-Functional Requirements
| ID | Requirement | Metric |
|----|-------------|--------|
| NFR-1 | Performance | < 300ms per endpoint (cached) |
| NFR-2 | Caching | 5-15 min TTL per endpoint |
| NFR-3 | Security | Admin-only access |

### Out of Scope
- Real-time streaming analytics.
- Long-term historical analytics beyond request range.

---

## API Endpoints

### Shared Query Parameters (FormRequest)
All endpoints use the same query schema via FormRequest + Filters DTO.

**Query Parameters**
- `center_id` (optional): Center scope ID.
- `from` (optional): Start date (YYYY-MM-DD).
- `to` (optional): End date (YYYY-MM-DD).
- `timezone` (optional): IANA timezone; default UTC.

**Validation Rules**
- `center_id`: integer, exists:centers,id
- `from`: date
- `to`: date, after_or_equal:from
- `timezone`: string

**queryParameters() examples** (Scribe)
- `center_id`: "12"
- `from`: "2026-01-01"
- `to`: "2026-01-31"
- `timezone`: "UTC"

---

## Response Schemas (Resources)
Each endpoint returns `meta` and a data section. All responses must be wrapped by Resources.

### 1) GET /analytics/overview
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

### 2) GET /analytics/courses-media
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

### 3) GET /analytics/learners-enrollments
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

### 4) GET /analytics/devices-requests
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

---

## Validation and Filters (Finds to Validate)

### Filters DTO
- `AnalyticsFilters` DTO encapsulates: `centerId`, `from`, `to`, `timezone`.
- Each endpoint FormRequest instantiates the DTO from `validated()`.

### FormRequests
- `AnalyticsOverviewRequest`
- `AnalyticsCoursesMediaRequest`
- `AnalyticsLearnersEnrollmentsRequest`
- `AnalyticsDevicesRequestsRequest`

All FormRequests must implement:
- `rules()` for query parameters.
- `queryParameters()` for Scribe docs.
- `filters()` method returning the DTO.

---

## Performance Considerations
- Use narrow selects and group-by counts only.
- Always scope by `center_id` when provided.
- Always constrain by date range when provided.
- Add indexes for `(center_id, status, created_at)` on high-volume tables.
- Cache each endpoint response by: `center_id + from + to`.

---

## Implementation Tasks

### Phase 1: API Contracts
| Task | Description |
|------|-------------|
| 1.1 | Create Filters DTO and 4 FormRequests |
| 1.2 | Add Resources for each response |
| 1.3 | Add controller + routes |

### Phase 2: Query Logic
| Task | Description |
|------|-------------|
| 2.1 | Implement service queries per endpoint |
| 2.2 | Add caching wrappers |
| 2.3 | Add necessary indexes |

### Phase 3: Documentation
| Task | Description |
|------|-------------|
| 3.1 | Ensure Scribe docs show query params |
| 3.2 | Add examples in resources (if needed) |

