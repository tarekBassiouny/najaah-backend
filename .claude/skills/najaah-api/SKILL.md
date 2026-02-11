# Najaah LMS - API Agent

## Purpose
Specialized agent for API design, endpoint implementation, resource formatting, and API documentation for Najaah LMS.

## When to Use This Agent
- Designing new API endpoints
- Creating API resources
- Writing FormRequest validation
- Implementing controllers
- Generating API documentation (Scribe)
- Versioning APIs
- Handling API errors

## Prerequisites
Always read the master skill first: `/mnt/skills/user/najaah/SKILL.md`

---

## Core Responsibilities

### 1. API Endpoint Design

**RESTful Conventions:**
```
POST   /api/v1/resource           - Create
GET    /api/v1/resource           - List (paginated)
GET    /api/v1/resource/{id}      - Show single
PUT    /api/v1/resource/{id}      - Update (full)
PATCH  /api/v1/resource/{id}      - Update (partial)
DELETE /api/v1/resource/{id}      - Delete

// Nested resources
GET    /api/v1/courses/{course}/videos         - List videos in course
POST   /api/v1/courses/{course}/videos         - Attach video to course

// Actions (non-CRUD)
POST   /api/v1/videos/{video}/request_playback - Start playback
POST   /api/v1/sessions/{session}/close        - Close session
POST   /api/v1/requests/{request}/approve      - Approve request
```

**URL Structure Rules:**
- Always under `/api/v1/`
- Use kebab-case for multi-word resources
- Use singular for resource names in routes
- Use plural for collection names
- Nest resources when there's clear ownership
- Actions use verb in path (request_playback, close_session)

### 2. Controller Implementation

**Thin Controller Pattern:**
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RequestPlaybackRequest;
use App\Http\Resources\PlaybackSessionResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Video;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;

final class PlaybackController extends Controller
{
    public function __construct(
        private readonly PlaybackService $playbackService
    ) {}

    /**
     * Request video playback session
     *
     * @group Playback
     */
    public function requestPlayback(
        RequestPlaybackRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        try {
            $result = $this->playbackService->requestPlayback(
                $request->user(),
                $center,
                $course,
                $video
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\DomainException $e) {
            $error = json_decode($e->getMessage(), true);
            
            return response()->json([
                'success' => false,
                'error' => $error,
            ], $this->getStatusCode($error['code']));
        }
    }

    private function getStatusCode(string $code): int
    {
        return match ($code) {
            'NOT_FOUND', 'SESSION_NOT_FOUND' => 404,
            'UNAUTHORIZED', 'CENTER_MISMATCH', 'DEVICE_MISMATCH',
            'ENROLLMENT_REQUIRED', 'VIEW_LIMIT_EXCEEDED' => 403,
            'CONCURRENT_DEVICE', 'SESSION_ENDED', 'INVALID_STATE' => 409,
            'VIDEO_NOT_READY', 'NO_ACTIVE_DEVICE', 'PENDING_REQUEST_EXISTS' => 422,
            default => 500,
        };
    }
}
```

**Controller Rules:**
- Constructor injection for services
- Type-hint FormRequest for validation
- Route model binding for entities
- Try-catch for DomainExceptions
- Return JsonResponse with success wrapper
- No business logic - delegate to services
- Use resources for complex responses

### 3. FormRequest Validation

**Request Structure:**
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

final class PlaybackProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization done in service layer
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:playback_sessions,id'],
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required.',
            'session_id.exists' => 'Invalid session ID.',
            'percentage.min' => 'Percentage must be between 0 and 100.',
            'percentage.max' => 'Percentage must be between 0 and 100.',
        ];
    }

    /**
     * Scribe documentation
     *
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [
            'session_id' => [
                'description' => 'The playback session ID.',
                'example' => 123,
            ],
            'percentage' => [
                'description' => 'Current playback progress (0-100).',
                'example' => 50,
            ],
        ];
    }
}
```

**Validation Rules Patterns:**
```php
// Common patterns
'email' => ['required', 'email', 'unique:users,email'],
'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
'status' => ['required', 'in:PENDING,APPROVED,REJECTED'],
'device_id' => ['required', 'string', 'uuid'],
'reason' => ['nullable', 'string', 'max:500'],
'granted_views' => ['required', 'integer', 'min:1', 'max:10'],

// Conditional rules
'new_password' => ['required_with:current_password', 'string', 'min:8'],
'video_id' => ['required_without:pdf_id', 'integer', 'exists:videos,id'],

// Custom rules
'device_id' => ['required', new ValidDeviceId()],
'percentage' => ['required', new PercentageRange(0, 100)],
```

### 4. API Resource Pattern

**Simple Resource:**
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
final class VideoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration_seconds' => $this->duration_seconds,
            'thumbnail_url' => $this->thumbnail_url,
            'encoding_status' => $this->encoding_status,
            'lifecycle_status' => $this->lifecycle_status,
            'is_ready' => $this->encoding_status === Video::ENCODING_READY
                       && $this->lifecycle_status === Video::LIFECYCLE_READY,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

**Resource with Relationships:**
```php
final class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'instructor_name' => $this->instructor_name,
            
            // Conditional relationships
            'center' => $this->whenLoaded('center', fn () => 
                new CenterResource($this->center)
            ),
            
            'videos' => $this->whenLoaded('videos', fn () => 
                VideoResource::collection($this->videos)
            ),
            
            // Conditional fields
            'settings' => $this->when(
                $request->user()?->is_admin,
                fn () => $this->settings
            ),
            
            // Computed fields
            'video_count' => $this->whenCounted('videos'),
            'total_duration' => $this->when(
                $this->relationLoaded('videos'),
                fn () => $this->videos->sum('duration_seconds')
            ),
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

**Resource Collections with Pagination:**
```php
final class CourseCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
        ];
    }
}
```

### 5. Response Format Standards

**Success Response (Single Resource):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Introduction to Physics",
    "status": "published"
  }
}
```

**Success Response (Collection):**
```json
{
  "success": true,
  "data": [
    {"id": 1, "title": "Video 1"},
    {"id": 2, "title": "Video 2"}
  ],
  "meta": {
    "total": 50,
    "count": 15,
    "per_page": 15,
    "current_page": 1,
    "total_pages": 4
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "VIEW_LIMIT_EXCEEDED",
    "message": "No remaining views for this video."
  }
}
```

**Validation Error Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### 6. Pagination Implementation

**Simple Pagination:**
```php
public function index(Request $request): JsonResponse
{
    $perPage = $request->input('per_page', 15);
    $perPage = min($perPage, 100); // Max 100 items
    
    $videos = Video::query()
        ->where('center_id', $request->user()->center_id)
        ->where('lifecycle_status', Video::LIFECYCLE_PUBLISHED)
        ->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => VideoResource::collection($videos)->resolve(),
        'meta' => [
            'total' => $videos->total(),
            'per_page' => $videos->perPage(),
            'current_page' => $videos->currentPage(),
            'last_page' => $videos->lastPage(),
        ],
    ]);
}
```

**Cursor Pagination (for large datasets):**
```php
public function index(): JsonResponse
{
    $sessions = PlaybackSession::query()
        ->where('user_id', auth()->id())
        ->orderBy('started_at', 'desc')
        ->cursorPaginate(20);
    
    return response()->json([
        'success' => true,
        'data' => PlaybackSessionResource::collection($sessions)->resolve(),
        'meta' => [
            'next_cursor' => $sessions->nextCursor()?->encode(),
            'prev_cursor' => $sessions->previousCursor()?->encode(),
            'per_page' => $sessions->perPage(),
        ],
    ]);
}
```

### 7. Filtering and Sorting

**Query Parameter Filtering:**
```php
public function index(VideoIndexRequest $request): JsonResponse
{
    $query = Video::query()
        ->where('center_id', $request->user()->center_id);
    
    // Apply filters from request
    if ($request->has('status')) {
        $query->where('lifecycle_status', $request->input('status'));
    }
    
    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
    
    if ($request->has('tag')) {
        $query->whereJsonContains('tags', $request->input('tag'));
    }
    
    // Apply sorting
    $sortBy = $request->input('sort_by', 'created_at');
    $sortOrder = $request->input('sort_order', 'desc');
    $query->orderBy($sortBy, $sortOrder);
    
    $videos = $query->paginate($request->input('per_page', 15));
    
    return response()->json([
        'success' => true,
        'data' => VideoResource::collection($videos)->resolve(),
        'meta' => [...],
    ]);
}
```

**FormRequest with Filters:**
```php
final class VideoIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:0,1,2,3,4'],
            'tag' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'string', 'in:title,created_at,duration_seconds'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Scribe documentation
     *
     * @return array<string, mixed>
     */
    public function queryParameters(): array
    {
        return [
            'search' => [
                'description' => 'Search in title and description',
                'example' => 'physics',
            ],
            'status' => [
                'description' => 'Filter by lifecycle status',
                'example' => 3,
            ],
            'sort_by' => [
                'description' => 'Sort by field',
                'example' => 'created_at',
            ],
            'sort_order' => [
                'description' => 'Sort order',
                'example' => 'desc',
            ],
            'per_page' => [
                'description' => 'Items per page (max 100)',
                'example' => 15,
            ],
        ];
    }
}
```

### 8. Scribe Documentation

**Controller Docblocks:**
```php
/**
 * Request video playback session
 *
 * Starts a new playback session for the authenticated student.
 * Validates enrollment, view limits, and device status before
 * generating a Bunny Stream embed token.
 *
 * @group Playback
 * @authenticated
 *
 * @urlParam center integer required The center ID. Example: 1
 * @urlParam course integer required The course ID. Example: 5
 * @urlParam video integer required The video ID. Example: 10
 *
 * @response 200 {
 *   "success": true,
 *   "data": {
 *     "library_id": "55",
 *     "video_uuid": "abc-123-def",
 *     "embed_token": "sha256hash...",
 *     "embed_token_expires": 1705320240,
 *     "session_id": 123,
 *     "expires_in": 240
 *   }
 * }
 *
 * @response 403 scenario="View limit exceeded" {
 *   "success": false,
 *   "error": {
 *     "code": "VIEW_LIMIT_EXCEEDED",
 *     "message": "No remaining views for this video."
 *   }
 * }
 */
public function requestPlayback(
    RequestPlaybackRequest $request,
    Center $center,
    Course $course,
    Video $video
): JsonResponse {
    // Implementation
}
```

**Scribe Configuration:**
```php
// config/scribe.php
return [
    'type' => 'laravel',
    'theme' => 'default',
    'title' => 'Najaah LMS API Documentation',
    'description' => 'API documentation for Najaah Learning Management System',
    'base_url' => env('APP_URL', 'http://localhost'),
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/v1/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],
    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{YOUR_AUTH_TOKEN}',
    ],
];
```

### 9. Error Handling Middleware

**Global Exception Handler:**
```php
// app/Exceptions/Handler.php
public function register(): void
{
    $this->renderable(function (DomainException $e, Request $request) {
        if ($request->expectsJson()) {
            $error = json_decode($e->getMessage(), true);
            
            return response()->json([
                'success' => false,
                'error' => $error,
            ], $this->getStatusCode($error['code'] ?? 'UNKNOWN'));
        }
        
        return null;
    });
    
    $this->renderable(function (ModelNotFoundException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource not found.',
                ],
            ], 404);
        }
        
        return null;
    });
}
```

### 10. Rate Limiting

**API Rate Limits:**
```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    // Global API limit
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
    
    // Playback requests (stricter)
    RateLimiter::for('playback', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()->id);
    });
    
    // Authentication (very strict)
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

**Apply Rate Limiting:**
```php
// routes/api.php
Route::middleware(['auth:jwt', 'throttle:playback'])->group(function () {
    Route::post('videos/{video}/request_playback', [PlaybackController::class, 'requestPlayback']);
});

Route::middleware('throttle:auth')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/otp/send', [OtpController::class, 'send']);
});
```

---

## API Design Checklist

When creating new endpoints:

- [ ] Follow RESTful conventions
- [ ] Use proper HTTP methods (GET, POST, PUT, DELETE)
- [ ] Implement FormRequest validation
- [ ] Create API Resource for response
- [ ] Add error handling with proper codes
- [ ] Implement pagination for collections
- [ ] Add filtering and sorting
- [ ] Document with Scribe annotations
- [ ] Apply rate limiting
- [ ] Write feature tests
- [ ] Test all error scenarios
- [ ] Update API documentation

---

## Route Organization

**Group by Feature:**
```php
// routes/api.php

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('auth/otp/send', [OtpController::class, 'send']);
    Route::post('auth/otp/verify', [OtpController::class, 'verify']);
});

// Student routes
Route::middleware('auth:jwt')->prefix('v1')->group(function () {
    // Playback
    Route::prefix('centers/{center}/courses/{course}/videos/{video}')->group(function () {
        Route::post('request_playback', [PlaybackController::class, 'requestPlayback']);
        Route::post('refresh_token', [PlaybackController::class, 'refreshToken']);
        Route::post('playback_progress', [PlaybackController::class, 'updateProgress']);
        Route::post('close_session', [PlaybackController::class, 'closeSession']);
    });
    
    // Device management
    Route::post('settings/device-change', [DeviceChangeRequestController::class, 'create']);
    Route::get('settings/device', [DeviceController::class, 'show']);
    
    // Extra views
    Route::post('extra-views/request', [ExtraViewRequestController::class, 'create']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('v1/admin')->group(function () {
    Route::resource('courses', CourseController::class);
    Route::resource('videos', VideoController::class);
    
    // Approval workflows
    Route::post('device-change-requests/{request}/approve', [AdminDeviceChangeController::class, 'approve']);
    Route::post('device-change-requests/{request}/reject', [AdminDeviceChangeController::class, 'reject']);
    Route::post('extra-view-requests/{request}/approve', [AdminExtraViewController::class, 'approve']);
});
```

---

## API Versioning Strategy

**Current: v1**
- All endpoints under `/api/v1/`
- Breaking changes require new version
- Maintain backward compatibility within version

**When to Create v2:**
- Breaking changes to request/response format
- Removal of fields
- Change in authentication mechanism
- Major business logic changes

**Migration Strategy:**
- Keep v1 running while v2 is adopted
- Deprecation notices in v1 responses
- Documentation for migration path
- Sunset date for v1

---

## Related Skills
- Master Skill: `/mnt/skills/user/najaah/SKILL.md`
- Feature Agent: `/mnt/skills/user/najaah-features/SKILL.md`
- Code Quality Agent: `/mnt/skills/user/najaah-quality/SKILL.md`