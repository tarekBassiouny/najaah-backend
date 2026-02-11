# Najaah LMS - Master Project Skill

## Purpose
Comprehensive knowledge base for the Najaah Learning Management System. This skill provides Claude and Claude Code with complete context about the project architecture, business rules, and development standards.

## When to Use This Skill
- Starting any new feature development
- Reviewing or refactoring existing code
- Understanding system architecture
- Resolving technical questions about the project
- Creating documentation or specifications

---

## Project Overview

**Najaah LMS** is a multi-tenant Learning Management System connecting Centers (education providers) with Students through video-based courses.

### Core Characteristics
- **Multi-tenant SaaS**: Centers operate as isolated tenants with branded/unbranded modes
- **Mobile-first**: Primary interface is Flutter mobile app with JWT authentication
- **Video streaming**: Bunny Stream CDN integration with DRM protection (Widevine)
- **Strict access control**: Device binding, view limits, concurrent session prevention
- **Settings hierarchy**: Student > Video > Course > Center overrides

### Tech Stack
```
Backend:  Laravel 11 / PHP 8.4
Database: MySQL 8 (via Laravel Sail)
CDN:      Bunny Stream (video) + Bunny CDN (storage)
Auth:     JWT (PHPOpenSourceSaver/jwt-auth) for mobile
          Sanctum SPA for admin web
Testing:  Pest PHP
API Docs: Scribe
```

---

## System Architecture

### Multi-Tenancy Model

**Two Center Types:**
1. **Branded Centers**: Own subdomain, isolated student accounts
2. **Unbranded Centers**: Under Najaah.com, shared student identity

**Data Isolation:**
- All tables include `center_id` for tenant scoping
- `CenterScopeService` ensures queries are scoped
- Foreign keys cascade on delete/update
- Soft deletes on all tables

### User Roles & Access

```
Role Hierarchy:
├── Super Admin (global access)
├── Center Owner (full center access)
├── Center Admin (manages students, enrollments, devices)
├── Content Manager (courses, videos, PDFs only)
└── Student (mobile consumption)
```

**Identity Rules:**
- Branded: Students isolated per center
- Unbranded: Students shared across all unbranded centers

### Authentication Flow

**Mobile (Students):**
```
1. Phone + OTP login
2. Device registration (UUID + fingerprint)
3. JWT access token (15-60 min) + refresh token (30-90 days)
4. Token bound to device_id
5. Every request validates device status
```

**Web (Admin):**
```
1. Email + password
2. Laravel Sanctum SPA cookies
3. HttpOnly, secure session
```

---

## Core Domain Models

### Course Hierarchy
```
Course
  ├── Sections
  │     ├── Videos (many-to-many via course_video)
  │     └── PDFs (many-to-many via course_pdf)
  └── Settings (JSON overrides)
```

**Course Statuses:**
- 0: DRAFT - Work in progress
- 1: UPLOADING - Content being uploaded
- 2: READY - Encoded, not published
- 3: PUBLISHED - Live to students
- 4: ARCHIVED - Hidden, historical

### Video Lifecycle
```
Video Creation → Uploading → Encoding → Ready → Published
                                              ↓
                                         Archived/Deleted
```

**Video Statuses:**
- `encoding_status`: 0=pending, 1=processing, 2=failed, 3=ready
- `lifecycle_status`: 0=draft, 1=uploading, 2=ready, 3=published, 4=archived, 5=deleted
- `upload_status`: 0=not_started, 1=in_progress, 2=failed, 3=completed

### Playback Sessions

**Session Lifecycle:**
```
request_playback → ACTIVE (with embed_token)
                    ↓
                progress_updates (extends expires_at)
                    ↓
                percentage >= 80% → is_full_play = true (view counted)
                    ↓
                close_session OR timeout → ENDED
```

**Timing Rules:**
- Embed token TTL: 240 seconds (4 min), clamped 180-300
- Session timeout: 60 seconds of inactivity
- Heartbeat interval: 30 seconds (recommended)
- Full play threshold: 80% progress
- Session extends on each progress update

### Device Management

**One Device Policy:**
```
First Login:
  → Register device (UUID + model + OS)
  → Set STATUS_ACTIVE
  → Issue JWT bound to device_id

Different Device Login:
  → DEVICE_MISMATCH error
  → Student submits DeviceChangeRequest
  → Admin approves/rejects
  → On approval: old device → REVOKED, new device → ACTIVE

Reinstall Detection:
  → Same device model detected
  → Update device_id (keep ACTIVE)
  → Create audit log
  → No change request needed
```

**Device Statuses:**
- 0: STATUS_ACTIVE - Device authorized
- 1: STATUS_REVOKED - Deactivated
- 2: STATUS_PENDING - Awaiting approval

### View Limits

**View Counting Logic:**
```
Default limit: 2 views per video
View counted when: is_full_play = true (≥80% progress)
Remaining = limit - count(is_full_play sessions)

Hierarchy (last wins):
  Center default_view_limit
    → CenterSetting JSON
      → CourseSetting JSON
        → CourseVideo.view_limit_override
          → VideoSetting JSON
            → StudentSetting.extra_views
```

**Extra View Workflow:**
```
Student requests → PENDING
  ↓
Admin reviews → APPROVED (grants N views) OR REJECTED
  ↓
extra_views added to StudentSetting JSON
  ↓
ViewLimitService includes in calculation
```

---

## Service Layer Architecture

### Pattern
```
Controller → FormRequest (validation) → Service/Action → Model
            ↓
         Resource (response formatting)
```

**Rules:**
- Controllers: Thin, no business logic
- FormRequests: Validation + authorization
- Services: Business logic, return arrays with type hints
- Actions: Single-purpose operations
- Models: Relationships + casts only

### Key Services

**Playback System:**
```php
PlaybackService
  ├── requestPlayback()     // Start session, generate token
  ├── refreshEmbedToken()   // Refresh expired token
  ├── updateProgress()      // Track progress, detect full play
  └── closeSession()        // End session

PlaybackAuthorizationService
  ├── assertCanStartPlayback()  // Validate all prerequisites
  ├── assertCanRefreshToken()   // Validate token refresh
  └── assertCanUpdateProgress() // Validate progress update

ViewLimitService
  ├── getRemainingViews()   // Calculate remaining for user+video
  └── hasViewsRemaining()   // Boolean check

BunnyEmbedTokenService
  └── generate()            // SHA256 token generation
```

**Device System:**
```php
DeviceService
  ├── register()            // Register/update device on login
  ├── assertActiveDevice()  // Validate device in middleware
  └── handleReinstall()     // Detect reinstall by fingerprint

DeviceChangeService
  ├── create()              // Student creates request
  ├── approve()             // Admin approves (swap devices)
  └── reject()              // Admin rejects
```

**Settings System:**
```php
SettingsResolverService
  └── resolve()             // Hierarchical setting resolution

CenterScopeService
  └── scope()               // Apply center_id scoping
```

---

## Database Design Principles

### Standard Columns (ALL tables)
```php
$table->id();                    // BIGINT UNSIGNED AUTO_INCREMENT
$table->timestamps();            // created_at, updated_at
$table->softDeletes();           // deleted_at
```

### Foreign Key Pattern
```php
$table->foreignId('center_id')
    ->constrained()
    ->cascadeOnUpdate()
    ->cascadeOnDelete();
```

### Status Columns
```php
// Always use integer enums, not strings
const STATUS_ACTIVE = 0;
const STATUS_REVOKED = 1;
const STATUS_PENDING = 2;
```

### Indexing Strategy
```php
// Index ALL foreign keys
$table->index('center_id');
$table->index('user_id');
$table->index(['user_id', 'video_id']); // Composite for lookups

// Index soft deletes
$table->index('deleted_at');

// Unique constraints
$table->unique(['user_id', 'device_id']);
```

### JSON Settings Pattern
```php
// Settings tables store overrides as JSON
{
  "view_limit": 5,
  "pdf_download_permission": true,
  "extra_views": {
    "123": 2,  // video_id: extra_views_granted
    "456": 1
  }
}
```

---

## API Standards

### Versioning
```
All endpoints under: /api/v1/
```

### Request/Response Format

**Success (single resource):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Course Name"
  }
}
```

**Success (collection with pagination):**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message"
  }
}
```

### Error Codes (app/Support/ErrorCodes.php)

| Code | HTTP | Usage |
|------|------|-------|
| NOT_FOUND | 404 | Resource not found |
| UNAUTHORIZED | 403 | Not authorized |
| CENTER_MISMATCH | 403 | Student/center access violation |
| DEVICE_MISMATCH | 403 | Device not active |
| CONCURRENT_DEVICE | 409 | Playback on another device |
| ENROLLMENT_REQUIRED | 403 | No active enrollment |
| VIEW_LIMIT_EXCEEDED | 403 | No views remaining |
| VIDEO_NOT_READY | 422 | Video not encoded |
| SESSION_NOT_FOUND | 404 | Playback session not found |
| SESSION_ENDED | 409 | Session already ended |
| NO_ACTIVE_DEVICE | 422 | No registered device |
| PENDING_REQUEST_EXISTS | 422 | Duplicate pending request |
| INVALID_STATE | 409 | Invalid state transition |

---

## Coding Standards

### PHP 8.4 Requirements
```php
<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\User;
use App\Models\Video;

final readonly class PlaybackService
{
    public function __construct(
        private PlaybackAuthorizationService $authService,
        private BunnyEmbedTokenService $tokenService,
    ) {}

    /**
     * @return array{library_id: string, video_uuid: string, embed_token: string, ...}
     */
    public function requestPlayback(User $user, Video $video): array
    {
        // Implementation
    }
}
```

**Mandatory:**
- `declare(strict_types=1);` on ALL files
- Typed properties
- Final classes for services
- Constructor property promotion
- Readonly where applicable
- Full PHPDoc with array shapes

### Model Standards
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $device_id
 * @property int $status
 */
final class UserDevice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 0;
    public const STATUS_REVOKED = 1;

    protected $fillable = [
        'user_id',
        'device_id',
        'model',
        'os_version',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'approved_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, UserDevice>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### FormRequest Pattern
```php
final class RequestPlaybackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization in service
    }

    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return []; // Scribe documentation
    }
}
```

### Service Exception Pattern
```php
private function deny(string $code, string $message): never
{
    throw new \DomainException(
        json_encode(['code' => $code, 'message' => $message])
    );
}

// Usage:
if (!$this->hasViewsRemaining($user, $video)) {
    $this->deny('VIEW_LIMIT_EXCEEDED', 'No remaining views for this video.');
}
```

---

## Testing Standards

### Coverage Requirements
- **Minimum:** 90% project-wide
- **100% Required:** Auth, money calculations, view limits, imports

### Test Structure
```php
uses(RefreshDatabase::class);

describe('PlaybackService', function () {
    it('creates session when authorized', function () {
        $user = User::factory()->create();
        $video = Video::factory()->create();
        
        $service = app(PlaybackService::class);
        $result = $service->requestPlayback($user, $video);
        
        expect($result)->toHaveKeys(['session_id', 'embed_token']);
    });

    it('throws VIEW_LIMIT_EXCEEDED when no views remain', function () {
        // Setup: user with 0 remaining views
        
        $service = app(PlaybackService::class);
        
        expect(fn() => $service->requestPlayback($user, $video))
            ->toThrow(DomainException::class);
    });
});
```

### Test Commands
```bash
# Run all tests
./vendor/bin/sail test

# Run with coverage
./vendor/bin/sail test --coverage --min=90

# Run specific feature
./vendor/bin/sail test --filter="Playback"

# Lint
./vendor/bin/sail composer lint

# Quality check (Pint + PHPStan + Tests)
./vendor/bin/sail composer quality
```

---

## Business Rules Reference

### View Counting
- **Full play threshold:** 80% progress
- **View counted once:** Only when `is_full_play` becomes true
- **Calculation:** `remaining = limit - count(is_full_play=true)`
- **Lock detection:** `is_locked` flag set when remaining = 0

### Device Policy
- **One active device** per student
- **Device registered** on first login
- **Change requires** admin approval
- **JWT tokens bound** to device_id
- **Reinstall detection** by device model fingerprint

### Session Lifecycle
- **Token TTL:** 240 seconds (4 minutes), clamped 180-300
- **Session TTL:** Configured via `config('playback.session_ttl')`
- **Session timeout:** 60 seconds of inactivity
- **Heartbeat interval:** 30 seconds (recommended)
- **Progress extends session:** Each update resets `expires_at` and `last_activity_at`
- **Concurrent blocking:** Only one active session per user
- **Auto-close reasons:** timeout, user, max_views

### Settings Hierarchy (later overrides earlier)
1. Center defaults (table columns)
2. CenterSetting (JSON)
3. CourseSetting (JSON)
4. VideoSetting (JSON)
5. StudentSetting (JSON)

---

## File Paths Reference

### Services
```
app/Services/
├── Playback/
│   ├── PlaybackService.php
│   ├── PlaybackAuthorizationService.php
│   └── ViewLimitService.php
├── Devices/
│   ├── DeviceService.php
│   └── DeviceChangeService.php
├── Settings/
│   └── SettingsResolverService.php
├── Centers/
│   └── CenterScopeService.php
└── Bunny/
    └── BunnyEmbedTokenService.php
```

### Models
```
app/Models/
├── User.php
├── Center.php
├── Course.php
├── Video.php
├── PlaybackSession.php
├── UserDevice.php
├── DeviceChangeRequest.php
├── ExtraViewRequest.php
└── Pivots/
    └── CourseVideo.php
```

### Controllers
```
app/Http/Controllers/
├── Mobile/
│   ├── PlaybackController.php
│   └── DeviceChangeRequestController.php
└── Admin/
    ├── DeviceChangeRequestController.php
    └── ExtraViewRequestController.php
```

---

## Common Commands

```bash
# Development
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed

# Testing
./vendor/bin/sail test
./vendor/bin/sail test --filter="Playback"

# Code Quality
./vendor/bin/sail pint --test
./vendor/bin/sail composer phpstan
./vendor/bin/sail composer quality

# API Documentation
./vendor/bin/sail artisan scribe:generate

# Cleanup
./vendor/bin/sail artisan playback:close-stale --timeout=60
```

---

## Integration Points

### Bunny Stream
```php
// Video URL Construction
https://iframe.mediadelivery.net/embed/{library_id}/{video_uuid}
  ?token={embed_token}
  &expires={embed_token_expires}

// Token Generation
$expiresAt = now()->addSeconds($ttl)->timestamp;
$token = hash('sha256', $secret . $videoUuid . $expiresAt);
```

### Bunny CDN (Storage)
```php
// Signed URL for PDFs
Storage::disk('bunny')->temporaryUrl($path, now()->addMinutes(5));
```

### OTP Provider (Abstract)
```php
// Provider configured in config/otp.php
// Implementation in app/Services/Otp/
interface OtpServiceInterface {
    public function send(string $phone): string;
    public function verify(string $phone, string $code): bool;
}
```

---

## Related Documentation

For deeper details, reference these docs in your project:

- `docs/CLAUDE_CONTEXT.md` - Quick reference
- `docs/AI_INSTRUCTIONS.md` - Master system rules
- `docs/architecture/DATABASE_SCHEMA.md` - Complete schema
- `docs/laravel12-best-practices.md` - Coding standards
- `docs/features/PLAYBACK.md` - Playback deep dive
- `docs/features/DEVICE_MANAGEMENT.md` - Device system
- `docs/features/VIEW_LIMITS.md` - View limit details
- `docs/features/SETTINGS.md` - Settings hierarchy
- `docs/codex/CODEX_DOMAIN_RULES.md` - Domain rules

---

## How to Use This Skill

**When starting a new feature:**
1. Review relevant business rules section
2. Check service layer patterns
3. Follow coding standards
4. Reference file paths for similar implementations

**When fixing bugs:**
1. Check business rules for expected behavior
2. Review service layer logic
3. Verify database constraints
4. Check error codes

**When refactoring:**
1. Follow architecture patterns
2. Maintain coding standards
3. Preserve business rules
4. Update tests

**When creating documentation:**
1. Follow existing doc structure
2. Include code examples
3. Reference related files
4. Update this skill if needed
