# Najaah LMS - Feature Agent

## Purpose
Specialized agent for implementing business logic, domain rules, workflows, and feature specifications for Najaah LMS.

## When to Use This Agent
- Implementing new features
- Writing business logic in services
- Creating workflow processes (requests, approvals)
- Enforcing domain rules
- Building authorization logic
- Implementing state machines

## Prerequisites
Always read the master skill first: `/mnt/skills/user/najaah/SKILL.md`

---

## Core Responsibilities

### 1. Service Layer Implementation

**Service Pattern (STRICT):**
```php
<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\User;
use App\Models\Video;
use DomainException;

final readonly class PlaybackService
{
    public function __construct(
        private PlaybackAuthorizationService $authService,
        private ViewLimitService $viewLimitService,
        private BunnyEmbedTokenService $tokenService,
    ) {}

    /**
     * @return array{
     *     library_id: string,
     *     video_uuid: string,
     *     embed_token: string,
     *     embed_token_expires: int,
     *     session_id: string,
     *     expires_in: int
     * }
     */
    public function requestPlayback(
        User $user,
        Center $center,
        Course $course,
        Video $video
    ): array {
        // 1. Authorization checks
        $this->authService->assertCanStartPlayback($user, $center, $course, $video);
        
        // 2. Business logic
        // Implementation...
        
        return [
            'library_id' => $video->library_id,
            'video_uuid' => $video->source_id,
            // ...
        ];
    }

    /**
     * Deny helper for clean exception throwing
     */
    private function deny(string $code, string $message): never
    {
        throw new DomainException(
            json_encode(['code' => $code, 'message' => $message])
        );
    }
}
```

**Service Rules:**
- Constructor injection ONLY (no service locator pattern)
- Readonly class and properties
- Strict type hints everywhere
- Return typed arrays with PHPDoc shapes
- Use `deny()` helper for domain exceptions
- No direct controller/request dependencies

### 2. Authorization Pattern

**Authorization Service:**
```php
final readonly class PlaybackAuthorizationService
{
    /**
     * Assert all prerequisites for starting playback
     */
    public function assertCanStartPlayback(
        User $user,
        Center $center,
        Course $course,
        Video $video
    ): void {
        // User is student
        if (!$user->is_student) {
            $this->deny('UNAUTHORIZED', 'Only students can start playback.');
        }

        // Center access
        if ($user->center_id !== $center->id) {
            $this->deny('CENTER_MISMATCH', 'Student does not belong to this center.');
        }

        // Course ownership
        if ($course->center_id !== $center->id) {
            $this->deny('CENTER_MISMATCH', 'Course does not belong to this center.');
        }

        // Course published
        if ($course->status !== Course::STATUS_PUBLISHED) {
            $this->deny('NOT_FOUND', 'Course is not available.');
        }

        // Video attached to course
        if (!$course->videos()->where('videos.id', $video->id)->exists()) {
            $this->deny('NOT_FOUND', 'Video not found in this course.');
        }

        // Video ready for playback
        if ($video->encoding_status !== Video::ENCODING_READY) {
            $this->deny('VIDEO_NOT_READY', 'Video is still encoding.');
        }

        if ($video->lifecycle_status !== Video::LIFECYCLE_READY) {
            $this->deny('VIDEO_NOT_READY', 'Video is not ready for playback.');
        }

        // Active enrollment
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->first();

        if (!$enrollment) {
            $this->deny('ENROLLMENT_REQUIRED', 'Active enrollment required.');
        }

        // View limit check
        if (!$this->viewLimitService->hasViewsRemaining($user, $video, $course)) {
            $this->deny('VIEW_LIMIT_EXCEEDED', 'No remaining views for this video.');
        }
    }

    private function deny(string $code, string $message): never
    {
        throw new DomainException(
            json_encode(['code' => $code, 'message' => $message])
        );
    }
}
```

### 3. Business Rules Enforcement

**View Limit Calculation:**
```php
final readonly class ViewLimitService
{
    public function getRemainingViews(
        User $user,
        Video $video,
        Course $course
    ): int {
        // Get effective limit from hierarchy
        $limit = $this->resolveViewLimit($user, $video, $course);
        
        // Count full plays
        $fullPlays = PlaybackSession::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('is_full_play', true)
            ->count();
        
        // Get approved extra views
        $extraViews = ExtraViewRequest::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('status', ExtraViewRequest::STATUS_APPROVED)
            ->sum('granted_views') ?? 0;
        
        // Calculate remaining
        $totalLimit = $limit + $extraViews;
        return max(0, $totalLimit - $fullPlays);
    }

    public function hasViewsRemaining(
        User $user,
        Video $video,
        Course $course
    ): bool {
        return $this->getRemainingViews($user, $video, $course) > 0;
    }

    /**
     * Resolve view limit from settings hierarchy
     * Priority: Student > Video > Course > CourseVideo > Center
     */
    private function resolveViewLimit(
        User $user,
        Video $video,
        Course $course
    ): int {
        // Student setting
        $studentSetting = $user->settings?->settings['view_limit'] ?? null;
        if ($studentSetting !== null) {
            return (int) $studentSetting;
        }

        // Video setting
        $videoSetting = $video->settings?->settings['view_limit'] ?? null;
        if ($videoSetting !== null) {
            return (int) $videoSetting;
        }

        // Course-Video pivot override
        $pivotOverride = $course->videos()
            ->where('videos.id', $video->id)
            ->first()
            ?->pivot
            ?->view_limit_override;
        if ($pivotOverride !== null) {
            return $pivotOverride;
        }

        // Course setting
        $courseSetting = $course->settings?->settings['view_limit'] ?? null;
        if ($courseSetting !== null) {
            return (int) $courseSetting;
        }

        // Center setting
        $centerSetting = $course->center->settings?->settings['view_limit'] ?? null;
        if ($centerSetting !== null) {
            return (int) $centerSetting;
        }

        // Center default column
        return $course->center->default_view_limit;
    }
}
```

**Full Play Detection:**
```php
public function updateProgress(
    User $user,
    PlaybackSession $session,
    int $percentage
): array {
    // Validate session ownership
    if ($session->user_id !== $user->id) {
        return []; // Silently ignore
    }

    // Session already ended
    if ($session->ended_at !== null) {
        return [];
    }

    // Session expired
    if ($session->expires_at < now()) {
        return [];
    }

    // Update last activity and extend session
    $session->last_activity_at = now();
    $session->expires_at = now()->addSeconds(config('playback.session_ttl'));

    // Update progress if increased
    if ($percentage > $session->progress_percent) {
        $session->progress_percent = $percentage;

        // Detect full play (80% threshold)
        if ($percentage >= 80 && !$session->is_full_play) {
            $session->is_full_play = true;
            // View is now counted!
        }
    }

    $session->save();

    // Check if video is now locked
    $isLocked = !$this->viewLimitService->hasViewsRemaining(
        $user,
        $session->video,
        $session->course
    );

    // Update is_locked flag
    $session->is_locked = $isLocked;
    $session->save();

    return [
        'progress' => $session->progress_percent,
        'is_full_play' => $session->is_full_play,
        'is_locked' => $isLocked,
        'remaining_views' => $this->viewLimitService->getRemainingViews(
            $user,
            $session->video,
            $session->course
        ),
    ];
}
```

### 4. Workflow Implementation

**Request-Approval Pattern:**
```php
// Device Change Request workflow
final readonly class DeviceChangeService
{
    /**
     * Student creates request
     */
    public function create(
        User $student,
        string $newDeviceId,
        string $model,
        string $osVersion,
        ?string $reason
    ): DeviceChangeRequest {
        // Validate student role
        if (!$student->is_student) {
            $this->deny('UNAUTHORIZED', 'Only students can request device changes.');
        }

        // Validate has active device
        $currentDevice = $student->devices()
            ->where('status', UserDevice::STATUS_ACTIVE)
            ->first();

        if (!$currentDevice) {
            $this->deny('NO_ACTIVE_DEVICE', 'No active device found.');
        }

        // Check for pending request
        $pending = DeviceChangeRequest::where('user_id', $student->id)
            ->where('status', DeviceChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pending) {
            $this->deny('PENDING_REQUEST_EXISTS', 'Already have pending request.');
        }

        // Create request
        return DeviceChangeRequest::create([
            'user_id' => $student->id,
            'center_id' => $student->center_id,
            'current_device_id' => $currentDevice->device_id,
            'new_device_id' => $newDeviceId,
            'new_model' => $model,
            'new_os_version' => $osVersion,
            'status' => DeviceChangeRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);
    }

    /**
     * Admin approves request
     */
    public function approve(
        User $admin,
        DeviceChangeRequest $request,
        ?string $reason = null
    ): DeviceChangeRequest {
        // Validate admin scope (same center)
        if ($admin->center_id !== $request->center_id) {
            $this->deny('UNAUTHORIZED', 'Cannot approve request from different center.');
        }

        // Validate request is pending
        if ($request->status !== DeviceChangeRequest::STATUS_PENDING) {
            $this->deny('INVALID_STATE', 'Request is not pending.');
        }

        DB::transaction(function () use ($request, $admin, $reason) {
            $student = $request->user;

            // Revoke current device
            UserDevice::where('user_id', $student->id)
                ->where('device_id', $request->current_device_id)
                ->update(['status' => UserDevice::STATUS_REVOKED]);

            // Create or update new device
            UserDevice::updateOrCreate(
                [
                    'user_id' => $student->id,
                    'device_id' => $request->new_device_id,
                ],
                [
                    'model' => $request->new_model,
                    'os_version' => $request->new_os_version,
                    'status' => UserDevice::STATUS_ACTIVE,
                    'approved_at' => now(),
                    'last_used_at' => now(),
                ]
            );

            // Revoke all other devices
            UserDevice::where('user_id', $student->id)
                ->where('device_id', '!=', $request->new_device_id)
                ->update(['status' => UserDevice::STATUS_REVOKED]);

            // Update request
            $request->update([
                'status' => DeviceChangeRequest::STATUS_APPROVED,
                'decision_reason' => $reason,
                'decided_by' => $admin->id,
                'decided_at' => now(),
            ]);

            // Create audit log
            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'device_change_approved',
                'entity_type' => DeviceChangeRequest::class,
                'entity_id' => $request->id,
                'metadata' => [
                    'student_id' => $student->id,
                    'old_device' => $request->current_device_id,
                    'new_device' => $request->new_device_id,
                ],
            ]);
        });

        return $request->fresh();
    }

    /**
     * Admin rejects request
     */
    public function reject(
        User $admin,
        DeviceChangeRequest $request,
        ?string $reason = null
    ): DeviceChangeRequest {
        // Validate admin scope
        if ($admin->center_id !== $request->center_id) {
            $this->deny('UNAUTHORIZED', 'Cannot reject request from different center.');
        }

        // Validate request is pending
        if ($request->status !== DeviceChangeRequest::STATUS_PENDING) {
            $this->deny('INVALID_STATE', 'Request is not pending.');
        }

        $request->update([
            'status' => DeviceChangeRequest::STATUS_REJECTED,
            'decision_reason' => $reason,
            'decided_by' => $admin->id,
            'decided_at' => now(),
        ]);

        return $request;
    }

    private function deny(string $code, string $message): never
    {
        throw new DomainException(
            json_encode(['code' => $code, 'message' => $message])
        );
    }
}
```

### 5. State Machine Pattern

**Session Lifecycle:**
```php
final readonly class PlaybackSessionStateMachine
{
    public function start(User $user, Video $video, Device $device): PlaybackSession
    {
        return PlaybackSession::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'device_id' => $device->id,
            'started_at' => now(),
            'expires_at' => now()->addSeconds(config('playback.session_ttl')),
            'progress_percent' => 0,
            'is_full_play' => false,
            'auto_closed' => false,
            'is_locked' => false,
            'watch_duration' => 0,
        ]);
    }

    public function updateProgress(
        PlaybackSession $session,
        int $percentage
    ): void {
        $wasNotFullPlay = !$session->is_full_play;
        
        $session->update([
            'progress_percent' => max($session->progress_percent, $percentage),
            'is_full_play' => $percentage >= 80 ? true : $session->is_full_play,
            'last_activity_at' => now(),
            'expires_at' => now()->addSeconds(config('playback.session_ttl')),
        ]);

        // Transition to full_play state
        if ($wasNotFullPlay && $session->is_full_play) {
            event(new VideoFullyWatched($session));
        }
    }

    public function close(
        PlaybackSession $session,
        int $watchDuration,
        string $reason
    ): void {
        // Already ended, ignore
        if ($session->ended_at !== null) {
            return;
        }

        $session->update([
            'ended_at' => now(),
            'watch_duration' => $watchDuration,
            'close_reason' => $reason,
            'auto_closed' => in_array($reason, ['timeout', 'max_views']),
        ]);

        event(new SessionClosed($session));
    }

    public function expire(PlaybackSession $session): void
    {
        $this->close($session, $session->watch_duration, 'timeout');
    }
}
```

### 6. Settings Hierarchy Resolution

**Settings Resolver:**
```php
final readonly class SettingsResolverService
{
    /**
     * Resolve setting from hierarchy
     * Priority: Student > Video > Course > Center > System
     */
    public function resolve(
        string $key,
        ?User $user = null,
        ?Video $video = null,
        ?Course $course = null,
        ?Center $center = null
    ): mixed {
        // Student level
        if ($user && $user->settings) {
            $value = data_get($user->settings->settings, $key);
            if ($value !== null) {
                return $value;
            }
        }

        // Video level
        if ($video && $video->settings) {
            $value = data_get($video->settings->settings, $key);
            if ($value !== null) {
                return $value;
            }
        }

        // Course level
        if ($course && $course->settings) {
            $value = data_get($course->settings->settings, $key);
            if ($value !== null) {
                return $value;
            }
        }

        // Center level (settings table)
        if ($center && $center->settings) {
            $value = data_get($center->settings->settings, $key);
            if ($value !== null) {
                return $value;
            }
        }

        // Center level (direct column)
        if ($center) {
            // Map setting keys to column names
            $columnMap = [
                'view_limit' => 'default_view_limit',
                'pdf_download' => 'pdf_download_permission',
                'extra_view_requests' => 'allow_extra_view_requests',
                'device_limit' => 'device_limit',
            ];

            if (isset($columnMap[$key])) {
                return $center->{$columnMap[$key]};
            }
        }

        // System default
        return $this->getSystemDefault($key);
    }

    private function getSystemDefault(string $key): mixed
    {
        return match ($key) {
            'view_limit' => 2,
            'pdf_download' => true,
            'extra_view_requests' => true,
            'device_limit' => 1,
            default => null,
        };
    }
}
```

---

## Domain Rules Enforcement

### Rule: One Device Per Student
```php
public function register(User $user, string $deviceId, string $model, string $os): UserDevice
{
    // Check for existing active device
    $activeDevice = UserDevice::where('user_id', $user->id)
        ->where('status', UserDevice::STATUS_ACTIVE)
        ->first();

    // Different device exists
    if ($activeDevice && $activeDevice->device_id !== $deviceId) {
        $this->deny('DEVICE_MISMATCH', 'Different device is already active.');
    }

    // Same device, update last used
    if ($activeDevice && $activeDevice->device_id === $deviceId) {
        $activeDevice->update(['last_used_at' => now()]);
        return $activeDevice;
    }

    // First device, create and activate
    $device = UserDevice::create([
        'user_id' => $user->id,
        'device_id' => $deviceId,
        'model' => $model,
        'os_version' => $os,
        'status' => UserDevice::STATUS_ACTIVE,
        'approved_at' => now(),
        'last_used_at' => now(),
    ]);

    // Revoke all other devices (safety)
    UserDevice::where('user_id', $user->id)
        ->where('id', '!=', $device->id)
        ->update(['status' => UserDevice::STATUS_REVOKED]);

    return $device;
}
```

### Rule: No Concurrent Playback
```php
public function assertNoConcurrentSession(User $user, Device $currentDevice): void
{
    $activeSession = PlaybackSession::where('user_id', $user->id)
        ->whereNull('ended_at')
        ->where('expires_at', '>', now())
        ->first();

    if ($activeSession) {
        // Same device: close old session, allow new one
        if ($activeSession->device_id === $currentDevice->id) {
            $this->closeSession(
                $activeSession->id,
                $activeSession->watch_duration,
                'user'
            );
            return;
        }

        // Different device: block
        $this->deny(
            'CONCURRENT_DEVICE',
            'Playback is active on another device.'
        );
    }
}
```

### Rule: View Limit Enforcement
```php
public function assertViewsRemaining(User $user, Video $video, Course $course): void
{
    $remaining = $this->viewLimitService->getRemainingViews($user, $video, $course);

    if ($remaining <= 0) {
        $this->deny(
            'VIEW_LIMIT_EXCEEDED',
            'No remaining views. Request extra views from your instructor.'
        );
    }
}
```

### Rule: Enrollment Required
```php
public function assertActiveEnrollment(User $user, Course $course): void
{
    $enrollment = $user->enrollments()
        ->where('course_id', $course->id)
        ->where('status', Enrollment::STATUS_ACTIVE)
        ->first();

    if (!$enrollment) {
        $this->deny(
            'ENROLLMENT_REQUIRED',
            'You must be enrolled in this course.'
        );
    }
}
```

---

## Event-Driven Patterns

### Domain Events
```php
namespace App\Events;

class VideoFullyWatched
{
    public function __construct(
        public readonly PlaybackSession $session
    ) {}
}

class SessionClosed
{
    public function __construct(
        public readonly PlaybackSession $session
    ) {}
}

class DeviceChanged
{
    public function __construct(
        public readonly User $user,
        public readonly UserDevice $oldDevice,
        public readonly UserDevice $newDevice
    ) {}
}
```

### Event Listeners
```php
namespace App\Listeners;

class UpdateViewCount
{
    public function handle(VideoFullyWatched $event): void
    {
        // Invalidate view count cache
        Cache::forget("views:{$event->session->user_id}:{$event->session->video_id}");

        // Notify instructors if view limit reached
        $remaining = app(ViewLimitService::class)->getRemainingViews(
            $event->session->user,
            $event->session->video,
            $event->session->course
        );

        if ($remaining === 0) {
            event(new ViewLimitReached($event->session->user, $event->session->video));
        }
    }
}
```

---

## Transaction Management

### Use Transactions for Multi-Step Operations
```php
use Illuminate\Support\Facades\DB;

public function approve(User $admin, DeviceChangeRequest $request): DeviceChangeRequest
{
    return DB::transaction(function () use ($admin, $request) {
        // Step 1: Revoke old device
        UserDevice::where('device_id', $request->current_device_id)
            ->update(['status' => UserDevice::STATUS_REVOKED]);

        // Step 2: Activate new device
        UserDevice::updateOrCreate(
            ['user_id' => $request->user_id, 'device_id' => $request->new_device_id],
            ['status' => UserDevice::STATUS_ACTIVE]
        );

        // Step 3: Update request
        $request->update(['status' => 'APPROVED']);

        // Step 4: Create audit log
        AuditLog::create([...]);

        return $request->fresh();
    });
}
```

---

## Validation Layer

### Custom Validation Rules
```php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidDeviceId implements Rule
{
    public function passes($attribute, $value): bool
    {
        // UUID v4 format
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    public function message(): string
    {
        return 'The :attribute must be a valid UUID v4.';
    }
}
```

### Usage in FormRequests
```php
public function rules(): array
{
    return [
        'device_id' => ['required', 'string', new ValidDeviceId()],
        'reason' => ['nullable', 'string', 'max:500'],
    ];
}
```

---

## Feature Implementation Checklist

When implementing a new feature:

- [ ] Define domain rules and business logic
- [ ] Create service class with interface
- [ ] Implement authorization service
- [ ] Write service methods with strict types
- [ ] Add validation rules
- [ ] Create FormRequest classes
- [ ] Implement controller (thin)
- [ ] Create API resource for responses
- [ ] Add domain events if needed
- [ ] Write unit tests for services
- [ ] Write feature tests for endpoints
- [ ] Document in `/docs/features/`
- [ ] Update this skill if needed

---

## Common Patterns Reference

### Deny Helper
```php
private function deny(string $code, string $message): never
{
    throw new DomainException(
        json_encode(['code' => $code, 'message' => $message])
    );
}
```

### Service Return Types
```php
/**
 * @return array{id: int, status: string, created_at: string}
 */
public function create(...): array
{
    return [
        'id' => $entity->id,
        'status' => $entity->status,
        'created_at' => $entity->created_at->toIso8601String(),
    ];
}
```

### Authorization Checks
```php
// In service method
$this->authService->assertCanPerformAction($user, $resource);

// In authorization service
public function assertCanPerformAction(User $user, Resource $resource): void
{
    if (!$user->can('perform', $resource)) {
        $this->deny('UNAUTHORIZED', 'Not authorized.');
    }
}
```

---

## Related Skills
- Master Skill: `/mnt/skills/user/najaah/SKILL.md`
- Architecture Agent: `/mnt/skills/user/najaah-architecture/SKILL.md`
- Code Quality Agent: `/mnt/skills/user/najaah-quality/SKILL.md`