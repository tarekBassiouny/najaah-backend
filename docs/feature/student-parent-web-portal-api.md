# Student & Parent Web Portal — Frontend Handoff

## Overview

The backend provides a complete web portal API for two audiences:

1. **Student Web Portal** — full feature parity with the mobile app
2. **Parent Web Portal** — read-only dashboard for monitoring linked students

All endpoints are under `/api/v1/web/` and require the `X-Api-Key` header (resolved via `ResolveCenterApiKey` middleware).

---

## Authentication

### Headers (all requests)

```
X-Api-Key: {center_api_key}
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}  (for protected endpoints)
```

### Student Web Auth Flow

```
1. POST /api/v1/web/auth/student/send-otp
   Body: { "phone": "+966...", "country_code": "SA" }
   Response: { "success": true, "token": "otp_verification_token" }

2. POST /api/v1/web/auth/student/verify
   Body: { "token": "otp_verification_token", "otp": "1234" }
   Response: { "success": true, "token": { "access_token": "...", "refresh_token": "..." }, "user": {...} }

3. POST /api/v1/web/auth/student/refresh
   Body: { "refresh_token": "..." }
   Response: { "success": true, "token": { "access_token": "...", "refresh_token": "..." } }

4. GET /api/v1/web/auth/student/me → current user profile
5. POST /api/v1/web/auth/student/logout → revoke tokens
```

**Web Device Binding**: On verify, the backend auto-registers a web device with a generated UUID. No hardware fingerprint needed. If the center's `web_device_limit` is reached, the oldest web device is revoked.

### Parent Web Auth Flow

```
1. POST /api/v1/web/auth/parent/register
   Body: { "name": "Parent Name", "phone": "+966...", "country_code": "SA" }
   Response: { "success": true, "token": { "access_token": "...", "refresh_token": "..." }, "user": {...} }
   Note: Auto-links to students whose parent_phone matches this phone number.

2. POST /api/v1/web/auth/parent/send-otp  (returning parents)
   Body: { "phone": "+966...", "country_code": "SA" }

3. POST /api/v1/web/auth/parent/verify
   Body: { "token": "otp_verification_token", "otp": "1234" }

4. POST /api/v1/web/auth/parent/refresh
5. GET /api/v1/web/auth/parent/me → parent profile with linked students
6. POST /api/v1/web/auth/parent/logout
```

**No device binding for parents** — parents have read-only access, no playback.

---

## Center Settings That Control Portal Availability

| Setting | Type | Default | Controls |
|---------|------|---------|----------|
| `features.web_access` | Feature flag | `false` | Master switch for student web portal |
| `features.web_playback` | Feature flag | `false` | Enables video playback on web |
| `features.parent_portal` | Feature flag | `false` | Master switch for parent portal |
| `allow_web_access` | Center setting | `false` | Center-level toggle for web access |
| `allow_web_playback` | Center setting | `false` | Center-level toggle for web playback |
| `allow_parent_portal` | Center setting | `false` | Center-level toggle for parent portal |
| `web_device_limit` | Center setting | `3` | Max web devices per student |

**Important**: Feature flags **override** center settings. Even if `allow_web_access: true`, if `features.web_access: false` (the default), web access is disabled. Both must be enabled.

---

## Student Web Portal Endpoints

All routes mirror mobile routes. Auth middleware: `jwt.web.student` or `jwt.web.student.optional` (guest browsing).

### Guest-Accessible (no auth required if guest browsing enabled)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/courses/explore` | Browse available courses |
| GET | `/api/v1/web/centers/{center}/courses/{course}` | Course details |
| GET | `/api/v1/web/search` | Search courses |
| GET | `/api/v1/web/centers` | List centers (unbranded only) |
| GET | `/api/v1/web/centers/{center}` | Show center |
| GET | `/api/v1/web/centers/{center}/categories` | Center categories |
| GET | `/api/v1/web/instructors` | List instructors |
| GET | `/api/v1/web/categories` | List categories |

### Authenticated Student

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/auth/me` | Student profile |
| GET | `/api/v1/web/auth/me/profile` | Full profile details |
| POST | `/api/v1/web/auth/me` | Update profile |
| PATCH | `/api/v1/web/auth/me/education` | Update education |
| GET | `/api/v1/web/courses/enrolled` | Enrolled courses |
| GET | `/api/v1/web/courses/enrolled/by-instructor` | Enrolled by instructor |
| GET | `/api/v1/web/centers/{center}/activity/weekly` | Weekly activity |

### Playback

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/request_playback` | Request playback |
| POST | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/refresh_token` | Refresh playback token |
| POST | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/playback_progress` | Update progress |
| POST | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/close_session` | Close session |

### PDFs

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/centers/{center}/courses/{course}/pdfs/{pdf}/signed-url` | Get signed URL |

### Requests & Access

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/extra-view` | Request extra views |
| POST | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/access-request` | Request video access |
| GET | `/api/v1/web/centers/{center}/courses/{course}/videos/{video}/access-status` | Check access status |
| POST | `/api/v1/web/video-access-codes/redeem` | Redeem access code |
| POST | `/api/v1/web/centers/{center}/courses/{course}/enroll-request` | Request enrollment |

### Video Codes

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/web/video-codes/redeem` | Redeem batch code |
| POST | `/api/v1/web/video-codes/validate` | Validate batch code |
| GET | `/api/v1/web/video-codes/my-redemptions` | List my redemptions |

### Surveys

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/surveys/assigned` | List assigned surveys |
| GET | `/api/v1/web/surveys/{survey}` | Show survey |
| POST | `/api/v1/web/surveys/{survey}/submit` | Submit survey |

### Course Assets

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/centers/{center}/courses/{course}/assets` | List course assets |
| GET | `/api/v1/web/centers/{center}/assets/{type}/{id}` | Show asset |
| POST | `/api/v1/web/centers/{center}/assets/{type}/{id}/progress` | Track progress |

### Quizzes

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/web/centers/{center}/assets/quiz/{quiz}/attempts` | Start quiz attempt |
| GET | `/api/v1/web/centers/{center}/assets/quiz/attempts/{attempt}` | Show attempt |
| POST | `/api/v1/web/centers/{center}/assets/quiz/attempts/{attempt}/answers` | Save answer |
| POST | `/api/v1/web/centers/{center}/assets/quiz/attempts/{attempt}/submit` | Submit attempt |
| GET | `/api/v1/web/centers/{center}/assets/quiz/attempts/{attempt}/results` | Show results |

### Assignments

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/web/centers/{center}/assets/assignment/{assignment}/submission` | Create submission |
| POST | `/api/v1/web/centers/{center}/assets/assignment/submissions/{submission}/files` | Upload file |
| DELETE | `/api/v1/web/centers/{center}/assets/assignment/submissions/{submission}/files/{file}` | Remove file |
| POST | `/api/v1/web/centers/{center}/assets/assignment/submissions/{submission}/submit` | Submit |
| GET | `/api/v1/web/centers/{center}/assets/assignment/{assignment}/groups` | List groups |
| POST | `/api/v1/web/centers/{center}/assets/assignment/{assignment}/groups` | Create group |
| GET | `/api/v1/web/centers/{center}/assets/assignment/groups/{group}` | Show group |
| POST | `/api/v1/web/centers/{center}/assets/assignment/groups/{group}/join` | Join group |
| POST | `/api/v1/web/centers/{center}/assets/assignment/groups/{group}/leave` | Leave group |

### Education Lookups

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/centers/{center}/education` | Education config |
| GET | `/api/v1/web/centers/{center}/grades` | Grades list |
| GET | `/api/v1/web/centers/{center}/schools` | Schools list |
| GET | `/api/v1/web/centers/{center}/colleges` | Colleges list |

---

## Parent Web Portal Endpoints

Auth middleware: `jwt.web.parent`

### Linked Students

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/students` | List linked students |
| GET | `/api/v1/web/students/{student}` | Show student detail |

### Link Management

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/links` | List link requests |
| POST | `/api/v1/web/links` | Request new link (rate limited: 5/60s) |

Request body for POST:
```json
{
  "student_phone": "+966...",
  "center_id": 1
}
```

### Student Enrollments

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/students/{student}/enrollments` | List enrollments |

### Course Progress

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/students/{student}/courses/{course}/progress` | Course progress detail |

### Quiz Results

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/students/{student}/courses/{course}/quiz-attempts` | List quiz attempts |
| GET | `/api/v1/web/quiz-attempts/{attempt}` | Show quiz attempt detail |

### Assignment Submissions

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/students/{student}/courses/{course}/assignments` | List submissions |

### Weekly Activity

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/web/students/{student}/centers/{center}/activity/weekly` | Weekly activity |

---

## Admin Parent Management Endpoints

Auth middleware: `jwt.admin` with permissions.

### View (requires `parents.view`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/admin/centers/{center}/parents` | List parents in center |
| GET | `/api/v1/admin/centers/{center}/parents/pending-requests` | Pending link requests |
| GET | `/api/v1/admin/centers/{center}/parents/{parent}` | Parent detail |
| GET | `/api/v1/admin/students/{student}/parent-links` | Student's parent links |

### Manage (requires `parents.manage`)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/v1/admin/centers/{center}/parent-links` | Create link |
| PATCH | `/api/v1/admin/centers/{center}/parent-links/{link}` | Approve/reject/revoke |

Create link body:
```json
{
  "parent_id": 123,
  "student_id": 456
}
```

Update link body:
```json
{
  "action": "approve"  // or "reject", "revoke"
}
```

---

## Parent Linking Flows

### Auto-Match (on registration)
1. Parent registers with phone number.
2. Backend searches for students in the same center with `parent_phone` matching.
3. Matching students are automatically linked with `Active` status and `AutoMatched` method.

### Parent Request
1. Parent sends `POST /api/v1/web/links` with `student_phone` and `center_id`.
2. Link created with `PendingApproval` status and `ParentRequested` method.
3. Admin approves/rejects via `PATCH /api/v1/admin/centers/{center}/parent-links/{link}`.

### Admin Managed
1. Admin creates link via `POST /api/v1/admin/centers/{center}/parent-links`.
2. Link created with `Active` status and `AdminManaged` method.

---

## Error Codes

| Code | HTTP | Description |
|------|------|-------------|
| `WEB_ACCESS_DISABLED` | 403 | Web access is disabled for this center |
| `PARENT_PORTAL_DISABLED` | 403 | Parent portal is disabled for this center |
| `DEVICE_LIMIT_REACHED` | 403 | Web device limit exceeded |
| `NOT_A_STUDENT` | 403 | User is not a student (web student auth) |
| `NOT_A_PARENT` | 403 | User is not a parent (web parent auth) |
| `STUDENT_NOT_LINKED` | 403 | Parent does not have an active link to this student |
| `LINK_ALREADY_EXISTS` | 409 | Parent-student link already exists |
| `STUDENT_NOT_FOUND` | 404 | Student not found in center |
| `USER_BANNED` | 403 | User account is banned |

---

## Response Shapes

### Unified Success
```json
{
  "success": true,
  "message": "...",
  "data": { ... }
}
```

### Unified Error
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message"
  }
}
```

### Paginated
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 42
  }
}
```

### Token Response (auth verify/refresh)
```json
{
  "success": true,
  "token": {
    "access_token": "eyJ...",
    "refresh_token": "eyJ..."
  },
  "user": {
    "id": 1,
    "name": "...",
    "phone": "+966...",
    "is_student": true,
    "is_parent": false
  }
}
```

### Parent Me Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Parent Name",
    "phone": "+966...",
    "is_parent": true,
    "linked_students": [
      {
        "id": 10,
        "name": "Student Name",
        "center_id": 1,
        "center_name": "Center Name",
        "link_status": "active"
      }
    ]
  }
}
```
