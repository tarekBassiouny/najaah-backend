# View Limits System

> View limit enforcement and extra view request workflow.

## Overview

The view limits system controls how many times a student can watch each video:
- Configurable limits at multiple levels (center, course, video, pivot)
- Extra view requests for approved additional views
- Full play tracking (90% threshold)

---

## View Limit Resolution

### Hierarchy (later overrides earlier)

```
┌─────────────────────────────────────────────────────────────────────┐
│                     Settings Resolution Order                        │
└─────────────────────────────────────────────────────────────────────┘

1. Center defaults (centers.default_view_limit column)
      ↓ overridden by
2. CenterSetting (JSON settings.view_limit)
      ↓ overridden by
3. CourseSetting (JSON settings.view_limit)
      ↓ overridden by
4. VideoSetting (JSON settings.view_limit)
      ↓ overridden by
5. course_video.view_limit_override (pivot table)
```

### Resolution Logic

**Location:** `app/Services/Playback/ViewLimitService.php`

```php
private function resolveLimit(User $user, Video $video, Course $course, ?int $pivotOverride): int
{
    // 1. Get resolved settings via hierarchy
    $settings = $this->settingsResolver->resolve($user, $video, $course, $course->center);
    $limit = $settings['view_limit'] ?? null;

    // 2. Pivot override takes precedence if no settings limit
    if (!is_numeric($limit) && is_numeric($pivotOverride)) {
        $limit = $pivotOverride;
    }

    // 3. Default to 0 (unlimited) if no limit set
    if (!is_numeric($limit)) {
        $limit = 0;
    }

    // 4. Add extra views (approved requests + student settings)
    $extra = $this->resolveExtraViews($user, $video);

    return (int) $limit + $extra;
}
```

---

## Full Play Counting

### Threshold

A view is counted as "full play" when progress reaches **80%**:

```php
// PlaybackService.php, updateProgress()
$isFullPlay = $percentage >= 80 || $session->is_full_play;
```

### Counting Method

```php
// ViewLimitService.php
private function countFullPlays(User $user, Video $video): int
{
    return PlaybackSession::where('user_id', $user->id)
        ->where('video_id', $video->id)
        ->where('is_full_play', true)
        ->whereNull('deleted_at')
        ->count();
}
```

### Calculation

```
remaining_views = total_limit - full_play_count

where:
  total_limit = base_limit + extra_views
  full_play_count = count(playback_sessions WHERE is_full_play = true)
```

---

## Service Layer

### ViewLimitService

**Location:** `app/Services/Playback/ViewLimitService.php`

#### `remaining(User, Video, Course, ?int $pivotOverride): int`

Returns remaining views for a user/video combination.

```php
public function remaining(User $user, Video $video, Course $course, ?int $pivotOverride = null): int
{
    $limit = $this->resolveLimit($user, $video, $course, $pivotOverride);
    $fullPlays = $this->countFullPlays($user, $video);

    return $limit - $fullPlays;
}
```

**Returns:** Remaining views (can be negative if over limit)

#### `assertWithinLimit(User, Video, Course, ?int $pivotOverride): void`

Throws exception if no views remaining.

```php
public function assertWithinLimit(User $user, Video $video, Course $course, ?int $pivotOverride = null): void
{
    if ($this->remaining($user, $video, $course, $pivotOverride) <= 0) {
        throw new DomainException('View limit exceeded.', ErrorCodes::VIEW_LIMIT_EXCEEDED, 403);
    }
}
```

---

## Extra Views

### Sources of Extra Views

1. **StudentSetting** - Manually granted by admin:
   ```json
   {
     "extra_views": {
       "123": 5,  // video_id => extra count
       "456": 3
     }
   }
   ```

2. **ExtraViewRequest** - Approved requests:
   ```sql
   SELECT SUM(granted_views) FROM extra_view_requests
   WHERE user_id = ? AND video_id = ? AND status = 'APPROVED'
   ```

### Resolution

```php
private function resolveExtraViews(User $user, Video $video): int
{
    // From student settings
    $settings = $user->studentSetting?->settings ?? [];
    $extraViews = $settings['extra_views'][$video->id] ?? null;
    $base = is_numeric($extraViews) ? (int) $extraViews : 0;

    // From approved requests
    $approved = ExtraViewRequest::where('user_id', $user->id)
        ->where('video_id', $video->id)
        ->where('status', ExtraViewRequest::STATUS_APPROVED)
        ->whereNull('deleted_at')
        ->sum('granted_views');

    return $base + (int) $approved;
}
```

---

## Extra View Request Workflow

### Database Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK | Requesting student |
| `video_id` | FK | Video requesting views for |
| `course_id` | FK | Course context |
| `center_id` | FK | Center for admin scope |
| `status` | varchar | PENDING/APPROVED/REJECTED |
| `reason` | text | Student's reason |
| `granted_views` | int | Views granted (on approval) |
| `decision_reason` | text | Admin's reason |
| `decided_by` | FK | Admin user |
| `decided_at` | timestamp | Decision time |

### Status Flow

```
┌──────────┐     approve(granted_views)     ┌──────────┐
│ PENDING  │ ────────────────────────────► │ APPROVED │
│          │                                │          │
└────┬─────┘                                └──────────┘
     │
     │ reject(reason)
     ▼
┌──────────┐
│ REJECTED │
└──────────┘
```

### Service Methods

**Location:** `app/Services/Playback/ExtraViewRequestService.php`

#### `create(User $student, Course $course, Video $video, ?string $reason)`

Creates a new request.

**Validations:**
- User must be a student
- Must have active enrollment
- Video must be in course
- No pending request exists for this video

#### `approve(User $admin, ExtraViewRequest $request, int $grantedViews, ?string $reason)`

Approves with specified view count.

**Validations:**
- Admin must have access to center
- Request must be PENDING
- `granted_views > 0`

#### `reject(User $admin, ExtraViewRequest $request, ?string $reason)`

Rejects the request.

---

## API Endpoints

### Student Endpoint

**POST** `/api/v1/centers/{center}/courses/{course}/videos/{video}/extra-view`

Creates an extra view request.

**Request:**
```json
{
    "reason": "Need more time to finish the lecture."
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "status": "PENDING",
        "reason": "Need more time..."
    }
}
```

### Admin Endpoints

| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/api/v1/admin/extra-view-requests` | List requests |
| POST | `/api/v1/admin/extra-view-requests/{id}/approve` | Approve |
| POST | `/api/v1/admin/extra-view-requests/{id}/reject` | Reject |

**Approve Request:**
```json
{
    "granted_views": 3,
    "decision_reason": "Verified need"
}
```

---

## Error Codes

| Code | HTTP | Cause |
|------|------|-------|
| `VIEW_LIMIT_EXCEEDED` | 403 | No remaining views |
| `VIEWS_AVAILABLE` | 422 | Requesting extra when views remain |
| `PENDING_REQUEST_EXISTS` | 422 | Already has pending request for video |
| `ENROLLMENT_REQUIRED` | 403 | No active enrollment |
| `VIDEO_NOT_IN_COURSE` | 404 | Video not attached to course |
| `INVALID_VIEWS` | 422 | Granted views <= 0 |
| `INVALID_STATE` | 409 | Request not in PENDING state |

---

## Configuration

### Center-Level Defaults

In `centers` table:
- `default_view_limit` - Default views per video
- `allow_extra_view_requests` - Whether students can request extra

### Course-Video Override

In `course_video` pivot:
- `view_limit_override` - Override for specific video in course

### JSON Settings Keys

In any settings table (`center_settings`, `course_settings`, `video_settings`, `student_settings`):
```json
{
    "view_limit": 5,
    "allow_extra_view_requests": true
}
```

---

## Implementation Notes

### Unlimited Views

If `view_limit` resolves to `0` or `null`, views are unlimited:
```php
if (!is_numeric($limit)) {
    $limit = 0;  // 0 = unlimited in remaining() calculation
}
```

### Soft Delete Handling

All queries include `whereNull('deleted_at')` to respect soft deletes.

### Multiple Extra Requests

Students can have multiple approved requests for the same video. All `granted_views` are summed.

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Services/Playback/ViewLimitService.php` | Limit calculations |
| `app/Services/Playback/ExtraViewRequestService.php` | Request workflow |
| `app/Services/Settings/SettingsResolverService.php` | Settings hierarchy |
| `app/Models/ExtraViewRequest.php` | Request model |
| `app/Models/Pivots/CourseVideo.php` | Pivot with override |
| `app/Http/Controllers/Mobile/ExtraViewRequestController.php` | Student endpoint |
| `app/Http/Controllers/Admin/ExtraViewRequestController.php` | Admin endpoints |

---

## Testing

```bash
# Run view limit tests
./vendor/bin/sail test --filter="ViewLimit"
./vendor/bin/sail test --filter="ExtraView"

# Test files
tests/Feature/Admin/ExtraViewRequestTest.php
tests/Feature/Mobile/ExtraViewRequestTest.php
tests/Unit/Playback/ViewLimitServiceTest.php
```
