# Playback System

> Video playback session management with Bunny Stream integration.

## Overview

The playback system manages video viewing sessions, including:
- Bunny Stream embed token generation
- Session lifecycle (start, progress, end)
- Concurrent device prevention
- View counting for limits

---

## Database Schema

### Table: `playback_sessions`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK | Student viewing the video |
| `video_id` | FK | Video being watched |
| `course_id` | FK | Course context (nullable) |
| `enrollment_id` | FK | Enrollment context (nullable) |
| `device_id` | FK | Device session is bound to |
| `embed_token` | text | Bunny embed token hash |
| `embed_token_expires_at` | timestamp | Token expiration |
| `started_at` | timestamp | Session start |
| `ended_at` | timestamp | Session end (null = active) |
| `expires_at` | timestamp | Session timeout |
| `progress_percent` | int | 0-100 progress |
| `is_full_play` | bool | True when reached 80% |
| `auto_closed` | bool | True if closed by timeout/max_views |
| `is_locked` | bool | True when no remaining views |
| `watch_duration` | int | Total seconds watched |
| `close_reason` | varchar(20) | user/timeout/max_views |
| `last_activity_at` | timestamp | Last progress update time |

**Key relationships:**
- `user` → User (student)
- `video` → Video
- `course` → Course
- `enrollment` → Enrollment
- `device` → UserDevice

---

## Service Layer

### PlaybackService

**Location:** `app/Services/Playback/PlaybackService.php`

#### `requestPlayback(User, Center, Course, Video): array`

Starts a new playback session.

**Flow:**
1. Validate authorization via `PlaybackAuthorizationService`
2. Get active device
3. Resolve enrollment ID
4. Generate Bunny embed token
5. In transaction:
   - Close expired sessions
   - Check for active session on different device → `CONCURRENT_DEVICE`
   - Close active session on same device
   - Create new session
6. Return session data

**Returns:**
```php
[
    'library_id' => '55',
    'video_uuid' => 'abc-123-def',
    'embed_token' => 'sha256hash...',
    'embed_token_expires' => 1705320240,
    'embed_token_expires_at' => '2024-01-15T10:04:00+00:00',
    'session_id' => '123',
    'expires_in' => 240,
]
```

#### `refreshEmbedToken(User, Center, Course, Video, PlaybackSession): array`

Refreshes an expired embed token for an active session.

**Returns:**
```php
[
    'token' => 'newsha256hash...',
    'expires_in' => 240,
    'expires_at' => '2024-01-15T10:08:00+00:00',
]
```

#### `updateProgress(User, PlaybackSession, int): array`

Updates session progress percentage and returns status.

**Logic:**
- Ignores if session belongs to different user
- Ignores if session ended
- Ignores if session expired
- Updates `last_activity_at` and extends `expires_at` on every call
- If new progress > current progress:
  - Updates `progress_percent`
  - Sets `is_full_play = true` if `percentage >= 80`
  - Checks if video is locked after full play

**Returns:**
```php
[
    'progress' => 80,
    'is_full_play' => true,
    'is_locked' => false,
    'remaining_views' => 2,
]
```

#### `closeSession(int $sessionId, int $watchDuration, string $reason): void`

Closes a playback session.

**Parameters:**
- `$sessionId` - Session ID to close
- `$watchDuration` - Total seconds watched
- `$reason` - Close reason: `user`, `timeout`, `max_views`

**Logic:**
- Returns silently if session not found or already ended
- Sets `ended_at`, `watch_duration`, `close_reason`
- Sets `auto_closed = true` for `timeout` or `max_views` reasons

---

### PlaybackAuthorizationService

**Location:** `app/Services/Playback/PlaybackAuthorizationService.php`

#### `assertCanStartPlayback(User, Center, Course, Video): void`

Validates all prerequisites for starting playback:
- User is a student
- User has access to center
- Course belongs to center
- Video is attached to course
- Course is published (status = 3)
- Video is ready (encoding_status = 3, lifecycle_status = 2)
- Upload session is complete (upload_status = 3)
- User has active enrollment
- User has remaining views

#### `assertCanRefreshToken(User, Center, Course, Video, PlaybackSession): void`

Validates token refresh:
- User is a student
- Session exists and not ended
- Session belongs to user
- All context validations pass

#### `assertCanUpdateProgress(User, Center, Course, Video, PlaybackSession): void`

Validates progress update:
- Session exists
- Context is valid
- User is a student
- Session belongs to user
- Session not ended
- Video is ready
- Enrollment is active

---

### BunnyEmbedTokenService

**Location:** `app/Services/Bunny/BunnyEmbedTokenService.php`

#### `generate(string $videoUuid, User, int $centerId, int $enrollmentId, int $ttl): array`

Generates Bunny embed token using SHA256.

**Algorithm:**
```php
$expiresAt = now()->addSeconds($ttl)->timestamp;
$token = hash('sha256', $secret . $videoUuid . $expiresAt);
```

**Returns:**
```php
['token' => 'hash...', 'expires' => 1705320240]
```

---

## API Endpoints

### POST `/api/v1/centers/{center}/courses/{course}/videos/{video}/request_playback`

**Request:** No body required

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "library_id": "55",
        "video_uuid": "abc-123-def",
        "embed_token": "sha256hash...",
        "embed_token_expires": 1705320240,
        "embed_token_expires_at": "2024-01-15T10:04:00+00:00",
        "session_id": "123",
        "expires_in": 240
    }
}
```

**Error Responses:**
| Code | HTTP | Cause |
|------|------|-------|
| `ENROLLMENT_REQUIRED` | 403 | No active enrollment |
| `VIEW_LIMIT_EXCEEDED` | 403 | No views remaining |
| `CONCURRENT_DEVICE` | 409 | Active session on another device |
| `VIDEO_NOT_READY` | 422 | Video not encoded |
| `CENTER_MISMATCH` | 403 | Wrong center access |

---

### POST `/api/v1/centers/{center}/courses/{course}/videos/{video}/refresh_token`

**Request Body:**
```json
{
    "session_id": 123
}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "embed_token": "newsha256hash...",
        "expires_in": 240,
        "expires_at": "2024-01-15T10:08:00+00:00"
    }
}
```

**Error Responses:**
| Code | HTTP | Cause |
|------|------|-------|
| `SESSION_NOT_FOUND` | 404 | Invalid session_id |
| `SESSION_ENDED` | 409 | Session already ended |
| `UNAUTHORIZED` | 403 | Session belongs to another user |

---

### POST `/api/v1/centers/{center}/courses/{course}/videos/{video}/playback_progress`

**Request Body:**
```json
{
    "session_id": 123,
    "percentage": 50
}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "progress": 50,
        "is_full_play": false,
        "is_locked": false,
        "remaining_views": 3
    }
}
```

---

### POST `/api/v1/centers/{center}/courses/{course}/videos/{video}/close_session`

**Request Body:**
```json
{
    "session_id": 123,
    "watch_duration": 1234
}
```

**Success Response (200):**
```json
{
    "success": true
}
```

---

## Session Lifecycle

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Session State Machine                            │
└─────────────────────────────────────────────────────────────────────────┘

    ┌──────────┐
    │  START   │
    └────┬─────┘
         │ request_playback
         ▼
    ┌──────────┐     expires_at < now()     ┌──────────┐
    │  ACTIVE  │ ─────────────────────────► │  EXPIRED │
    │          │                            │(auto-end)│
    └────┬─────┘                            └──────────┘
         │
         │ progress updates
         │ (extends expires_at)
         │
         ├──────────────────────────────────────────────────────┐
         │ percentage >= 90                                     │
         ▼                                                      │
    ┌──────────┐                                                │
    │FULL_PLAY │ (is_full_play = true)                          │
    │ COUNTED  │ View now counted toward limit                  │
    └────┬─────┘                                                │
         │                                                      │
         │ new request_playback                                 │
         │ (same device)                                        │
         ▼                                                      ▼
    ┌──────────┐                                          ┌──────────┐
    │  ENDED   │ ◄────────────────────────────────────────│  ENDED   │
    │(replaced)│                                          │ (manual) │
    └──────────┘                                          └──────────┘
```

---

## Timing Configuration

| Setting | Value | Source |
|---------|-------|--------|
| Embed Token TTL | 240 seconds (4 min) | `config('bunny.embed_token_ttl')` |
| Token TTL Range | 180-300 seconds | Clamped in `resolveEmbedTokenTtl()` |
| Session TTL | Configurable | `config('playback.session_ttl')` |
| Full Play Threshold | 80% | Hardcoded in `updateProgress()` |
| Session Timeout | 60 seconds | Default for stale detection |
| Heartbeat Interval | 30 seconds | Recommended client interval |

---

## Client Implementation Guide

### Starting Playback

1. Call `request_playback` to get embed token and session_id
2. Construct Bunny iframe URL:
   ```
   https://iframe.mediadelivery.net/embed/{library_id}/{video_uuid}?token={embed_token}&expires={embed_token_expires}
   ```
3. Load iframe in player

### Token Refresh Loop

1. Set timer for `expires_in - 30` seconds
2. When timer fires, call `refresh_token` with session_id
3. Update iframe URL with new token
4. Repeat

### Progress Reporting

1. Track video progress in player
2. Call `playback_progress` at intervals (recommended: every 10-15 seconds when progress changes)
3. Only send if progress increased
4. Progress updates extend session, acting as heartbeat

### Error Handling

| Error Code | Action |
|------------|--------|
| `CONCURRENT_DEVICE` | Show "playing on another device" message |
| `SESSION_ENDED` | Call `request_playback` to start new session |
| `VIEW_LIMIT_EXCEEDED` | Show "no views remaining" + extra view request option |
| `ENROLLMENT_REQUIRED` | Redirect to enrollment flow |

---

## Cleanup Command

### `playback:close-stale`

Closes sessions with no activity for specified seconds.

```bash
# Close sessions with 60+ seconds of inactivity (default)
./vendor/bin/sail artisan playback:close-stale

# Custom timeout
./vendor/bin/sail artisan playback:close-stale --timeout=120
```

Scheduled to run every minute via Laravel scheduler.

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Services/Playback/PlaybackService.php` | Core playback logic |
| `app/Services/Playback/PlaybackAuthorizationService.php` | Authorization checks |
| `app/Services/Playback/ViewLimitService.php` | View limit calculations |
| `app/Services/Bunny/BunnyEmbedTokenService.php` | Token generation |
| `app/Http/Controllers/Mobile/PlaybackController.php` | HTTP endpoints |
| `app/Http/Requests/Mobile/PlaybackProgressRequest.php` | Progress validation |
| `app/Http/Requests/Mobile/RefreshPlaybackTokenRequest.php` | Refresh validation |
| `app/Http/Requests/Mobile/CloseSessionRequest.php` | Close session validation |
| `app/Models/PlaybackSession.php` | Session model |
| `app/Console/Commands/CloseStalePlaybackSessions.php` | Stale session cleanup |
| `config/bunny.php` | Bunny configuration |
| `config/playback.php` | Playback configuration |

---

## Testing

```bash
# Run playback tests
./vendor/bin/sail test --filter="Playback"

# Test files
tests/Feature/Mobile/PlaybackTest.php
tests/Feature/Mobile/RefreshPlaybackTokenTest.php
tests/Feature/Playback/RequestPlaybackTest.php
tests/Unit/Playback/PlaybackAuthorizationServiceTest.php
```
