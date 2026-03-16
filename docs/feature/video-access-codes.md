# Video Access Codes - Feature Documentation

> **Feature:** Anonymous video access codes for offline sales business model

**Related Documentation:**
- [Frontend Implementation Guide](./video-access-codes-frontend-guide.md) - Complete guide for mobile/frontend team

## Implementation Progress

| Phase | Status | Completed |
|-------|--------|-----------|
| Documentation | ✅ Done | 2026-03-14 |
| Phase 1: Database & Models | ✅ Done | 2026-03-14 |
| Phase 2: Services | ✅ Done | 2026-03-14 |
| Phase 3: Authorization Updates | ✅ Done | 2026-03-14 |
| Phase 4: Admin API | ✅ Done | 2026-03-14 |
| Phase 5: Mobile API | ✅ Done | 2026-03-14 |
| Phase 6: Mobile Integration | ✅ Done | 2026-03-14 |
| Testing | ✅ Syntax Verified | 2026-03-14 |

### Completed Files

**Phase 1 - Database & Models:**
- `app/Enums/CourseAccessModel.php` - Course access model enum
- `app/Enums/VideoCodeBatchStatus.php` - Batch status enum
- `app/Models/VideoCodeBatch.php` - Batch model
- `app/Models/VideoCodeRedemption.php` - Redemption model
- `app/Models/Course.php` - Updated with `access_model` field
- `app/Models/VideoAccess.php` - Updated with `total_view_limit` field
- `database/migrations/2026_03_14_221140_add_access_model_to_courses_table.php`
- `database/migrations/2026_03_14_221141_create_video_code_batches_table.php`
- `database/migrations/2026_03_14_221142_create_video_code_redemptions_table.php`
- `database/migrations/2026_03_14_221143_add_total_view_limit_to_video_accesses_table.php`

**Phase 2 - Services:**
- `app/Services/VideoAccess/VideoCodeGenerator.php` - Code generation algorithm
- `app/Services/VideoAccess/VideoCodeBatchService.php` - Batch management service
- `app/Services/VideoAccess/VideoCodeRedemptionService.php` - Code redemption service
- `app/Services/VideoAccess/Contracts/VideoCodeBatchServiceInterface.php`
- `app/Services/VideoAccess/Contracts/VideoCodeRedemptionServiceInterface.php`
- `app/Support/ErrorCodes.php` - Added new error codes

**Phase 3 - Authorization:**
- `app/Services/Playback/PlaybackAuthorizationService.php` - Support video code access model
- `app/Services/Playback/ViewLimitService.php` - Use total_view_limit for video code access

**Phase 4 - Admin API:**
- `app/Http/Controllers/Admin/VideoCodeBatchController.php`
- `app/Http/Requests/Admin/VideoAccess/CreateVideoCodeBatchRequest.php`
- `app/Http/Requests/Admin/VideoAccess/ExpandVideoCodeBatchRequest.php`
- `app/Http/Requests/Admin/VideoAccess/CloseVideoCodeBatchRequest.php`
- `app/Http/Requests/Admin/VideoAccess/ListVideoCodeBatchesRequest.php`
- `app/Http/Resources/Admin/VideoAccess/VideoCodeBatchResource.php`
- `app/Http/Resources/Admin/VideoAccess/VideoCodeBatchListResource.php`
- `app/Http/Resources/Admin/VideoAccess/VideoCodeBatchStatisticsResource.php`
- `routes/api/v1/admin/video-code-batches.php`
- `bootstrap/app.php` - Added route registration

**Phase 5 - Mobile API:**
- `app/Http/Controllers/Mobile/VideoAccessCodeController.php` - Updated with batch code methods
- `app/Http/Requests/Mobile/RedeemVideoCodeRequest.php`
- `app/Http/Requests/Mobile/ValidateVideoCodeRequest.php`
- `routes/api/v1/mobile.php` - Added new routes

**Phase 6 - Mobile Integration (Explore/Search/MyCourses):**
- `app/Models/Course.php` - Added `videoCodeBatches` relationship, `scopeWithVideoCodeAccessBy`, `scopeAccessibleBy`
- `app/Http/Resources/Mobile/ExploreCourseResource.php` - Added `access_model`, `has_access` fields
- `app/Http/Resources/Mobile/CourseDetailsResource.php` - Added `access_model`, `has_access` fields
- `app/Http/Resources/Mobile/CourseVideoResource.php` - Updated `requiresRedemption` and `resolveRedemptionStatus` for video_code model
- `app/Services/Courses/CourseService.php` - Updated `enrolled` and `enrolledGroupedByInstructor` to use `accessibleBy` scope

**Configuration:**
- `app/Providers/AppServiceProvider.php` - Registered new services

**Factories:**
- `database/factories/VideoCodeBatchFactory.php`
- `database/factories/VideoCodeRedemptionFactory.php`

---

## Overview

This feature introduces a new access model for videos that allows centers to generate anonymous access codes in batches, sell them offline, and invoice based on confirmed sales rather than usage.

## Business Model

### Key Differences from Existing System

| Aspect | Current (Enrollment-based) | New (Video Code) |
|--------|---------------------------|------------------|
| **Code Generation** | Per-student (requires `user_id`) | Per-video (anonymous, batch) |
| **Access Model** | Enrollment + Code approval layer | Code IS the access (no enrollment) |
| **Student Identity** | Required before code generation | Unknown at generation time |
| **Batch Management** | None | Required (generate → sell → invoice → close) |
| **Invoicing** | Not applicable | Based on confirmed sold codes |

### Client Flow

1. Upload video in course/section (for organization)
2. Generate batch of codes (e.g., 100 codes for Video X)
3. Download codes as CSV/PDF
4. Sell codes offline (e.g., 60 codes sold)
5. Students redeem codes (e.g., 30 actually viewed)
6. Invoice client for SOLD codes (60), not views (30)
7. Close batch - set final limit to confirmed sales

## Business Rules

### 1. Authentication Model
- **Guest browsing**: Allowed for viewing courses/videos catalog
- **Code redemption**: Requires registration/login
- **Video playback**: Requires registration + valid code

### 2. Invoicing Model
- **Trigger**: Batch close = invoice
- **Who can close**: System admin always; center admin optionally (configurable per deal)
- **Invoice basis**: "Sold" codes (confirmed at batch close)

### 3. Code Lifecycle
- **After batch close**: Unsold codes auto-deactivate (become invalid)
- **Used codes**: Remain valid for the student who redeemed them

### 4. Batch Model
**Expandable Single Batch per Course Video:**
- One "active" batch per `course_id + video_id` at a time
- Admin can **add more codes** to existing batch (no need to close first)
- When ready to invoice: close batch, confirm sold count, deactivate remaining
- After close: can start a new batch

### 5. View Limits
- **Code-specific limit**: Each batch can specify its own view limit
- **Fallback**: If not specified, use existing hierarchy (Video > Course > Center defaults)
- **Key insight**: Invoicing based on SOLD codes, not views. Low view rate is client's risk.

### 6. Access Model Scope
- **Per-course toggle**: Each course can be either:
  - `access_model: enrollment` (current behavior)
  - `access_model: video_code` (new code-based model)
- Center admin chooses this when creating the course
- `access_model` is immutable after creation; switching models requires creating a new course
- Allows mixing models within same center
- The same video may be attached to multiple courses, including across different access models
- Playback, view limits, extra-view requests, and code redemption remain course-scoped for shared videos

### 7. Multiple Codes per Video
- **Codes stack**: Student can redeem multiple codes for same video
- Each code adds its view limit to the student's total
- Use case: Student forgets old code, buys new one → both should work

### 8. Code Delivery
- **Both formats**: CSV (for digital use and bulk export) + PDF with QR codes (for printed cards)
- **PDF layouts**: Support `4`, `6`, or `8` cards per page only to keep cards and QR codes intact across page breaks
- **WhatsApp delivery**: Admins can send CSV exports directly to a WhatsApp number from the batch screen; send history is tracked in batch metadata

### 9. Batch Close Workflow
- **sold_limit approach**: Admin enters sold count when closing batch
- System sets `batch.sold_limit = X`
- NO individual code invalidation (performance + trust)
- Redemption enforced: `IF redeemed_count >= sold_limit THEN reject`
- Invoice generated based on `sold_limit`

### 10. Limit Reached Behavior
- **Reject + contact admin**: Clear error message
- If student complains: Admin can increase `sold_limit` (follow-up invoice if needed)

### 11. Guest Browsing
- **Full browse, no play**: See courses, sections, video titles
- Cannot play videos
- Prompt to login/register when attempting playback

## Technical Implementation

### Database Schema

#### New Tables

**video_code_batches**
```sql
CREATE TABLE video_code_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    center_id BIGINT UNSIGNED NOT NULL,
    batch_code VARCHAR(16) NOT NULL UNIQUE,
    secret_key VARCHAR(64) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    sold_limit INT UNSIGNED NULL,
    redeemed_count INT UNSIGNED DEFAULT 0,
    view_limit_per_code INT UNSIGNED DEFAULT 2,
    status TINYINT DEFAULT 0,
    generated_by BIGINT UNSIGNED NOT NULL,
    generated_at TIMESTAMP NOT NULL,
    closed_at TIMESTAMP NULL,
    closed_by BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
);
```

**video_code_redemptions**
```sql
CREATE TABLE video_code_redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    sequence_number INT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    video_access_id BIGINT UNSIGNED NULL,
    redeemed_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES video_code_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (batch_id, sequence_number)
);
```

#### Modified Tables

**courses** - Add `access_model` column
**video_accesses** - Add `total_view_limit` column

### Code Generation Algorithm

Codes are generated algorithmically using HMAC, not stored until redeemed:

```
Code Format: XXXX-XXXX-XXXX
Example:    ABCD-EFGH-JKLM
```

**Benefits:**
- Scales infinitely (no pre-storing millions of codes)
- Minimal storage (only redeemed codes stored)
- Batch record tracks: quantity, sold_limit, redeemed_count
- Backend accepts codes with or without dashes; clients can format every 4 characters for display/input

### API Endpoints

#### Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/centers/{center}/video-code-batches` | List all batches |
| POST | `/admin/centers/{center}/courses/{course}/videos/{video}/code-batches` | Create batch |
| GET | `/admin/centers/{center}/code-batches/{batch}` | Get batch details |
| POST | `/admin/centers/{center}/code-batches/{batch}/expand` | Add more codes |
| POST | `/admin/centers/{center}/code-batches/{batch}/close` | Close with sold_limit |
| GET | `/admin/centers/{center}/code-batches/{batch}/export/csv` | Download CSV |
| POST | `/admin/centers/{center}/code-batches/{batch}/send-whatsapp-csv` | Send CSV to WhatsApp |
| GET | `/admin/centers/{center}/code-batches/{batch}/export/pdf` | Download PDF |
| GET | `/admin/centers/{center}/code-batches/{batch}/statistics` | Redemption stats |
| GET | `/admin/centers/{center}/code-batches/{batch}/redemptions` | Paginated redemption history |

Notes:
- All admin video-access batch routes require backend permission `video_access.manage`.
- Admin course pickers can filter to valid video-code courses with `GET /admin/centers/{center}/courses?access_model=video_code`.
- Batch list search supports `search` across `batch_code`, course title, and video title.
- Shared videos are supported across multiple courses; open batch checks are evaluated per `course_id + video_id`.

#### Mobile Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/mobile/video-codes/redeem` | Redeem a code |
| POST | `/mobile/video-codes/validate` | Validate without redeeming |
| GET | `/mobile/video-codes/my-redemptions` | List student's redeemed codes |

## Files Created

### Enums
- `app/Enums/CourseAccessModel.php`
- `app/Enums/VideoCodeBatchStatus.php`

### Models
- `app/Models/VideoCodeBatch.php`
- `app/Models/VideoCodeRedemption.php`

### Services
- `app/Services/VideoAccess/VideoCodeGenerator.php`
- `app/Services/VideoAccess/VideoCodeBatchService.php`
- `app/Services/VideoAccess/VideoCodeRedemptionService.php`
- `app/Services/VideoAccess/Contracts/VideoCodeBatchServiceInterface.php`
- `app/Services/VideoAccess/Contracts/VideoCodeRedemptionServiceInterface.php`

### Controllers
- `app/Http/Controllers/Admin/VideoCodeBatchController.php`
- `app/Http/Controllers/Mobile/VideoCodeController.php` (updated)

### Form Requests
- `app/Http/Requests/Admin/VideoAccess/CreateVideoCodeBatchRequest.php`
- `app/Http/Requests/Admin/VideoAccess/ExpandVideoCodeBatchRequest.php`
- `app/Http/Requests/Admin/VideoAccess/CloseVideoCodeBatchRequest.php`
- `app/Http/Requests/Admin/VideoAccess/ListVideoCodeBatchRedemptionsRequest.php`
- `app/Http/Requests/Mobile/RedeemVideoCodeRequest.php`
- `app/Http/Requests/Mobile/ValidateVideoCodeRequest.php`

### Resources
- `app/Http/Resources/Admin/VideoAccess/VideoCodeBatchResource.php`
- `app/Http/Resources/Admin/VideoAccess/VideoCodeBatchStatisticsResource.php`
- `app/Http/Resources/Admin/VideoAccess/VideoCodeBatchRedemptionResource.php`
- `app/Http/Resources/Mobile/VideoCodeRedemptionResource.php`

### Migrations
- `database/migrations/XXXX_create_video_code_batches_table.php`
- `database/migrations/XXXX_create_video_code_redemptions_table.php`
- `database/migrations/XXXX_add_access_model_to_courses_table.php`
- `database/migrations/XXXX_add_total_view_limit_to_video_accesses_table.php`

### Factories
- `database/factories/VideoCodeBatchFactory.php`
- `database/factories/VideoCodeRedemptionFactory.php`

### Tests
- `tests/Feature/Admin/VideoCodeBatchTest.php`
- `tests/Feature/Mobile/VideoCodeRedemptionTest.php`
- `tests/Unit/VideoCodeGeneratorTest.php`

## Mobile API Integration

### Minimal Mobile Changes Required

**Good news:** The backend handles all complex logic. Mobile only needs minimal changes.

### Response Fields (Course Listing & Details)

| Field | Type | Description |
|-------|------|-------------|
| `is_enrolled` | boolean | **TRUE if student has access** (works for both models) |
| `has_access` | boolean | Same as `is_enrolled` (alias for clarity) |
| `access_model` | string | `enrollment` or `video_code` - use for UI customization |

**Key Point:** `is_enrolled` now returns `true` for BOTH:
- Enrollment model: student has active enrollment
- Video code model: student has redeemed at least one video code

### What Mobile Needs to Do

#### Option A: Zero Changes (Basic)
If you just want it to work:
- **No changes needed!**
- `is_enrolled = true` means student has access
- Continue using `is_enrolled` as before

#### Option B: Minimal Changes (Better UX)
For better user experience, use `access_model` to customize labels:

```dart
// Example: Customize button/label based on access model
String getAccessLabel(Course course) {
  if (!course.isEnrolled) {
    return course.accessModel == 'video_code'
      ? 'Enter Code'      // For video_code courses
      : 'Enroll Now';     // For enrollment courses
  }
  return course.accessModel == 'video_code'
    ? 'Accessed'          // Student has redeemed codes
    : 'Enrolled';         // Student is enrolled
}
```

---

## The Two Access Flows Explained

### Flow 1: Enrollment Model (`access_model: "enrollment"`)

This is the **existing flow** - no changes needed.

```
┌─────────────────────────────────────────────────────────────┐
│                    ENROLLMENT FLOW                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Student browses courses                                 │
│     └─ is_enrolled: false                                   │
│                                                             │
│  2. Student requests enrollment                             │
│     └─ POST /centers/{center}/courses/{course}/enroll      │
│                                                             │
│  3. Admin approves enrollment                               │
│     └─ is_enrolled: true                                    │
│                                                             │
│  4. Student can access course videos                        │
│     └─ If requires_video_approval: true                     │
│        └─ Videos show access_status: locked/pending/granted │
│        └─ Student may need to request video access          │
│     └─ If requires_video_approval: false                    │
│        └─ Videos are immediately accessible                 │
│                                                             │
│  5. Playback                                                │
│     └─ Normal playback with view limits                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Video States (Enrollment Model):**
- `access_status: "locked"` - No access, show "Request Access" button
- `access_status: "pending"` - Request submitted, show "Pending" state
- `access_status: "approved"` - Code sent, show "Enter Code" button
- `access_status: "granted"` - Access granted, can play video

---

### Flow 2: Video Code Model (`access_model: "video_code"`)

This is the **new flow** for offline code sales.

```
┌─────────────────────────────────────────────────────────────┐
│                    VIDEO CODE FLOW                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Student browses courses                                 │
│     └─ is_enrolled: false (no codes redeemed yet)          │
│     └─ access_model: "video_code"                          │
│                                                             │
│  2. Student buys code offline (from center)                 │
│     └─ Physical card or digital code                        │
│     └─ Code format: ABCD-EFGH-JKLM                         │
│                                                             │
│  3. Student redeems code in app                             │
│     └─ POST /mobile/video-codes/redeem                      │
│     └─ Body: { "code": "ABCD-EFGH-JKLM" }                  │
│     └─ Response: video details + access granted             │
│                                                             │
│  4. After first redemption                                  │
│     └─ is_enrolled: true (has access to course)            │
│     └─ That specific video: access_status: "granted"        │
│     └─ Other videos: access_status: "locked"                │
│                                                             │
│  5. Playback                                                │
│     └─ Normal playback with view limits                     │
│     └─ View limit comes from the code's batch settings      │
│                                                             │
│  6. Multiple codes (optional)                               │
│     └─ Student can redeem more codes for same video         │
│     └─ View limits STACK (add up)                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Video States (Video Code Model):**
- `access_status: "locked"` - No code redeemed, show "Enter Code" button
- `access_status: "granted"` - Code redeemed, can play video

**Note:** No "pending" or "approved" states - it's simpler. Either you have a code or you don't.

---

## Mobile Screens Mapping

### Course List / Explore Screen

| Field | Usage |
|-------|-------|
| `is_enrolled` | Show "Enrolled" badge or highlight |
| `access_model` | (Optional) Show different icon/label |

**No changes needed** - `is_enrolled` works for both models.

### Course Details Screen

| Scenario | Enrollment Model | Video Code Model |
|----------|------------------|------------------|
| Not accessed | Show "Enroll" button | Show "Enter Code" button |
| Has access | Show "Enrolled" badge | Show "Accessed" badge |

**Minimal change:** Check `access_model` to show appropriate button text.

### Video List (in Course)

| Field | Usage |
|-------|-------|
| `is_locked` | Can student play this video? |
| `access_status` | What state is the video in? |
| `requires_redemption` | Does this video need code/approval? |

| access_status | Enrollment Model | Video Code Model |
|---------------|------------------|------------------|
| `locked` | Show "Request Access" | Show "Enter Code" |
| `pending` | Show "Pending Approval" | N/A |
| `approved` | Show "Enter Code" | N/A |
| `granted` | Show Play button | Show Play button |

### Code Entry Screen (NEW for video_code)

For `video_code` courses, add a simple code entry:

```
POST /mobile/video-codes/redeem
Body: { "code": "ABCD-EFGH-JKLM" }

Success Response:
{
  "success": true,
  "data": {
    "video_id": 123,
    "video_title": "Lesson 1",
    "course_id": 456,
    "view_limit": 3,
    "message": "Code redeemed successfully"
  }
}

Error Response:
{
  "success": false,
  "error": {
    "code": "VIDEO_CODE_INVALID",
    "message": "Invalid code format or code not found"
  }
}
```

**Possible error codes:**
- `VIDEO_CODE_INVALID` - Bad format or not found
- `VIDEO_CODE_ALREADY_REDEEMED` - Someone else used this code
- `VIDEO_CODE_BATCH_LIMIT_REACHED` - Batch sold limit reached
- `VIDEO_CODE_BATCH_CLOSED` - Batch is closed

---

## API Endpoints Summary

### Existing (No Changes)
- `GET /mobile/courses/explore` - Browse courses
- `GET /mobile/centers/{center}/courses/{course}` - Course details
- `GET /mobile/courses/enrolled` - My courses (now includes video_code courses!)

### New Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/mobile/video-codes/redeem` | Redeem a video code |
| `POST` | `/mobile/video-codes/validate` | Validate code without redeeming |
| `GET` | `/mobile/video-codes/my-redemptions` | List student's redeemed codes |

---

## Quick Decision Tree for Mobile

```
Is course.access_model == "video_code"?
│
├─ YES (Video Code Model)
│   │
│   ├─ course.is_enrolled == false?
│   │   └─ Show "Enter Code" button on course
│   │
│   ├─ video.access_status == "locked"?
│   │   └─ Show "Enter Code" button on video
│   │
│   └─ video.access_status == "granted"?
│       └─ Show Play button
│
└─ NO (Enrollment Model - existing flow)
    │
    └─ Use existing logic (no changes)
```

## Files Modified

- `app/Models/Course.php` - Added `access_model` field, `videoCodeBatches` relationship, access scopes
- `app/Models/VideoAccess.php` - Added `total_view_limit` field
- `app/Services/Playback/PlaybackAuthorizationService.php` - Support code-based access
- `app/Services/Playback/ViewLimitService.php` - Use total_view_limit for stacking
- `app/Services/Courses/CourseService.php` - Use `accessibleBy` scope for enrolled endpoints
- `app/Http/Resources/Mobile/ExploreCourseResource.php` - Added `access_model`, `has_access` fields
- `app/Http/Resources/Mobile/CourseDetailsResource.php` - Added `access_model`, `has_access` fields
- `app/Http/Resources/Mobile/CourseVideoResource.php` - Updated for video_code model handling
- `app/Providers/AppServiceProvider.php` - Register new services
- `app/Support/ErrorCodes.php` - Add new error codes
- `routes/api/v1/admin/video-code-batches.php` - New admin routes
- `routes/api/v1/mobile.php` - Updated mobile routes
