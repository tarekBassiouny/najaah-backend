# Student & Parent Web Portal — Student Web API Contract

## Status: Implemented
## Source Phase: 3 — Student Web Portal
## Audience: Portal frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the student-facing web API surface that reuses existing mobile controller behavior under web JWT auth.

---

## Base URL

```
/api/v1/web
```

All paths below are relative to this prefix.

---

## Authentication

| Middleware | Meaning |
|---|---|
| `jwt.web.student.optional` + `guest.browsing` | Guest browsing allowed when center has `allow_guest_browsing` enabled; authenticated users always pass |
| `jwt.web.student` | Authenticated web student required (web JWT) |

---

## Response Envelope

All responses follow the unified format:

```jsonc
// Success
{ "success": true, "message": "...", "data": { ... } }

// Success with pagination
{ "success": true, "data": [ ... ], "meta": { "page": 1, "per_page": 15, "total": 42 } }

// Error
{ "success": false, "error": { "code": "ERROR_CODE", "message": "Human-readable message." } }
```

---

## 1. Guest-Browsable Routes

**Middleware:** `jwt.web.student.optional` + `guest.browsing`

These routes work for unauthenticated guests (when center allows guest browsing) and for authenticated students.

### 1.1 Explore Courses

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/courses/explore` |
| **Status** | Implemented |
| **Controller** | `Mobile\ExploreController@explore` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |
| `category_id` | integer | No | Filter by category ID |
| `instructor_id` | integer | No | Filter by instructor ID |
| `enrolled` | boolean | No | Filter by enrollment status |
| `is_featured` | boolean | No | Filter by featured flag |
| `publish_from` | date | No | Filter courses published on or after this date |
| `publish_to` | date | No | Filter courses published on or before this date |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* ExploreCourseResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 42 }
}
```

---

### 1.2 Show Course Details

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/courses/{course}` |
| **Status** | Implemented |
| **Controller** | `Mobile\ExploreController@show` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* CourseDetailsResource */ }
}
```

---

### 1.3 Search Courses

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/search` |
| **Status** | Implemented |
| **Controller** | `Mobile\SearchController@index` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search term for course title or instructor name. If empty, returns fallback/recent courses |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* ExploreCourseResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 5 }
}
```

---

### 1.4 List Centers (Unbranded Only)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers` |
| **Status** | Implemented |
| **Controller** | `Mobile\CentersController@index` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search centers by name or description |
| `is_featured` | boolean | No | Filter by featured status |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* CenterResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 10 }
}
```

**Note:** Branded students receive a 403 error. Only unbranded center listing is supported.

---

### 1.5 Show Center with Courses

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}` |
| **Status** | Implemented |
| **Controller** | `Mobile\CentersController@show` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `is_featured` | boolean | No | Filter center courses by featured status |
| `category_id` | integer | No | Filter center courses by category ID |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": {
    "center": { /* CenterResource */ },
    "courses": [ /* ExploreCourseResource[] */ ]
  },
  "meta": { "page": 1, "per_page": 15, "total": 8 }
}
```

---

### 1.6 List Center Categories

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/categories` |
| **Status** | Implemented |
| **Controller** | `Mobile\CategoryController@centerIndex` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search by category title |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* CategoryResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 6 }
}
```

---

### 1.7 List Instructors

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/instructors` |
| **Status** | Implemented |
| **Controller** | `Mobile\InstructorController@index` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search by instructor name or title |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* InstructorResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 12 }
}
```

---

### 1.8 List Categories

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/categories` |
| **Status** | Implemented |
| **Controller** | `Mobile\CategoryController@index` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search by category title |
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* CategoryResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 6 }
}
```

---

## 2. Profile & Auth

**Middleware:** `jwt.web.student`

### 2.1 Get Profile (Compact)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/auth/me` |
| **Status** | Implemented |
| **Controller** | `Web\Student\MeController@profile` |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* StudentUserResource — includes center, roles */ }
}
```

---

### 2.2 Get Profile Details (Full)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/auth/me/profile` |
| **Status** | Implemented |
| **Controller** | `Web\Student\MeController@profileDetails` |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": {
    /* StudentProfileResource fields */
    "is_complete_profile": true,
    "profile_completion": {
      "missing_steps": [],
      "missing_fields": []
    }
  }
}
```

---

### 2.3 Update Profile

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/me` |
| **Status** | Implemented |
| **Controller** | `Web\Student\MeController@updateProfile` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `name` | string | No* | Student display name |
| `parent_phone` | string\|null | No* | Parent phone number (regex: `^[\d+\s().-]+$`, max 24 chars) |

*At least one field must be provided.

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* StudentUserResource */ }
}
```

---

### 2.4 Update Education Profile

| | |
|---|---|
| **Method** | `PATCH` |
| **Path** | `/auth/me/education` |
| **Status** | Implemented |
| **Controller** | `Web\Student\MeController@updateEducation` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `grade_id` | integer\|null | No | Grade ID (must be active, scoped to center) |
| `school_id` | integer\|null | No | School ID (must be active, scoped to center) |
| `college_id` | integer\|null | No | College ID (must be active, scoped to center) |

All submitted entities must belong to the same center. Required fields depend on center education_profile settings.

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Educational profile updated successfully.",
  "data": {
    "grade": { "id": 12, "name": "Grade 10" },
    "school": { "id": 8, "name": "Al-Noor School" },
    "college": null,
    "is_complete_profile": true,
    "profile_completion": {
      "missing_steps": [],
      "missing_fields": []
    }
  }
}
```

---

### 2.5 Logout

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/auth/logout` |
| **Status** | Implemented |
| **Controller** | `Web\Student\MeController@logout` |

**Response:** `200 OK`

```jsonc
{ "success": true }
```

---

## 3. Enrolled Courses

**Middleware:** `jwt.web.student`

### 3.1 List Enrolled Courses

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/courses/enrolled` |
| **Status** | Implemented |
| **Controller** | `Mobile\EnrolledCoursesController@index` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |
| `category_id` | integer | No | Filter by category ID |
| `instructor_id` | integer | No | Filter by instructor ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* ExploreCourseResource[] */ ],
  "meta": { "page": 1, "per_page": 15, "total": 5 }
}
```

---

### 3.2 Enrolled Courses by Instructor

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/courses/enrolled/by-instructor` |
| **Status** | Implemented |
| **Controller** | `Mobile\EnrolledCoursesController@byInstructor` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |
| `category_id` | integer | No | Filter by category ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* InstructorWithCoursesResource[] */ ]
}
```

---

### 3.3 Weekly Activity

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/activity/weekly` |
| **Status** | Implemented |
| **Controller** | `Mobile\WeeklyActivityController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `days` | integer | No | Number of days (1-30, default: 7) |

**Response:** `200 OK`

```jsonc
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
      }
    ],
    "totals": {
      "watch_duration_seconds": 18000,
      "quiz_attempts_count": 5,
      "assignment_submissions_count": 3
    }
  }
}
```

---

## 4. Video Playback

**Middleware:** `jwt.web.student`

All playback endpoints share path parameters:

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |
| `video` | integer | Video ID |

### 4.1 Request Playback

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/request_playback` |
| **Status** | Implemented |
| **Controller** | `Mobile\PlaybackController@requestPlayback` |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* PlaybackSessionResource */ }
}
```

---

### 4.2 Refresh Playback Token

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/refresh_token` |
| **Status** | Implemented |
| **Controller** | `Mobile\PlaybackController@refreshToken` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `session_id` | integer | Yes | Active playback session ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* PlaybackTokenResource */ }
}
```

---

### 4.3 Update Playback Progress

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/playback_progress` |
| **Status** | Implemented |
| **Controller** | `Mobile\PlaybackController@updateProgress` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `session_id` | integer | Yes | Active playback session ID |
| `percentage` | integer | Yes | Progress percentage (0-100) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* progress object */ }
}
```

---

### 4.4 Close Playback Session

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/close_session` |
| **Status** | Implemented |
| **Controller** | `Mobile\PlaybackController@closeSession` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `session_id` | integer | Yes | Playback session ID |
| `watch_duration` | integer | Yes | Total watch duration in seconds |

**Response:** `200 OK`

```jsonc
{ "success": true }
```

---

## 5. PDFs

**Middleware:** `jwt.web.student`

### 5.1 Get PDF Signed URL

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/courses/{course}/pdfs/{pdf}/signed-url` |
| **Status** | Implemented |
| **Controller** | `Mobile\PdfController@signedUrl` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |
| `pdf` | integer | PDF ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": {
    "url": "https://cdn.example.com/pdfs/...?signature=...",
    "expires_at": "2026-03-24T12:10:00Z"
  }
}
```

---

## 6. Extra View Requests

**Middleware:** `jwt.web.student`

### 6.1 Request Extra Views

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/extra-view` |
| **Status** | Implemented |
| **Controller** | `Mobile\ExtraViewRequestController@store` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |
| `video` | integer | Video ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `reason` | string | No | Reason for requesting extra views |

**Response:** `200 OK`

```jsonc
{ "success": true }
```

---

## 7. Video Access Approval

**Middleware:** `jwt.web.student`

### 7.1 Request Video Access

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/access-request` |
| **Status** | Implemented |
| **Controller** | `Mobile\VideoAccessRequestController@store` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |
| `video` | integer | Video ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `reason` | string | No | Reason for requesting access |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": {
    "request_id": 123,
    "status": "pending"
  }
}
```

---

### 7.2 Check Video Access Status

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/courses/{course}/videos/{video}/access-status` |
| **Status** | Implemented |
| **Controller** | `Mobile\VideoAccessRequestController@status` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |
| `video` | integer | Video ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": { /* status object from service */ }
}
```

---

### 7.3 Redeem Video Access Code (Enrollment-Based)

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/video-access-codes/redeem` |
| **Status** | Implemented |
| **Controller** | `Mobile\VideoAccessCodeController@redeem` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `code` | string | Yes | The access code to redeem |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Video unlocked successfully.",
  "data": {
    "id": 1,
    "video_id": 42,
    "course_id": 10,
    "granted_at": "2026-03-24T10:00:00Z"
  }
}
```

---

## 8. Video Code Batches (video_code access model)

**Middleware:** `jwt.web.student`

### 8.1 Redeem Batch Code

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/video-codes/redeem` |
| **Status** | Implemented |
| **Controller** | `Mobile\VideoAccessCodeController@redeemBatchCode` |
| **Throttle** | `throttle:video-code-redeem` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `code` | string | Yes | The batch video code |
| `video_id` | integer | Yes | Target video ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Code redeemed successfully",
  "data": {
    "redemption_id": 1,
    "video_id": 42,
    "video_title": "Lecture 5",
    "course_id": 10,
    "course_title": "Biology 101",
    "center_id": 3,
    "view_limit": 5,
    "total_view_limit": 10,
    "batch_code": "BIO101",
    "sequence_number": 7,
    "redeemed_at": "2026-03-24T10:00:00+00:00"
  }
}
```

---

### 8.2 Validate Batch Code

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/video-codes/validate` |
| **Status** | Implemented |
| **Controller** | `Mobile\VideoAccessCodeController@validateBatchCode` |
| **Throttle** | `throttle:video-code-validate` |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `code` | string | Yes | The batch video code to validate |
| `video_id` | integer | Yes | Target video ID |

**Response:** `200 OK` (always 200, check `valid` flag)

```jsonc
// Valid code
{
  "success": true,
  "message": "Code is valid",
  "data": {
    "valid": true,
    "video_id": 42,
    "video_title": "Lecture 5",
    "course_id": 10,
    "course_title": "Biology 101",
    "center_id": 3,
    "view_limit": 5,
    "can_redeem": true
  }
}

// Invalid code
{
  "success": true,
  "data": {
    "valid": false,
    "reason": "Code has already been redeemed"
  }
}
```

---

### 8.3 My Redemptions

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/video-codes/my-redemptions` |
| **Status** | Implemented |
| **Controller** | `Mobile\VideoAccessCodeController@myRedemptions` |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page, max 100 (default: 15) |
| `course_id` | integer | No | Filter redemptions by course |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "BIO101-0007",
      "batch_code": "BIO101",
      "sequence_number": 7,
      "video_id": 42,
      "video_title": "Lecture 5",
      "course_id": 10,
      "course_title": "Biology 101",
      "center_id": 3,
      "view_limit": 5,
      "redeemed_at": "2026-03-24T10:00:00+00:00"
    }
  ],
  "meta": {
    "page": 1,
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

## 9. Enrollment Requests

**Middleware:** `jwt.web.student`

### 9.1 Request Enrollment

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/courses/{course}/enroll-request` |
| **Status** | Implemented |
| **Controller** | `Mobile\EnrollmentRequestController@store` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `reason` | string | No | Reason for enrollment request |

**Response:** `200 OK`

```jsonc
{ "success": true }
```

---

## 10. Surveys

**Middleware:** `jwt.web.student`

### 10.1 List Assigned Surveys

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/surveys/assigned` |
| **Status** | Implemented |
| **Controller** | `Mobile\SurveyController@assigned` |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": [ /* AssignedSurveyResource[] */ ]
}
```

---

### 10.2 Show Survey

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/surveys/{survey}` |
| **Status** | Implemented |
| **Controller** | `Mobile\SurveyController@show` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `survey` | integer | Survey ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": {
    /* AssignedSurveyResource fields */
    "has_submitted": false
  }
}
```

Returns 404 if survey is unavailable, not assigned, or already submitted.

---

### 10.3 Submit Survey

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/surveys/{survey}/submit` |
| **Status** | Implemented |
| **Controller** | `Mobile\SurveyController@submit` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `survey` | integer | Survey ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `answers` | array | Yes | Array of answer objects (min: 1) |
| `answers.*.question_id` | integer | Yes | Survey question ID |
| `answers.*.answer` | mixed | Yes | Answer value (integer for single-choice, array for multi-choice, string for text) |

**Response:** `201 Created`

```jsonc
{
  "success": true,
  "message": "Survey submitted successfully",
  "data": { /* SurveySubmissionResource */ }
}
```

**Error codes:** `NOT_AVAILABLE` (400), `FORBIDDEN` (403), `ALREADY_SUBMITTED` (409), `VALIDATION_ERROR` (422)

---

## 11. Course Assets (Unified Read Surface)

**Middleware:** `jwt.web.student`

### 11.1 List Course Assets

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/courses/{course}/assets` |
| **Status** | Implemented |
| **Controller** | `Mobile\CourseAssetController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `course` | integer | Course ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `type` | string | No | Filter by asset type (enum: `quiz`, `assignment`, `video`, `pdf`) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": [ /* CourseAssetListResource[] */ ]
}
```

---

### 11.2 Show Course Asset

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/assets/{type}/{id}` |
| **Status** | Implemented |
| **Controller** | `Mobile\CourseAssetController@show` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `type` | string | Asset type (`quiz`, `assignment`, `video`, `pdf`) |
| `id` | integer | Asset ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": { /* CourseAssetDetailResource */ }
}
```

---

### 11.3 Track Asset Progress

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/{type}/{id}/progress` |
| **Status** | Implemented |
| **Controller** | `Mobile\CourseAssetController@trackProgress` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `type` | string | Asset type (`quiz`, `assignment`, `video`, `pdf`) |
| `id` | integer | Asset ID |

**Body Parameters:** Varies by asset type (validated by `TrackLearningAssetProgressRequest`).

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": { /* LearningAssetProgressResource */ }
}
```

---

## 12. Quizzes

**Middleware:** `jwt.web.student`

### 12.1 Start Quiz Attempt

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/quiz/{quiz}/attempts` |
| **Status** | Implemented |
| **Controller** | `Mobile\QuizAttemptController@start` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `quiz` | integer | Quiz ID |

**Response:** `201 Created` (new attempt) or `200 OK` (resuming existing)

```jsonc
{
  "success": true,
  "message": "Quiz attempt started",
  "data": { /* QuizAttemptDetailResource — includes quiz questions and saved answers */ }
}
```

**Error codes:** `NOT_ENROLLED` (403), `NOT_AVAILABLE` (400), `NO_ATTEMPTS_LEFT` (400)

---

### 12.2 Show Quiz Attempt

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/assets/quiz/attempts/{attempt}` |
| **Status** | Implemented |
| **Controller** | `Mobile\QuizAttemptController@show` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `attempt` | integer | Quiz attempt ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": { /* QuizAttemptDetailResource */ }
}
```

---

### 12.3 Save Quiz Answer

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/quiz/attempts/{attempt}/answers` |
| **Status** | Implemented |
| **Controller** | `Mobile\QuizAttemptController@answer` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `attempt` | integer | Quiz attempt ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `question_id` | integer | Yes | Quiz question ID |
| `answer_ids` | array\<integer\> | Yes | Selected answer option IDs |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Answer saved",
  "data": {
    "question_id": 5,
    "answer_ids": [12, 14],
    "remaining_time_seconds": 540
  }
}
```

**Error codes:** `ATTEMPT_CLOSED` (400), `TIME_EXPIRED` (400), `INVALID_QUESTION` (400)

---

### 12.4 Submit Quiz Attempt

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/quiz/attempts/{attempt}/submit` |
| **Status** | Implemented |
| **Controller** | `Mobile\QuizAttemptController@submit` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `attempt` | integer | Quiz attempt ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Quiz submitted successfully",
  "data": { /* QuizResultResource */ }
}
```

**Error codes:** `ALREADY_SUBMITTED` (400)

---

### 12.5 Get Quiz Results

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/assets/quiz/attempts/{attempt}/results` |
| **Status** | Implemented |
| **Controller** | `Mobile\QuizAttemptController@results` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `attempt` | integer | Quiz attempt ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": { /* QuizResultResource */ }
}
```

**Error codes:** `NOT_SUBMITTED` (400)

---

## 13. Assignments

**Middleware:** `jwt.web.student`

### 13.1 Create/Update Submission Draft

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/assignment/{assignment}/submission` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentSubmissionController@store` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `assignment` | integer | Assignment ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `submission_type` | string | No | Submission type |
| `text_content` | string | No | Text submission content |
| `link_url` | string | No | Link URL submission |

**Response:** `201 Created` (new) or `200 OK` (updated)

```jsonc
{
  "success": true,
  "message": "Draft created",
  "data": { /* AssignmentSubmissionResource — includes files */ }
}
```

**Error codes:** `NOT_ENROLLED` (403), `CANNOT_SUBMIT` (400), `ALREADY_SUBMITTED` (400)

---

### 13.2 Upload File to Submission

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/assignment/submissions/{submission}/files` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentSubmissionController@uploadFile` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `submission` | integer | Submission ID |

**Body Parameters:** `multipart/form-data`

| Param | Type | Required | Description |
|---|---|---|---|
| `file` | file | Yes | The file to upload |

**Response:** `201 Created`

```jsonc
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "file_name": "report.pdf",
    "file_size_kb": 2048,
    "file_type": "pdf"
  }
}
```

**Error codes:** `CANNOT_MODIFY` (400), `FILE_LIMIT_REACHED` (400), `INVALID_FILE_TYPE` (400), `FILE_TOO_LARGE` (400)

---

### 13.3 Remove File from Submission

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/centers/{center}/assets/assignment/submissions/{submission}/files/{file}` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentSubmissionController@removeFile` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `submission` | integer | Submission ID |
| `file` | integer | File ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "File removed successfully"
}
```

**Error codes:** `CANNOT_MODIFY` (400)

---

### 13.4 Submit Assignment

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/assignment/submissions/{submission}/submit` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentSubmissionController@submit` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `submission` | integer | Submission ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Assignment submitted successfully",
  "data": { /* AssignmentSubmissionResource — includes files */ }
}
```

**Error codes:** `ALREADY_SUBMITTED` (400), `EMPTY_SUBMISSION` (400)

---

## 14. Assignment Groups

**Middleware:** `jwt.web.student`

### 14.1 List Assignment Groups

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/assets/assignment/{assignment}/groups` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentGroupController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `assignment` | integer | Assignment ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": {
    "groups": [ /* AssignmentGroupResource[] */ ],
    "my_group_id": 5,
    "max_group_size": 4
  }
}
```

**Error codes:** `NOT_GROUP_ASSIGNMENT` (400)

---

### 14.2 Create Assignment Group

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/assignment/{assignment}/groups` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentGroupController@store` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `assignment` | integer | Assignment ID |

**Body Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Group name |

**Response:** `201 Created`

```jsonc
{
  "success": true,
  "message": "Group created successfully",
  "data": { /* AssignmentGroupResource — includes members */ }
}
```

**Error codes:** `NOT_GROUP_ASSIGNMENT` (400), `ALREADY_IN_GROUP` (400)

---

### 14.3 Show Assignment Group

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/assets/assignment/groups/{group}` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentGroupController@show` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `group` | integer | Group ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Operation completed",
  "data": { /* AssignmentGroupResource — includes members, creator, submission */ }
}
```

---

### 14.4 Join Assignment Group

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/assignment/groups/{group}/join` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentGroupController@join` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `group` | integer | Group ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Joined group successfully",
  "data": { /* AssignmentGroupResource */ }
}
```

**Error codes:** `ALREADY_IN_GROUP` (400), `GROUP_FULL` (400)

---

### 14.5 Leave Assignment Group

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/centers/{center}/assets/assignment/groups/{group}/leave` |
| **Status** | Implemented |
| **Controller** | `Mobile\AssignmentGroupController@leave` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer | Center ID |
| `group` | integer | Group ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "message": "Left group successfully"
}
```

**Error codes:** `NOT_IN_GROUP` (400)

---

## 15. Education Lookups

**Middleware:** `jwt.web.student`

All education lookup endpoints require the student to belong to the given center.

### 15.1 Education Overview (Settings + All Lookups)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/education` |
| **Status** | Implemented |
| **Controller** | `Mobile\Education\EducationLookupController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer (numeric) | Center ID |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": {
    "settings": {
      "enable_grade": true,
      "enable_school": true,
      "enable_college": true,
      "enable_parent_phone": true,
      "require_grade": false,
      "require_school": false,
      "require_college": false,
      "require_parent_phone": false
    },
    "lookups": {
      "grades": [ /* GradeResource[] */ ],
      "schools": [ /* SchoolResource[] */ ],
      "colleges": [ /* CollegeResource[] */ ]
    }
  }
}
```

---

### 15.2 List Grades

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/grades` |
| **Status** | Implemented |
| **Controller** | `Mobile\Education\GradeLookupController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer (numeric) | Center ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `stage` | integer | No | Filter by stage |
| `search` | string | No | Search by name (en/ar) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* GradeResource[] */ ]
}
```

Returns empty array if grade module is disabled for the center.

---

### 15.3 List Schools

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/schools` |
| **Status** | Implemented |
| **Controller** | `Mobile\Education\SchoolLookupController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer (numeric) | Center ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `type` | integer | No | Filter by school type |
| `search` | string | No | Search by name (en/ar) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* SchoolResource[] */ ]
}
```

Returns empty array if school module is disabled for the center.

---

### 15.4 List Colleges

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/centers/{center}/colleges` |
| **Status** | Implemented |
| **Controller** | `Mobile\Education\CollegeLookupController@index` |

**Path Parameters:**

| Param | Type | Description |
|---|---|---|
| `center` | integer (numeric) | Center ID |

**Query Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `search` | string | No | Search by name (en/ar) |

**Response:** `200 OK`

```jsonc
{
  "success": true,
  "data": [ /* CollegeResource[] */ ]
}
```

Returns empty array if college module is disabled for the center.

---

## Endpoint Summary

| # | Method | Path | Auth | Status |
|---|---|---|---|---|
| 1.1 | GET | `/courses/explore` | optional | Implemented |
| 1.2 | GET | `/centers/{center}/courses/{course}` | optional | Implemented |
| 1.3 | GET | `/search` | optional | Implemented |
| 1.4 | GET | `/centers` | optional | Implemented |
| 1.5 | GET | `/centers/{center}` | optional | Implemented |
| 1.6 | GET | `/centers/{center}/categories` | optional | Implemented |
| 1.7 | GET | `/instructors` | optional | Implemented |
| 1.8 | GET | `/categories` | optional | Implemented |
| 2.1 | GET | `/auth/me` | required | Implemented |
| 2.2 | GET | `/auth/me/profile` | required | Implemented |
| 2.3 | POST | `/auth/me` | required | Implemented |
| 2.4 | PATCH | `/auth/me/education` | required | Implemented |
| 2.5 | POST | `/auth/logout` | required | Implemented |
| 3.1 | GET | `/courses/enrolled` | required | Implemented |
| 3.2 | GET | `/courses/enrolled/by-instructor` | required | Implemented |
| 3.3 | GET | `/centers/{center}/activity/weekly` | required | Implemented |
| 4.1 | POST | `/centers/{center}/courses/{course}/videos/{video}/request_playback` | required | Implemented |
| 4.2 | POST | `/centers/{center}/courses/{course}/videos/{video}/refresh_token` | required | Implemented |
| 4.3 | POST | `/centers/{center}/courses/{course}/videos/{video}/playback_progress` | required | Implemented |
| 4.4 | POST | `/centers/{center}/courses/{course}/videos/{video}/close_session` | required | Implemented |
| 5.1 | GET | `/centers/{center}/courses/{course}/pdfs/{pdf}/signed-url` | required | Implemented |
| 6.1 | POST | `/centers/{center}/courses/{course}/videos/{video}/extra-view` | required | Implemented |
| 7.1 | POST | `/centers/{center}/courses/{course}/videos/{video}/access-request` | required | Implemented |
| 7.2 | GET | `/centers/{center}/courses/{course}/videos/{video}/access-status` | required | Implemented |
| 7.3 | POST | `/video-access-codes/redeem` | required | Implemented |
| 8.1 | POST | `/video-codes/redeem` | required | Implemented |
| 8.2 | POST | `/video-codes/validate` | required | Implemented |
| 8.3 | GET | `/video-codes/my-redemptions` | required | Implemented |
| 9.1 | POST | `/centers/{center}/courses/{course}/enroll-request` | required | Implemented |
| 10.1 | GET | `/surveys/assigned` | required | Implemented |
| 10.2 | GET | `/surveys/{survey}` | required | Implemented |
| 10.3 | POST | `/surveys/{survey}/submit` | required | Implemented |
| 11.1 | GET | `/centers/{center}/courses/{course}/assets` | required | Implemented |
| 11.2 | GET | `/centers/{center}/assets/{type}/{id}` | required | Implemented |
| 11.3 | POST | `/centers/{center}/assets/{type}/{id}/progress` | required | Implemented |
| 12.1 | POST | `/centers/{center}/assets/quiz/{quiz}/attempts` | required | Implemented |
| 12.2 | GET | `/centers/{center}/assets/quiz/attempts/{attempt}` | required | Implemented |
| 12.3 | POST | `/centers/{center}/assets/quiz/attempts/{attempt}/answers` | required | Implemented |
| 12.4 | POST | `/centers/{center}/assets/quiz/attempts/{attempt}/submit` | required | Implemented |
| 12.5 | GET | `/centers/{center}/assets/quiz/attempts/{attempt}/results` | required | Implemented |
| 13.1 | POST | `/centers/{center}/assets/assignment/{assignment}/submission` | required | Implemented |
| 13.2 | POST | `/centers/{center}/assets/assignment/submissions/{submission}/files` | required | Implemented |
| 13.3 | DELETE | `/centers/{center}/assets/assignment/submissions/{submission}/files/{file}` | required | Implemented |
| 13.4 | POST | `/centers/{center}/assets/assignment/submissions/{submission}/submit` | required | Implemented |
| 14.1 | GET | `/centers/{center}/assets/assignment/{assignment}/groups` | required | Implemented |
| 14.2 | POST | `/centers/{center}/assets/assignment/{assignment}/groups` | required | Implemented |
| 14.3 | GET | `/centers/{center}/assets/assignment/groups/{group}` | required | Implemented |
| 14.4 | POST | `/centers/{center}/assets/assignment/groups/{group}/join` | required | Implemented |
| 14.5 | POST | `/centers/{center}/assets/assignment/groups/{group}/leave` | required | Implemented |
| 15.1 | GET | `/centers/{center}/education` | required | Implemented |
| 15.2 | GET | `/centers/{center}/grades` | required | Implemented |
| 15.3 | GET | `/centers/{center}/schools` | required | Implemented |
| 15.4 | GET | `/centers/{center}/colleges` | required | Implemented |

**Total: 48 endpoints**
