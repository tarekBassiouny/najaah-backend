# Claude Context - XYZ LMS Backend

> Quick reference for AI assistants working on this codebase.
> Last updated: January 2026

## Project Overview

**XYZ LMS** is a multi-tenant Learning Management System built with Laravel 11 and PHP 8.4.

### Key Characteristics
- **Multi-tenant**: Centers (tenants) with branded/unbranded modes
- **Mobile-first**: JWT authentication for mobile students
- **Video streaming**: Bunny Stream integration with embed tokens
- **View limits**: Per-video view restrictions with extra view requests
- **Device binding**: One active device per student

### Tech Stack
- Laravel 11 / PHP 8.4
- MySQL (via Laravel Sail)
- Bunny Stream (video hosting)
- JWT authentication (PHPOpenSourceSaver/jwt-auth)
- Pest PHP (testing)
- Scribe (API documentation)

---

## Key Services & File Paths

### Playback System
| Component | Path |
|-----------|------|
| PlaybackService | `app/Services/Playback/PlaybackService.php` |
| PlaybackAuthorizationService | `app/Services/Playback/PlaybackAuthorizationService.php` |
| ViewLimitService | `app/Services/Playback/ViewLimitService.php` |
| ExtraViewRequestService | `app/Services/Playback/ExtraViewRequestService.php` |
| BunnyEmbedTokenService | `app/Services/Bunny/BunnyEmbedTokenService.php` |
| PlaybackController | `app/Http/Controllers/Mobile/PlaybackController.php` |

### Device Management
| Component | Path |
|-----------|------|
| DeviceService | `app/Services/Devices/DeviceService.php` |
| DeviceChangeService | `app/Services/Devices/DeviceChangeService.php` |
| JwtMobileMiddleware | `app/Http/Middleware/JwtMobileMiddleware.php` |

### Settings System
| Component | Path |
|-----------|------|
| SettingsResolverService | `app/Services/Settings/SettingsResolverService.php` |
| CenterScopeService | `app/Services/Centers/CenterScopeService.php` |

### Models
| Model | Path |
|-------|------|
| PlaybackSession | `app/Models/PlaybackSession.php` |
| UserDevice | `app/Models/UserDevice.php` |
| ExtraViewRequest | `app/Models/ExtraViewRequest.php` |
| DeviceChangeRequest | `app/Models/DeviceChangeRequest.php` |
| CourseVideo (pivot) | `app/Models/Pivots/CourseVideo.php` |

---

## Database Tables Summary

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `playback_sessions` | Track video viewing | user_id, video_id, course_id, device_id, embed_token, progress_percent, is_full_play, is_locked, auto_closed, watch_duration, close_reason, last_activity_at, expires_at |
| `user_devices` | Device registration | user_id, device_id (UUID), model, os_version, status (0/1/2) |
| `device_change_requests` | Device change workflow | user_id, current_device_id, new_device_id, status |
| `extra_view_requests` | Extra view workflow | user_id, video_id, course_id, granted_views, status |
| `course_video` | Video-course pivot | course_id, video_id, view_limit_override, visible |
| `center_settings` | Center JSON settings | center_id, settings (JSON) |
| `course_settings` | Course JSON settings | course_id, settings (JSON) |
| `video_settings` | Video JSON settings | video_id, settings (JSON) |
| `student_settings` | Student JSON settings | user_id, settings (JSON) |

---

## API Response Format

### Success Response
```json
{
    "success": true,
    "data": { ... }
}
```

### Success with Pagination
```json
{
    "success": true,
    "data": [ ... ],
    "meta": {
        "page": 1,
        "per_page": 15,
        "total": 100
    }
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human readable message"
    }
}
```

### Error Codes (`app/Support/ErrorCodes.php`)
| Code | HTTP | Description |
|------|------|-------------|
| `NOT_FOUND` | 404 | Resource not found |
| `UNAUTHORIZED` | 403 | Not authorized for action |
| `CENTER_MISMATCH` | 403 | Student/center access violation |
| `DEVICE_MISMATCH` | 403 | Device not active for user |
| `CONCURRENT_DEVICE` | 409 | Playback active on another device |
| `ENROLLMENT_REQUIRED` | 403 | No active enrollment |
| `VIEW_LIMIT_EXCEEDED` | 403 | No views remaining |
| `VIDEO_NOT_READY` | 422 | Video not encoded/ready |
| `SESSION_NOT_FOUND` | 404 | Playback session not found |
| `SESSION_ENDED` | 409 | Session already ended |
| `NO_ACTIVE_DEVICE` | 422 | No registered device |
| `PENDING_REQUEST_EXISTS` | 422 | Duplicate pending request |
| `INVALID_STATE` | 409 | Invalid state transition |

---

## Business Rules

### View Counting
- **Full play threshold**: 80% (`is_full_play = percentage >= 80`)
- **View counted once**: Only when `is_full_play` becomes true
- **Calculation**: `remaining = limit - count(is_full_play=true)`
- **Lock detection**: `is_locked` flag set when remaining views reach 0

### Device Policy
- **One active device per student**
- **Device registered on first login**
- **Change requires admin approval**
- **JWT tokens bound to device_id**

### Session Lifecycle
- **Token TTL**: 240 seconds (4 minutes), clamped 180-300
- **Session TTL**: Configured via `config('playback.session_ttl')`
- **Session timeout**: 60 seconds of inactivity (configurable)
- **Heartbeat interval**: 30 seconds recommended
- **Progress extends session**: Each update resets expiry and last_activity_at
- **Concurrent blocking**: Only one active session per user
- **Auto-close reasons**: `timeout`, `user`, `max_views`

### Settings Hierarchy (later overrides earlier)
1. Center defaults (table columns)
2. CenterSetting (JSON)
3. CourseSetting (JSON)
4. VideoSetting (JSON)
5. StudentSetting (JSON)

---

## Implementation Status

### Implemented
- [x] Playback session management
- [x] Bunny embed token generation & refresh
- [x] Progress tracking with full play detection
- [x] View limit enforcement
- [x] Extra view request workflow
- [x] Device registration & validation
- [x] Device change request workflow
- [x] Settings hierarchy resolution
- [x] JWT mobile authentication
- [x] Center-scoped access control

### Missing / Gaps
- [ ] Device limit enforcement (setting exists, not enforced)

### Recently Implemented
- [x] `is_locked` flag computation for videos (in updateProgress)
- [x] Remaining views in API responses (requestPlayback, updateProgress)
- [x] Session close endpoint (`close_session`)
- [x] Device fingerprint for reinstall detection
- [x] Stale session cleanup command (`playback:close-stale`)

---

## Code Conventions

### Models
- PHPDoc `@property` annotations for all columns
- `SoftDeletes` trait on most models
- Status constants (e.g., `STATUS_ACTIVE = 0`)
- Relationship methods with return type hints

### Services
- Constructor injection via `__construct()`
- Private `deny()` helper throwing `DomainException`
- Return array types with PHPDoc shapes
- No direct model instantiation in controllers

### Controllers
- Type-hinted FormRequest injection
- Route model binding for entities
- Return `JsonResponse` with success wrapper
- Authorization delegated to services

### FormRequests
- `authorize()` always returns `true`
- Authorization done in controller/service
- `bodyParameters()` for Scribe docs
- `queryParameters()` for GET endpoints
- `filters()` method returning DTO for complex queries

### Migrations
- Foreign keys with `constrained()->cascadeOnUpdate()`
- Soft deletes on most tables
- Composite indexes for common queries

---

## Quick Commands

```bash
# Run tests
./vendor/bin/sail test

# Run specific test group
./vendor/bin/sail test --filter="Playback"

# Lint (Pint + PHPStan)
./vendor/bin/sail composer lint

# Full quality check
./vendor/bin/sail composer quality

# Generate API docs
./vendor/bin/sail artisan scribe:generate

# Fresh migrate
./vendor/bin/sail artisan migrate:fresh --seed
```

---

## Related Documentation

- [Database Schema](./architecture/DATABASE_SCHEMA.md)
- [Scribe Patterns](./api/SCRIBE_PATTERNS.md)
- [Playback System](./features/PLAYBACK.md)
- [Device Management](./features/DEVICE_MANAGEMENT.md)
- [View Limits](./features/VIEW_LIMITS.md)
- [Settings System](./features/SETTINGS.md)
- [Feature Planning Template](./plans/TEMPLATE.md)
