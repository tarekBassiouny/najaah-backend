# Najaah LMS - Code Quality Agent

## Purpose
Specialized agent for ensuring code quality, testing standards, linting, type safety, and maintainability for Najaah LMS.

## When to Use This Agent
- Writing tests (unit, feature, integration)
- Running code quality checks
- Enforcing coding standards
- Type checking with PHPStan
- Creating factories and seeders
- Reviewing code quality
- Setting up CI/CD quality gates

## Prerequisites
Always read the master skill first: `/mnt/skills/user/najaah/SKILL.md`

---

## Core Responsibilities

### 1. Testing Standards

**Test Structure with Pest:**
```php
<?php

declare(strict_types=1);

uses(RefreshDatabase::class);

describe('PlaybackService', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['is_student' => true]);
        $this->center = Center::factory()->create();
        $this->course = Course::factory()->create([
            'center_id' => $this->center->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $this->video = Video::factory()->create([
            'center_id' => $this->center->id,
            'encoding_status' => Video::ENCODING_READY,
            'lifecycle_status' => Video::LIFECYCLE_READY,
        ]);
        $this->course->videos()->attach($this->video->id);
    });

    it('starts playback session when authorized', function () {
        $service = app(PlaybackService::class);
        
        $result = $service->requestPlayback(
            $this->user,
            $this->center,
            $this->course,
            $this->video
        );

        expect($result)
            ->toBeArray()
            ->toHaveKeys(['library_id', 'video_uuid', 'embed_token', 'session_id'])
            ->and($result['session_id'])->toBeInt();

        $this->assertDatabaseHas('playback_sessions', [
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);
    });

    it('throws VIEW_LIMIT_EXCEEDED when no views remain', function () {
        // Create 2 full play sessions (default limit is 2)
        PlaybackSession::factory()
            ->count(2)
            ->create([
                'user_id' => $this->user->id,
                'video_id' => $this->video->id,
                'is_full_play' => true,
            ]);

        $service = app(PlaybackService::class);

        expect(fn() => $service->requestPlayback(
            $this->user,
            $this->center,
            $this->course,
            $this->video
        ))->toThrow(DomainException::class);
    });

    it('allows playback when extra views granted', function () {
        // Use up default views
        PlaybackSession::factory()
            ->count(2)
            ->create([
                'user_id' => $this->user->id,
                'video_id' => $this->video->id,
                'is_full_play' => true,
            ]);

        // Grant extra view
        ExtraViewRequest::factory()->create([
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
            'status' => 'APPROVED',
            'granted_views' => 1,
        ]);

        $service = app(PlaybackService::class);

        $result = $service->requestPlayback(
            $this->user,
            $this->center,
            $this->course,
            $this->video
        );

        expect($result)->toBeArray();
    });
});
```

### 2. Test Coverage Requirements

**Minimum Coverage:**
- Project-wide: 90%
- Critical paths: 100% (Auth, payments, view limits, session management)

**Coverage Commands:**
```bash
# Run tests with coverage report
./vendor/bin/sail test --coverage

# Enforce minimum coverage
./vendor/bin/sail test --coverage --min=90

# Generate HTML coverage report
./vendor/bin/sail test --coverage --coverage-html=coverage-report

# Coverage for specific path
./vendor/bin/sail test --coverage --path=app/Services/Playback
```

### 3. Unit Testing Patterns

**Service Unit Tests (with Mocks):**
```php
describe('ViewLimitService', function () {
    it('calculates remaining views correctly', function () {
        $user = User::factory()->make(['id' => 1]);
        $video = Video::factory()->make(['id' => 10]);
        $course = Course::factory()->make(['id' => 5]);
        $center = Center::factory()->make([
            'id' => 2,
            'default_view_limit' => 2,
        ]);
        $course->center = $center;

        // Mock PlaybackSession query
        $sessionMock = Mockery::mock('alias:' . PlaybackSession::class);
        $sessionMock->shouldReceive('where->where->where->count')
            ->andReturn(1); // 1 full play exists

        // Mock ExtraViewRequest query
        $requestMock = Mockery::mock('alias:' . ExtraViewRequest::class);
        $requestMock->shouldReceive('where->where->where->sum')
            ->andReturn(0); // No extra views

        $service = app(ViewLimitService::class);
        $remaining = $service->getRemainingViews($user, $video, $course);

        expect($remaining)->toBe(1); // 2 - 1 = 1 remaining
    });

    it('includes extra views in calculation', function () {
        // Test implementation with extra views granted
    });

    it('respects settings hierarchy', function () {
        // Test each level of hierarchy
    });
});
```

**Model Unit Tests:**
```php
describe('PlaybackSession model', function () {
    it('has correct fillable attributes', function () {
        $session = new PlaybackSession();
        
        expect($session->getFillable())->toContain(
            'user_id',
            'video_id',
            'device_id',
            'progress_percent'
        );
    });

    it('casts attributes correctly', function () {
        $session = PlaybackSession::factory()->create();
        
        expect($session->progress_percent)->toBeInt()
            ->and($session->is_full_play)->toBeBool()
            ->and($session->started_at)->toBeInstanceOf(Carbon::class);
    });

    it('has correct relationships', function () {
        $session = PlaybackSession::factory()->create();
        
        expect($session->user)->toBeInstanceOf(User::class)
            ->and($session->video)->toBeInstanceOf(Video::class)
            ->and($session->device)->toBeInstanceOf(UserDevice::class);
    });

    it('applies soft deletes', function () {
        $session = PlaybackSession::factory()->create();
        $session->delete();
        
        expect($session->trashed())->toBeTrue();
        $this->assertSoftDeleted('playback_sessions', ['id' => $session->id]);
    });
});
```

### 4. Feature Testing Patterns

**API Endpoint Tests:**
```php
describe('Playback API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['is_student' => true]);
        $this->device = UserDevice::factory()->create([
            'user_id' => $this->user->id,
            'status' => UserDevice::STATUS_ACTIVE,
        ]);
        
        // Authenticate
        $this->actingAs($this->user);
    });

    it('returns 200 on successful playback request', function () {
        $center = Center::factory()->create();
        $course = Course::factory()->create([
            'center_id' => $center->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $video = Video::factory()->create(['center_id' => $center->id]);
        $course->videos()->attach($video->id);

        $response = $this->postJson(
            "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'library_id',
                    'video_uuid',
                    'embed_token',
                    'embed_token_expires',
                    'session_id',
                    'expires_in',
                ],
            ])
            ->assertJson(['success' => true]);
    });

    it('returns 403 when view limit exceeded', function () {
        // Setup: Use up all views
        
        $response = $this->postJson(
            "/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback"
        );

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VIEW_LIMIT_EXCEEDED',
                ],
            ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/playback/progress', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_id', 'percentage']);
    });
});
```

**Database State Tests:**
```php
it('creates device on first login', function () {
    $user = User::factory()->create();
    
    $service = app(DeviceService::class);
    $device = $service->register($user, 'device-uuid-123', 'iPhone 14', 'iOS 17');

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'device_id' => 'device-uuid-123',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);
});

it('revokes old device on approval', function () {
    $user = User::factory()->create();
    $oldDevice = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'old-device',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);
    
    $request = DeviceChangeRequest::factory()->create([
        'user_id' => $user->id,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'status' => 'PENDING',
    ]);

    $admin = User::factory()->create();
    $service = app(DeviceChangeService::class);
    $service->approve($admin, $request);

    $this->assertDatabaseHas('user_devices', [
        'device_id' => 'old-device',
        'status' => UserDevice::STATUS_REVOKED,
    ]);

    $this->assertDatabaseHas('user_devices', [
        'device_id' => 'new-device',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);
});
```

### 5. Integration Testing

**Full Workflow Tests:**
```php
describe('Device change workflow', function () {
    it('completes full device change flow', function () {
        // 1. Student has active device
        $student = User::factory()->create(['is_student' => true]);
        $oldDevice = UserDevice::factory()->create([
            'user_id' => $student->id,
            'status' => UserDevice::STATUS_ACTIVE,
        ]);

        // 2. Student submits change request
        $this->actingAs($student);
        $response = $this->postJson('/api/v1/settings/device-change', [
            'reason' => 'Lost my old phone',
        ]);
        $response->assertStatus(200);
        $requestId = $response->json('data.id');

        // 3. Admin approves request
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $response = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/approve");
        $response->assertStatus(200);

        // 4. Verify state changes
        $this->assertDatabaseHas('device_change_requests', [
            'id' => $requestId,
            'status' => 'APPROVED',
        ]);

        $this->assertDatabaseHas('user_devices', [
            'device_id' => $oldDevice->device_id,
            'status' => UserDevice::STATUS_REVOKED,
        ]);

        // 5. Student can now login with new device
        // Test new device login succeeds
    });
});
```

### 6. Factory Standards

**Proper Factory Structure:**
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'is_student' => false,
            'is_admin' => false,
            'is_super_admin' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function student(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_student' => true,
        ]);
    }

    public function admin(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function unverified(): self
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

**Factory Rules:**
- NEVER use `fake()->optional()` - always generate complete data
- NEVER produce duplicate unique fields (email, phone, slugs)
- Always generate valid JSON for translation fields
- Use factories for relationships, not direct IDs
- Provide state methods for common variations

### 7. PHPStan Configuration

**Level 8 Type Safety:**
```php
// phpstan.neon
parameters:
    level: 8
    paths:
        - app
        - tests
    excludePaths:
        - app/Console/Kernel.php
    ignoreErrors:
        # Ignore specific Laravel magic
        - '#Method.*::factory\(\) has no return type specified#'
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

**Common PHPStan Fixes:**
```php
// WRONG: Missing type hint
public function getData()
{
    return ['key' => 'value'];
}

// CORRECT: Full type specification
/**
 * @return array{key: string}
 */
public function getData(): array
{
    return ['key' => 'value'];
}

// WRONG: Unknown property
$user->is_student;

// CORRECT: Add @property annotation to model
/**
 * @property bool $is_student
 */
class User extends Model {}
```

### 8. Laravel Pint Configuration

**PSR-12 Compliance:**
```json
{
    "preset": "psr12",
    "rules": {
        "declare_strict_types": true,
        "no_unused_imports": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "blank_line_after_opening_tag": true,
        "method_chaining_indentation": true,
        "multiline_whitespace_before_semicolons": {
            "strategy": "no_multi_line"
        }
    }
}
```

**Pint Commands:**
```bash
# Check for issues
./vendor/bin/sail pint --test

# Fix issues automatically
./vendor/bin/sail pint

# Fix specific directory
./vendor/bin/sail pint app/Services

# Dry run (show what would change)
./vendor/bin/sail pint --test --dirty
```

### 9. Quality Check Commands

**Run All Quality Checks:**
```bash
# composer.json scripts section
"scripts": {
    "lint": [
        "@pint-test",
        "@phpstan"
    ],
    "pint-test": "./vendor/bin/pint --test",
    "phpstan": "./vendor/bin/phpstan analyse",
    "test": "./vendor/bin/pest",
    "test-coverage": "./vendor/bin/pest --coverage --min=90",
    "quality": [
        "@lint",
        "@test"
    ]
}

# Run commands
./vendor/bin/sail composer lint
./vendor/bin/sail composer quality
./vendor/bin/sail composer test-coverage
```

### 10. CI/CD Quality Gates

**GitHub Actions Example:**
```yaml
name: Quality Checks

on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          
      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist
        
      - name: Run Pint
        run: ./vendor/bin/pint --test
        
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse
        
      - name: Run Tests
        run: ./vendor/bin/pest
        
      - name: Check Coverage
        run: ./vendor/bin/pest --coverage --min=90
```

---

## Testing Best Practices

### 1. Test Naming Convention
```php
// Format: it('should [expected behavior] when [condition]')
it('throws DEVICE_MISMATCH when different device attempts login', function () {
    // Test
});

it('calculates remaining views correctly with extra views granted', function () {
    // Test
});

it('closes stale sessions after timeout period', function () {
    // Test
});
```

### 2. Arrange-Act-Assert Pattern
```php
it('creates playback session', function () {
    // Arrange
    $user = User::factory()->create();
    $video = Video::factory()->create();
    
    // Act
    $session = PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
    ]);
    
    // Assert
    expect($session->user_id)->toBe($user->id);
    $this->assertDatabaseHas('playback_sessions', ['id' => $session->id]);
});
```

### 3. Test Data Builders
```php
// Helper function for complex setup
function createPlaybackScenario(array $overrides = []): array
{
    $center = Center::factory()->create();
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => Course::STATUS_PUBLISHED,
    ]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id);
    $user = User::factory()->student()->create(['center_id' => $center->id]);
    $device = UserDevice::factory()->create(['user_id' => $user->id]);

    return array_merge([
        'center' => $center,
        'course' => $course,
        'video' => $video,
        'user' => $user,
        'device' => $device,
    ], $overrides);
}

// Usage
it('starts playback', function () {
    $scenario = createPlaybackScenario();
    
    $service = app(PlaybackService::class);
    $result = $service->requestPlayback(
        $scenario['user'],
        $scenario['center'],
        $scenario['course'],
        $scenario['video']
    );
    
    expect($result)->toBeArray();
});
```

### 4. Database Assertion Helpers
```php
// Assert record exists with specific attributes
$this->assertDatabaseHas('playback_sessions', [
    'user_id' => $user->id,
    'is_full_play' => true,
]);

// Assert record doesn't exist
$this->assertDatabaseMissing('playback_sessions', [
    'user_id' => $user->id,
    'ended_at' => null,
]);

// Assert soft deleted
$this->assertSoftDeleted('playback_sessions', [
    'id' => $session->id,
]);

// Assert count
$this->assertDatabaseCount('playback_sessions', 5);
```

### 5. Event and Queue Testing
```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('dispatches VideoFullyWatched event', function () {
    Event::fake();
    
    $service = app(PlaybackService::class);
    $service->updateProgress($session, 85); // >= 80%
    
    Event::assertDispatched(VideoFullyWatched::class, function ($event) use ($session) {
        return $event->session->id === $session->id;
    });
});

it('queues notification job', function () {
    Queue::fake();
    
    // Trigger action that queues job
    
    Queue::assertPushed(SendNotification::class, function ($job) {
        return $job->user->id === $user->id;
    });
});
```

---

## Code Review Checklist

When reviewing code, verify:

**Type Safety:**
- [ ] `declare(strict_types=1);` on all PHP files
- [ ] All properties typed
- [ ] All method parameters typed
- [ ] All return types specified
- [ ] PHPDoc for complex types
- [ ] No mixed types unless necessary

**Testing:**
- [ ] Unit tests for all services
- [ ] Feature tests for all endpoints
- [ ] Integration tests for workflows
- [ ] Edge cases covered
- [ ] Error cases tested
- [ ] 90%+ coverage

**Code Quality:**
- [ ] Passes Pint (PSR-12)
- [ ] Passes PHPStan level 8
- [ ] No unused imports
- [ ] No dead code
- [ ] Proper naming conventions

**Laravel Conventions:**
- [ ] Models have factories
- [ ] Migrations follow standards
- [ ] Services use constructor injection
- [ ] Controllers are thin
- [ ] Resources for API responses

**Documentation:**
- [ ] Complex logic has comments
- [ ] Public methods have docblocks
- [ ] README updated if needed
- [ ] CHANGELOG updated

---

## Quality Metrics

Track these metrics in your project:

```
Code Coverage:       90%+ (target: 95%)
PHPStan Level:       8/8
Pint Issues:         0
Test Count:          500+
Average Test Time:   < 5 seconds
Build Time:          < 2 minutes
```

---

## Common Testing Pitfalls

### Avoid: Testing Implementation Details
```php
// BAD: Testing private methods or internal state
it('calls internal method', function () {
    $service = app(MyService::class);
    // Trying to test private calculateSomething()
});

// GOOD: Test public behavior
it('returns correct calculation result', function () {
    $service = app(MyService::class);
    $result = $service->process($input);
    expect($result)->toBe($expected);
});
```

### Avoid: Brittle Tests
```php
// BAD: Coupling to database auto-increment IDs
expect($result['id'])->toBe(1);

// GOOD: Test relationships and properties
expect($result)->toHaveKey('id')
    ->and($result['id'])->toBeInt();
```

### Avoid: Unclear Test Failures
```php
// BAD: No context on failure
expect($result)->toBeTrue();

// GOOD: Descriptive expectations
expect($result)
    ->toBeTrue('User should be able to view video with active enrollment');
```

---

## Related Skills
- Master Skill: `/mnt/skills/user/najaah/SKILL.md`
- Architecture Agent: `/mnt/skills/user/najaah-architecture/SKILL.md`
- Feature Agent: `/mnt/skills/user/najaah-features/SKILL.md`