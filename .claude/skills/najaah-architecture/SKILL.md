# Najaah LMS - Architecture Agent

## Purpose
Specialized agent for database design, schema management, multi-tenancy architecture, and system design decisions for Najaah LMS.

## When to Use This Agent
- Designing new database tables and migrations
- Modifying existing schema
- Making multi-tenancy decisions
- Planning data relationships
- Optimizing database queries
- Creating indexes and constraints
- Designing caching strategies

## Prerequisites
Always read the master skill first: `/mnt/skills/user/najaah/SKILL.md`

---

## Core Responsibilities

### 1. Database Schema Design

**Every table MUST include:**
```php
$table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
$table->timestamps(); // created_at, updated_at
$table->softDeletes(); // deleted_at
```

**Foreign Key Pattern (STRICT):**
```php
$table->foreignId('center_id')
    ->constrained('centers')
    ->cascadeOnUpdate()
    ->cascadeOnDelete();

$table->foreignId('user_id')
    ->constrained('users')
    ->cascadeOnUpdate()
    ->cascadeOnDelete();
```

**Status Columns (ALWAYS integers):**
```php
// NEVER use ENUM or string statuses
$table->tinyInteger('status')->default(0);

// Define constants in model
const STATUS_ACTIVE = 0;
const STATUS_REVOKED = 1;
const STATUS_PENDING = 2;
```

### 2. Multi-Tenancy Rules

**Tenant Scoping:**
- Every entity table needs `center_id` foreign key
- Use `CenterScopeService` for automatic scoping
- Never expose cross-center data in queries
- Validate center ownership in authorization layer

**Two Center Types:**
```sql
-- Branded Centers
- Own subdomain (physics-academy.najaah.me)
- Isolated students (separate accounts per center)
- center.is_branded = true

-- Unbranded Centers  
- Under main domain (najaah.me/centers/math-tutoring)
- Shared students (one account across all unbranded)
- center.is_branded = false
```

**Center-Scoped Queries:**
```php
// WRONG: Missing center scope
Video::where('status', Video::STATUS_PUBLISHED)->get();

// CORRECT: Always scope by center
Video::where('center_id', $centerId)
    ->where('status', Video::STATUS_PUBLISHED)
    ->get();
```

### 3. Indexing Strategy

**Always Index:**
- All foreign keys
- Soft delete column (`deleted_at`)
- Status columns used in WHERE clauses
- Columns used in ORDER BY
- Unique constraints

**Composite Indexes for:**
```php
// User + Video lookups (playback sessions, view counts)
$table->index(['user_id', 'video_id']);

// Center + Status queries (admin lists)
$table->index(['center_id', 'status']);

// Cleanup jobs (stale session detection)
$table->index(['ended_at', 'last_activity_at']);

// Token expiration queries
$table->index('embed_token_expires_at');
$table->index('expires_at');
```

### 4. Relationship Patterns

**One-to-Many:**
```php
// Parent model
public function videos(): HasMany
{
    return $this->hasMany(Video::class);
}

// Child model
public function course(): BelongsTo
{
    return $this->belongsTo(Course::class);
}
```

**Many-to-Many (with pivot):**
```php
// Course model
public function videos(): BelongsToMany
{
    return $this->belongsToMany(Video::class, 'course_video')
        ->using(CourseVideo::class)
        ->withPivot('view_limit_override', 'visible', 'order_index')
        ->withTimestamps();
}

// Pivot model (app/Models/Pivots/CourseVideo.php)
class CourseVideo extends Pivot
{
    use SoftDeletes;
    
    protected $casts = [
        'view_limit_override' => 'integer',
        'visible' => 'boolean',
        'order_index' => 'integer',
    ];
}
```

**Polymorphic Relationships:**
```php
// Settings pattern
public function settable(): MorphTo
{
    return $this->morphTo();
}
```

### 5. JSON Column Patterns

**Settings Tables:**
```php
// Migration
$table->json('settings');

// Model cast
protected $casts = [
    'settings' => 'array',
];

// Usage
$centerSetting->settings = [
    'view_limit' => 5,
    'pdf_download_permission' => true,
];
```

**Translation Fields:**
```php
// Migration
$table->json('title_translations');
$table->json('description_translations');

// Model cast
protected $casts = [
    'title_translations' => 'array',
    'description_translations' => 'array',
];

// Usage
$course->title_translations = [
    'en' => 'Introduction to Physics',
    'ar' => 'مقدمة في الفيزياء',
];
```

---

## Table Design Checklist

When creating a new table, verify:

- [ ] `id` as BIGINT UNSIGNED primary key
- [ ] `timestamps()` for created_at, updated_at
- [ ] `softDeletes()` for deleted_at
- [ ] `center_id` foreign key (if entity is center-scoped)
- [ ] All foreign keys with `constrained()->cascadeOnUpdate()->cascadeOnDelete()`
- [ ] Status columns as integers with constants
- [ ] Indexes on all foreign keys
- [ ] Index on `deleted_at`
- [ ] Composite indexes for common queries
- [ ] Unique constraints where needed
- [ ] JSON columns with proper casts

---

## Common Table Patterns

### Entity Tables
```php
Schema::create('videos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('center_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->string('title');
    $table->json('title_translations');
    $table->text('description')->nullable();
    $table->json('description_translations')->nullable();
    $table->string('source_id')->unique(); // Bunny video ID
    $table->tinyInteger('encoding_status')->default(0);
    $table->tinyInteger('lifecycle_status')->default(0);
    $table->tinyInteger('upload_status')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['center_id', 'lifecycle_status']);
    $table->index('source_id');
    $table->index('deleted_at');
});
```

### Pivot Tables
```php
Schema::create('course_video', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('video_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('section_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->unsignedInteger('order_index')->default(0);
    $table->boolean('visible')->default(true);
    $table->unsignedInteger('view_limit_override')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->unique(['course_id', 'video_id', 'section_id']);
    $table->index(['course_id', 'visible']);
});
```

### Session/Tracking Tables
```php
Schema::create('playback_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('video_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('course_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('enrollment_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('device_id')->constrained('user_devices')->cascadeOnUpdate()->cascadeOnDelete();
    $table->text('embed_token')->nullable();
    $table->timestamp('embed_token_expires_at')->nullable();
    $table->timestamp('started_at');
    $table->timestamp('ended_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('last_activity_at')->nullable();
    $table->unsignedInteger('progress_percent')->default(0);
    $table->boolean('is_full_play')->default(false);
    $table->boolean('auto_closed')->default(false);
    $table->boolean('is_locked')->default(false);
    $table->unsignedInteger('watch_duration')->default(0);
    $table->string('close_reason', 20)->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['user_id', 'video_id']);
    $table->index(['course_id', 'user_id']);
    $table->index('embed_token_expires_at');
    $table->index('ended_at');
    $table->index('expires_at');
    $table->index(['ended_at', 'last_activity_at']); // Cleanup job
});
```

### Settings Tables
```php
Schema::create('center_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('center_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->json('settings');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('deleted_at');
});
```

### Request/Workflow Tables
```php
Schema::create('device_change_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->foreignId('center_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
    $table->string('current_device_id');
    $table->string('new_device_id');
    $table->string('new_model');
    $table->string('new_os_version');
    $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
    $table->text('reason')->nullable();
    $table->text('decision_reason')->nullable();
    $table->foreignId('decided_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
    $table->timestamp('decided_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['user_id', 'status']);
    $table->index(['center_id', 'status']);
});
```

---

## Query Optimization Patterns

### N+1 Prevention
```php
// WRONG: N+1 query problem
$courses = Course::where('center_id', $centerId)->get();
foreach ($courses as $course) {
    echo $course->instructor->name; // +1 query per course
}

// CORRECT: Eager load relationships
$courses = Course::where('center_id', $centerId)
    ->with('instructor')
    ->get();
```

### Chunking for Large Datasets
```php
// For batch operations on large tables
PlaybackSession::where('ended_at', null)
    ->where('last_activity_at', '<', now()->subMinutes(60))
    ->chunk(100, function ($sessions) {
        foreach ($sessions as $session) {
            $session->update(['ended_at' => now(), 'auto_closed' => true]);
        }
    });
```

### Subquery Optimization
```php
// Find users with pending device change requests
User::whereHas('deviceChangeRequests', function ($query) {
    $query->where('status', 'PENDING');
})->get();

// Count related records efficiently
Course::withCount('videos')
    ->where('center_id', $centerId)
    ->get();
```

---

## Caching Strategy

### Cache Keys Pattern
```php
// Format: entity:center:id:attribute
'video:55:123:view_limit'
'user:55:456:active_device'
'course:55:789:settings'

// Implementation
Cache::remember(
    "video:{$centerId}:{$videoId}:view_limit",
    now()->addMinutes(5),
    fn() => $this->calculateViewLimit($video)
);
```

### Cache Invalidation
```php
// Invalidate on model update
protected static function booted(): void
{
    static::updated(function (Video $video) {
        Cache::forget("video:{$video->center_id}:{$video->id}:view_limit");
    });
}
```

### Cache Tags (Redis only)
```php
Cache::tags(['videos', "center:{$centerId}"])
    ->remember("video_list:{$centerId}", now()->addHour(), fn() => ...);

// Flush all videos for a center
Cache::tags("center:{$centerId}")->flush();
```

---

## Migration Best Practices

### Naming Convention
```
YYYY_MM_DD_HHMMSS_action_table_name.php

Examples:
2025_01_20_120000_create_videos_table.php
2025_01_21_103000_add_device_id_to_playback_sessions_table.php
2025_01_22_084500_add_is_locked_to_playback_sessions_table.php
```

### Rollback Safety
```php
public function up(): void
{
    Schema::create('videos', function (Blueprint $table) {
        // Table definition
    });
}

public function down(): void
{
    Schema::dropIfExists('videos');
}
```

### Adding Columns to Existing Tables
```php
public function up(): void
{
    Schema::table('playback_sessions', function (Blueprint $table) {
        $table->boolean('is_locked')->default(false)->after('auto_closed');
        $table->index('is_locked');
    });
}

public function down(): void
{
    Schema::table('playback_sessions', function (Blueprint $table) {
        $table->dropIndex(['is_locked']);
        $table->dropColumn('is_locked');
    });
}
```

---

## Data Integrity Rules

### Cascade Rules
```php
// Parent deleted → children deleted
->cascadeOnDelete()

// Parent updated → children updated
->cascadeOnUpdate()

// Parent deleted → set NULL
->nullOnDelete()

// Parent deleted → restrict deletion
->restrictOnDelete()
```

### Soft Delete Cascade
```php
// When soft deleting parent, soft delete children
protected static function booted(): void
{
    static::deleting(function (Course $course) {
        if ($course->isForceDeleting()) {
            $course->videos()->forceDelete();
        } else {
            $course->videos()->delete();
        }
    });
}
```

---

## Common Queries Reference

### Find Active Sessions
```php
PlaybackSession::where('user_id', $userId)
    ->whereNull('ended_at')
    ->where('expires_at', '>', now())
    ->first();
```

### Count Full Plays for View Limit
```php
PlaybackSession::where('user_id', $userId)
    ->where('video_id', $videoId)
    ->where('is_full_play', true)
    ->count();
```

### Get Approved Extra Views
```php
ExtraViewRequest::where('user_id', $userId)
    ->where('video_id', $videoId)
    ->where('status', 'APPROVED')
    ->sum('granted_views');
```

### Find Active Device
```php
UserDevice::where('user_id', $userId)
    ->where('status', UserDevice::STATUS_ACTIVE)
    ->first();
```

### Close Stale Sessions
```php
PlaybackSession::whereNull('ended_at')
    ->where('last_activity_at', '<', now()->subSeconds($timeout))
    ->update([
        'ended_at' => now(),
        'auto_closed' => true,
        'close_reason' => 'timeout',
    ]);
```

---

## Schema Evolution Guidelines

### Adding New Features
1. Create migration with proper constraints
2. Update affected models with casts/relationships
3. Add indexes for new queries
4. Update factories with new fields
5. Update seeders if needed
6. Write tests for new relationships

### Modifying Existing Schema
1. Create new migration (never edit old ones)
2. Consider backward compatibility
3. Plan data migration for existing records
4. Update model casts and fillable
5. Update tests for new behavior

### Deprecating Fields
1. Mark as deprecated in model comments
2. Stop writing to field in new code
3. Wait for data migration/cleanup
4. Create migration to drop column
5. Update all references

---

## Architecture Decision Template

When making architectural decisions, document:

```markdown
## Decision: [Title]

### Context
[What problem are we solving?]

### Options Considered
1. Option A: [Description]
   - Pros: [...]
   - Cons: [...]

2. Option B: [Description]
   - Pros: [...]
   - Cons: [...]

### Decision
We chose [Option] because [reasoning].

### Consequences
- Positive: [...]
- Negative: [...]
- Tradeoffs: [...]

### Implementation Notes
[Technical details, migration path, etc.]
```

---

## Troubleshooting Common Issues

### Issue: N+1 Queries
**Solution:** Use eager loading with `with()` or lazy eager loading with `load()`

### Issue: Slow Queries
**Solution:** Add composite indexes, use `explain()` to analyze, consider caching

### Issue: Data Integrity Violations
**Solution:** Review foreign key constraints, add validation in service layer

### Issue: Multi-Tenancy Leaks
**Solution:** Use CenterScopeService, add tests for cross-center access

---

## Related Skills
- Master Skill: `/mnt/skills/user/najaah/SKILL.md`
- Feature Agent: `/mnt/skills/user/najaah-features/SKILL.md`
- Code Quality Agent: `/mnt/skills/user/najaah-quality/SKILL.md`