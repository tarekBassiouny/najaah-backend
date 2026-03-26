# Student & Parent Web Portal — Parent Web API Contract

## Status: Implemented
## Source Phase: 4 — Parent Web Portal
## Audience: Portal frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the read-only parent portal API for linked-student visibility, progress, quiz review, assignments, and activity.

---

## Auth

- Guard: `jwt.web.parent` middleware on all routes
- Center scoping: resolved via `resolved_center_id` request attribute (set by `ResolveCenterApiKey` middleware)
- Parent authorization always depends on an **active** `parent_student_links` row (`status = Active`)
- Every endpoint that accepts `{student}` calls `assertLinkedStudent()` which throws `403 STUDENT_NOT_LINKED` if no active link exists

---

## Base URL

All routes are prefixed with `/api/v1/web`.

---

## Endpoint Summary

| Method | Path | Purpose | Status |
|--------|------|---------|--------|
| `GET` | `/api/v1/web/students` | List linked students | implemented |
| `GET` | `/api/v1/web/students/{student}` | View linked student detail | implemented |
| `GET` | `/api/v1/web/links` | View all links (including pending) | implemented |
| `POST` | `/api/v1/web/links` | Request a new link to a student | implemented |
| `GET` | `/api/v1/web/students/{student}/enrollments` | Student enrollments | implemented |
| `GET` | `/api/v1/web/students/{student}/courses/{course}/progress` | Course progress summary | implemented |
| `GET` | `/api/v1/web/students/{student}/courses/{course}/quiz-attempts` | Quiz attempts for a course | implemented |
| `GET` | `/api/v1/web/quiz-attempts/{attempt}` | Full quiz attempt review | implemented |
| `GET` | `/api/v1/web/students/{student}/courses/{course}/assignments` | Assignment submissions for a course | implemented |
| `GET` | `/api/v1/web/students/{student}/centers/{center}/activity/weekly` | Weekly activity summary | implemented |

---

## Endpoints

### 1. List Linked Students

```
GET /api/v1/web/students
```

Returns only **active** links for the authenticated parent.

**Response `200`**

```json
{
  "success": true,
  "data": [
    {
      "link_id": 1,
      "student_id": 42,
      "name": "Ahmed",
      "phone": "+966500000001",
      "status": "Active",
      "link_method": "AutoMatched",
      "linked_at": "2026-03-01T12:00:00.000000Z"
    }
  ]
}
```

**Notes**
- Filters by `center_id` when the request resolves to a branded center.
- Only returns links with `status = Active`.

---

### 2. View Linked Student Detail

```
GET /api/v1/web/students/{student}
```

**Path parameters**
- `student` (integer, required) — student user ID

**Response `200`**

```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Ahmed",
    "phone": "+966500000001",
    "center": {
      "id": 1,
      "name": "Center Name"
    },
    "grade": {
      "id": 5,
      "name": "Grade 10"
    },
    "school": {
      "id": 3,
      "name": "School Name"
    }
  }
}
```

**Notes**
- `center`, `grade`, and `school` can each be `null` if not set on the student.
- Loads relationships: `center`, `grade`, `school`, `college`.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | No active link between parent and student |

---

### 3. View All Links

```
GET /api/v1/web/links
```

Returns **all** links for the parent (active, pending, revoked) — not filtered by status.

**Response `200`**

```json
{
  "success": true,
  "data": [
    {
      "link_id": 1,
      "student_id": 42,
      "name": "Ahmed",
      "phone": "+966500000001",
      "status": "PendingApproval",
      "link_method": "ParentRequested",
      "linked_at": "2026-03-20T10:00:00.000000Z"
    }
  ]
}
```

**`status` enum values**: `Active`, `PendingApproval`, `Revoked`

**`link_method` enum values**: `AdminManaged`, `AutoMatched`, `ParentRequested`

---

### 4. Request a New Link

```
POST /api/v1/web/links
```

**Rate limit**: 5 requests per 60 minutes.

**Request body** (validated by `RequestLinkRequest`)

```json
{
  "student_phone": "+966500000001"
}
```

| Field | Type | Rules |
|-------|------|-------|
| `student_phone` | string | required, max 20 chars |

**Response `201`**

```json
{
  "success": true,
  "data": {
    "link_id": 5,
    "student_id": 42,
    "name": "Ahmed",
    "phone": "+966500000001",
    "status": "PendingApproval",
    "link_method": "ParentRequested",
    "linked_at": "2026-03-24T08:00:00.000000Z"
  }
}
```

**Notes**
- Link is created with `status = PendingApproval` and requires admin approval before the parent can view student data.
- Dispatches `ParentLinkRequested` event.
- Creates an audit log entry.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 404 | No student found with this phone number |
| `LINK_ALREADY_EXISTS` | 422 | An active or pending link already exists for this student |

---

### 5. Student Enrollments

```
GET /api/v1/web/students/{student}/enrollments
```

**Path parameters**
- `student` (integer, required) — student user ID

**Response `200`**

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "course": {
        "id": 3,
        "title": "Mathematics 101",
        "thumbnail": "https://cdn.example.com/thumb.jpg"
      },
      "status": "Active",
      "enrolled_at": "2026-01-15T10:00:00.000000Z",
      "expires_at": "2026-06-15T10:00:00.000000Z"
    }
  ]
}
```

**Notes**
- Only returns active, non-deleted enrollments.
- `course` can be `null` if the course was deleted.
- `expires_at` can be `null` for non-expiring enrollments.
- `title` is locale-resolved from `title_translations`.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | No active link between parent and student |

---

### 6. Course Progress Summary

```
GET /api/v1/web/students/{student}/courses/{course}/progress
```

**Path parameters**
- `student` (integer, required) — student user ID
- `course` (integer, required) — course ID

**Response `200`**

```json
{
  "success": true,
  "data": {
    "quizzes": {
      "total": 5,
      "completed": 3,
      "passed": 2,
      "required": 4,
      "required_passed": 2
    },
    "assignments": {
      "total": 3,
      "completed": 2,
      "passed": 1,
      "required": 2,
      "required_passed": 1
    },
    "learning_assets": {
      "total": 10,
      "completed": 6,
      "in_progress": 2
    },
    "overall_completion_percentage": 50.0,
    "overall_content_completion_percentage": 61.11,
    "all_required_passed": false
  }
}
```

**Field descriptions**
| Field | Type | Description |
|-------|------|-------------|
| `quizzes.total` | int | Total active quizzes in course |
| `quizzes.completed` | int | Quizzes with at least one graded attempt |
| `quizzes.passed` | int | Quizzes with at least one passed attempt |
| `quizzes.required` | int | Quizzes marked `is_required` |
| `quizzes.required_passed` | int | Required quizzes that have been passed |
| `assignments.total` | int | Total active assignments in course |
| `assignments.completed` | int | Assignments with at least one graded submission |
| `assignments.passed` | int | Assignments with at least one passed submission |
| `assignments.required` | int | Assignments marked `is_required` |
| `assignments.required_passed` | int | Required assignments that have been passed |
| `learning_assets.total` | int | Total published learning assets in course |
| `learning_assets.completed` | int | Assets fully completed by student |
| `learning_assets.in_progress` | int | Assets started but not completed |
| `overall_completion_percentage` | float | `(required_passed / total_required) * 100` (quizzes + assignments) |
| `overall_content_completion_percentage` | float | `(completed / total_trackable) * 100` across all asset types |
| `all_required_passed` | bool | True when every required quiz and assignment is passed |

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | No active link between parent and student |

---

### 7. Quiz Attempts for a Course

```
GET /api/v1/web/students/{student}/courses/{course}/quiz-attempts
```

**Path parameters**
- `student` (integer, required) — student user ID
- `course` (integer, required) — course ID

**Response `200`**

```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "quiz": {
        "id": 7,
        "title": "Chapter 1 Quiz"
      },
      "attempt_number": 2,
      "status": "Graded",
      "started_at": "2026-03-20T14:00:00.000000Z",
      "submitted_at": "2026-03-20T14:30:00.000000Z",
      "time_spent_seconds": 1800,
      "score": 85.0,
      "points_earned": 17,
      "points_possible": 20,
      "passed": true,
      "answers": []
    }
  ]
}
```

**Notes**
- The `answers` array is empty in the list view (answers relation is not loaded).
- Results are ordered by `created_at` descending (newest first).
- `quiz` can be `null` if the quiz was deleted.
- `started_at` and `submitted_at` can be `null`.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | No active link between parent and student |

---

### 8. Quiz Attempt Detail

```
GET /api/v1/web/quiz-attempts/{attempt}
```

**Path parameters**
- `attempt` (integer, required) — quiz attempt ID

**Response `200`**

```json
{
  "success": true,
  "data": {
    "id": 101,
    "quiz": {
      "id": 7,
      "title": "Chapter 1 Quiz"
    },
    "attempt_number": 2,
    "status": "Graded",
    "started_at": "2026-03-20T14:00:00.000000Z",
    "submitted_at": "2026-03-20T14:30:00.000000Z",
    "time_spent_seconds": 1800,
    "score": 85.0,
    "points_earned": 17,
    "points_possible": 20,
    "passed": true,
    "answers": [
      {
        "question_id": 50,
        "question_text": "What is 2 + 2?",
        "selected_option_id": 201,
        "is_correct": true,
        "points_earned": 5
      }
    ]
  }
}
```

**Notes**
- Link validation uses the attempt's `user_id` and `center_id` to verify the parent is linked to the student.
- The `answers` array is populated in the detail view with full question text and correctness.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | Parent is not linked to the student who owns this attempt |

---

### 9. Assignment Submissions for a Course

```
GET /api/v1/web/students/{student}/courses/{course}/assignments
```

**Path parameters**
- `student` (integer, required) — student user ID
- `course` (integer, required) — course ID

**Response `200`**

```json
{
  "success": true,
  "data": [
    {
      "id": 55,
      "assignment": {
        "id": 12,
        "title": "Essay Assignment"
      },
      "status": "Graded",
      "submitted_at": "2026-03-18T09:00:00.000000Z",
      "score": 90.0,
      "score_after_penalty": 85.0,
      "passed": true,
      "is_late": false,
      "feedback": "Good work!",
      "graded_at": "2026-03-19T14:00:00.000000Z"
    }
  ]
}
```

**Field descriptions**
| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Submission ID |
| `assignment` | object/null | `{id, title}` — null if assignment deleted |
| `status` | string | Submission status enum name |
| `submitted_at` | string/null | ISO 8601 timestamp |
| `score` | float/null | Raw score before penalty |
| `score_after_penalty` | float/null | Score after late penalty applied |
| `passed` | bool/null | Whether the submission met the passing threshold |
| `is_late` | bool/null | Whether the submission was after the deadline |
| `feedback` | string/null | Grader feedback text |
| `graded_at` | string/null | ISO 8601 timestamp of grading |

**Notes**
- Only non-deleted submissions are returned.
- Results are ordered by `created_at` descending (newest first).
- `title` is locale-resolved from `title_translations`.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | No active link between parent and student |

---

### 10. Weekly Activity Summary

```
GET /api/v1/web/students/{student}/centers/{center}/activity/weekly
```

**Path parameters**
- `student` (integer, required) — student user ID
- `center` (integer, required) — center ID

**Query parameters**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `days` | int | `7` | Number of days to look back (inclusive of today) |

**Response `200`**

```json
{
  "success": true,
  "data": {
    "range": {
      "days": 7,
      "timezone": "Asia/Riyadh",
      "start_date": "2026-03-18",
      "end_date": "2026-03-24"
    },
    "series": [
      {
        "date": "2026-03-18",
        "watch_duration_seconds": 3600,
        "quiz_attempts_count": 2,
        "assignment_submissions_count": 1
      },
      {
        "date": "2026-03-19",
        "watch_duration_seconds": 0,
        "quiz_attempts_count": 0,
        "assignment_submissions_count": 0
      }
    ],
    "totals": {
      "watch_duration_seconds": 3600,
      "quiz_attempts_count": 2,
      "assignment_submissions_count": 1
    }
  }
}
```

**Field descriptions**
| Field | Type | Description |
|-------|------|-------------|
| `range.days` | int | Number of days in the range |
| `range.timezone` | string | Timezone used for date bucketing |
| `range.start_date` | string | `YYYY-MM-DD` start of range |
| `range.end_date` | string | `YYYY-MM-DD` end of range (today) |
| `series[].date` | string | `YYYY-MM-DD` for this day |
| `series[].watch_duration_seconds` | int | Total video watch time in seconds |
| `series[].quiz_attempts_count` | int | Number of quiz attempts on this day |
| `series[].assignment_submissions_count` | int | Number of assignment submissions on this day |
| `totals` | object | Sum of all series values |

**Notes**
- Timezone is resolved from the request (via `ResolveTimezone` middleware); falls back to `app.timezone` (UTC).
- `series` always contains exactly `days` entries, one per day, even if all values are zero.
- Watch duration is calculated from `playback_sessions` joined to courses belonging to the specified center.
- Quiz attempts use `started_at` (falling back to `created_at` if null) for date bucketing.
- Assignment submissions use `submitted_at` for date bucketing.

**Errors**
| Code | HTTP | When |
|------|------|------|
| `STUDENT_NOT_LINKED` | 403 | No active link between parent and student |

---

## Common Error Codes

| Code | HTTP | Description |
|------|------|-------------|
| `STUDENT_NOT_LINKED` | 403 | Parent does not have an active link to the specified student |
| `STUDENT_NOT_LINKED` | 404 | No student found with the given phone number (used by POST /links) |
| `LINK_ALREADY_EXISTS` | 422 | An active or pending link already exists for this parent-student pair |
| `PARENT_PORTAL_DISABLED` | 403 | Parent portal feature is disabled for this center |

---

## Read Rules

- Parents can view linked student profile and academic progress.
- Parents **cannot** start playback, access PDFs directly, submit quizzes, submit assignments, or mutate student settings.
- All data access requires an active link; pending or revoked links do not grant read access.
- Quiz attempt detail includes full answer review with question text and correctness.

---

## Linking Rules

- New link requests via `POST /links` are created with `status = PendingApproval` and require admin approval.
- Auto-link (triggered at login, not via this API) creates links with `status = Active` when the parent's phone matches a student's `parent_phone` field.
- Link requests are rate-limited to 5 per 60 minutes.
- Unbranded-center auto-linking may create multiple center-scoped rows for one parent.

---

## Resource Schemas

### ParentStudentResource

Used by: `GET /students`, `GET /links`, `POST /links`

```json
{
  "link_id": "int — ParentStudentLink ID",
  "student_id": "int — student user ID",
  "name": "string — student name",
  "phone": "string — student phone",
  "status": "string — Active | PendingApproval | Revoked",
  "link_method": "string — AdminManaged | AutoMatched | ParentRequested",
  "linked_at": "string — ISO 8601 timestamp"
}
```

### ParentProgressResource

Used by: `GET /students/{student}/courses/{course}/progress`

```json
{
  "quizzes": { "total": "int", "completed": "int", "passed": "int", "required": "int", "required_passed": "int" },
  "assignments": { "total": "int", "completed": "int", "passed": "int", "required": "int", "required_passed": "int" },
  "learning_assets": { "total": "int", "completed": "int", "in_progress": "int" },
  "overall_completion_percentage": "float",
  "overall_content_completion_percentage": "float",
  "all_required_passed": "bool"
}
```

### ParentQuizAttemptResource

Used by: `GET /students/{student}/courses/{course}/quiz-attempts`, `GET /quiz-attempts/{attempt}`

```json
{
  "id": "int — attempt ID",
  "quiz": { "id": "int", "title": "string (locale-resolved)" },
  "attempt_number": "int",
  "status": "string — attempt status enum name",
  "started_at": "string|null — ISO 8601",
  "submitted_at": "string|null — ISO 8601",
  "time_spent_seconds": "int|null",
  "score": "float|null",
  "points_earned": "int|null",
  "points_possible": "int|null",
  "passed": "bool|null",
  "answers": "array — empty in list, populated in detail"
}
```

### Answer object (within ParentQuizAttemptResource)

```json
{
  "question_id": "int",
  "question_text": "string (locale-resolved)",
  "selected_option_id": "int|null",
  "is_correct": "bool",
  "points_earned": "int|null"
}
```
