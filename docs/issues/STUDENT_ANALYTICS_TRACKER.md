# Student Analytics Feature - Issue Tracker

> **Created**: 2026-02-05
> **Status**: Planning Complete
> **Priority**: High
> **Estimated Effort**: 4-5 weeks

---

## Overview

Implement comprehensive student analytics derived from the `playback_sessions` table. This includes individual student metrics, admin access to student analytics, and bulk operations.

### Data Source
- **Primary Table**: `playback_sessions`
- **Key Fields**: `student_id`, `video_id`, `watch_duration`, `completion_percentage`, `view_count`, `started_at`, `ended_at`, `device_info`

### Authorization
- Admins can only view students in their center (`center_id` scoping)
- Super admins (`is_super = true`) can view all students
- Reuse existing `ScopeToCenterMiddleware`

---

## Phase 1: Foundation

### Issue #1: Create StudentAnalyticsInterface

**Priority**: P0 (Blocker)
**Estimate**: 1 hour
**Dependencies**: None

**File**: `app/Services/Contracts/StudentAnalyticsInterface.php`

**Description**: Define the contract for student analytics service.

**Methods to Define**:
```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StudentAnalyticsInterface
{
    /**
     * Get comprehensive overview of student's learning metrics.
     *
     * @return array{
     *   student: array,
     *   summary: array{
     *     total_watch_time: int,
     *     total_watch_time_formatted: string,
     *     videos_started: int,
     *     videos_completed: int,
     *     completion_rate: float,
     *     current_streak_days: int,
     *     longest_streak_days: int,
     *     total_sessions: int,
     *     avg_session_duration: int,
     *     engagement_score: float,
     *     risk_level: string
     *   },
     *   enrollments: array,
     *   recent_activity: array
     * }
     */
    public function getOverview(Student $student): array;

    /**
     * Get detailed course-by-course progress.
     *
     * @return array{courses: array, milestones: array}
     */
    public function getProgress(Student $student, ?int $courseId = null): array;

    /**
     * Get time investment analysis with patterns.
     *
     * @param string $period Options: 7d, 30d, 90d, 1y, all
     * @param string $granularity Options: hourly, daily, weekly, monthly
     * @return array{summary: array, time_series: array, patterns: array, streaks: array, devices: array}
     */
    public function getTimeInvestment(Student $student, string $period = '30d', string $granularity = 'daily'): array;

    /**
     * Get engagement patterns and behavior analysis.
     *
     * @return array{
     *   engagement_score: float,
     *   score_components: array,
     *   engagement_level: string,
     *   behavior_profile: string,
     *   completion_patterns: array,
     *   rewatch_behavior: array,
     *   dropout_indicators: array,
     *   comparison_to_peers: array
     * }
     */
    public function getEngagementPatterns(Student $student): array;

    /**
     * Get paginated activity history.
     *
     * @return LengthAwarePaginator
     */
    public function getActivityHistory(
        Student $student,
        ?string $from = null,
        ?string $to = null,
        bool $includeAbandoned = true,
        int $perPage = 50
    ): LengthAwarePaginator;

    /**
     * Get performance comparison with peers.
     *
     * @param string $compareeTo Options: center, course, cohort
     * @return array{student: array, comparison_group: array, percentiles: array, strengths: array, areas_for_improvement: array}
     */
    public function getPerformanceComparison(Student $student, string $compareTo = 'center', ?int $courseId = null): array;

    /**
     * Get dropout risk assessment.
     *
     * @return array{
     *   risk_level: string,
     *   risk_score: float,
     *   prediction_confidence: float,
     *   factors: array,
     *   historical_risk: array,
     *   intervention_recommendations: array,
     *   predicted_outcome: array
     * }
     */
    public function getRiskAssessment(Student $student): array;

    /**
     * Search students with analytics filters.
     *
     * @param array{
     *   query?: string,
     *   center_id?: int,
     *   engagement_level?: string,
     *   course_id?: int,
     *   last_active?: string,
     *   completion_rate_min?: float,
     *   completion_rate_max?: float,
     *   sort_by?: string,
     *   order?: string
     * } $filters
     * @return LengthAwarePaginator
     */
    public function searchStudents(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Export student analytics data.
     *
     * @param array<int> $studentIds
     * @param string $format Options: csv, pdf, json
     * @return mixed File download response or job dispatch
     */
    public function exportStudentAnalytics(
        array $studentIds,
        string $format,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): mixed;

    /**
     * Compare multiple students' metrics.
     *
     * @param array<int> $studentIds
     * @param array<string> $metrics Options: completion_rate, watch_time, engagement_score, session_duration
     * @param array<int> $courseIds Optional course filter
     * @return array{comparison_date: string, students: array, aggregates: array, rankings: array}
     */
    public function compareStudents(array $studentIds, array $metrics, array $courseIds = []): array;
}
```

**Acceptance Criteria**:
- [ ] File created at correct location
- [ ] All method signatures defined with proper types
- [ ] PHPDoc comments with return type arrays documented
- [ ] PHPStan passes (level 7)

---

### Issue #2: Create StudentAnalyticsService - Core Methods

**Priority**: P0 (Blocker)
**Estimate**: 4 hours
**Dependencies**: Issue #1

**File**: `app/Services/Analytics/StudentAnalyticsService.php`

**Description**: Implement the core analytics calculations.

**Implementation Details**:

```php
<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Student;
use App\Models\PlaybackSession;
use App\Models\Video;
use App\Services\Contracts\StudentAnalyticsInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

final class StudentAnalyticsService implements StudentAnalyticsInterface
{
    // Implement all methods from interface
}
```

**Key Calculations**:

1. **Total Watch Time**:
```php
$totalWatchTime = $student->playbackSessions()->sum('watch_duration');
```

2. **Completion Rate**:
```php
$videosCompleted = $student->playbackSessions()
    ->where('completion_percentage', '>=', 95)
    ->distinct('video_id')
    ->count('video_id');

$totalVideos = Video::whereHas('section.course.enrollments', function ($q) use ($student) {
    $q->where('student_id', $student->id);
})->count();

$completionRate = $totalVideos > 0 ? ($videosCompleted / $totalVideos) * 100 : 0;
```

3. **Study Streak**:
```php
private function calculateStreak(Student $student): array
{
    $dates = $student->playbackSessions()
        ->selectRaw('DATE(started_at) as study_date')
        ->distinct()
        ->orderBy('study_date', 'desc')
        ->pluck('study_date')
        ->map(fn($d) => Carbon::parse($d));

    $currentStreak = 0;
    $maxStreak = 0;
    $tempStreak = 1;
    $today = Carbon::today();

    if ($dates->isEmpty()) {
        return ['current' => 0, 'longest' => 0];
    }

    // Check if studied today or yesterday for current streak
    $lastStudyDate = $dates->first();
    $daysSinceLastStudy = $lastStudyDate->diffInDays($today);

    if ($daysSinceLastStudy > 1) {
        $currentStreak = 0;
    } else {
        $currentStreak = 1;
        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates[$i - 1]->diffInDays($dates[$i]) === 1) {
                $currentStreak++;
            } else {
                break;
            }
        }
    }

    // Calculate longest streak
    $tempStreak = 1;
    for ($i = 1; $i < $dates->count(); $i++) {
        if ($dates[$i - 1]->diffInDays($dates[$i]) === 1) {
            $tempStreak++;
        } else {
            $maxStreak = max($maxStreak, $tempStreak);
            $tempStreak = 1;
        }
    }
    $maxStreak = max($maxStreak, $tempStreak, $currentStreak);

    return ['current' => $currentStreak, 'longest' => $maxStreak];
}
```

4. **Engagement Score** (0-100):
```php
private function calculateEngagementScore(Student $student): float
{
    $sessions = $student->playbackSessions();

    if ($sessions->count() === 0) {
        return 0.0;
    }

    // Completion quality (40%)
    $avgCompletion = $sessions->avg('completion_percentage') ?? 0;
    $completionScore = $avgCompletion * 0.4;

    // Consistency (30%) - sessions per week over last 4 weeks
    $fourWeeksAgo = now()->subWeeks(4);
    $recentSessions = $student->playbackSessions()
        ->where('started_at', '>=', $fourWeeksAgo)
        ->count();
    $sessionsPerWeek = $recentSessions / 4;
    $consistencyScore = min(100, $sessionsPerWeek * 10) * 0.3; // 10 sessions/week = max

    // Watch time (20%) - compare to expected
    $totalWatchTime = $sessions->sum('watch_duration');
    $hoursWatched = $totalWatchTime / 3600;
    $watchTimeScore = min(100, $hoursWatched) * 0.2; // 100 hours = max

    // Recency (10%) - days since last activity
    $lastSession = $student->playbackSessions()->latest('started_at')->first();
    $daysSinceActive = $lastSession
        ? $lastSession->started_at->diffInDays(now())
        : 30;
    $recencyScore = max(0, 100 - ($daysSinceActive * 3.33)) * 0.1;

    return round($completionScore + $consistencyScore + $watchTimeScore + $recencyScore, 1);
}
```

5. **Risk Level**:
```php
private function calculateRiskLevel(float $engagementScore, Student $student): string
{
    // Check recent activity decline
    $twoWeeksAgo = now()->subWeeks(2);
    $fourWeeksAgo = now()->subWeeks(4);

    $recentSessions = $student->playbackSessions()
        ->where('started_at', '>=', $twoWeeksAgo)
        ->count();

    $previousSessions = $student->playbackSessions()
        ->whereBetween('started_at', [$fourWeeksAgo, $twoWeeksAgo])
        ->count();

    $activityDecline = $previousSessions > 0
        ? (($previousSessions - $recentSessions) / $previousSessions) * 100
        : 0;

    // Risk calculation
    if ($engagementScore < 30 || $activityDecline > 50) {
        return 'high';
    } elseif ($engagementScore < 50 || $activityDecline > 25) {
        return 'medium';
    }
    return 'low';
}
```

**Acceptance Criteria**:
- [ ] All interface methods implemented
- [ ] Calculations are accurate and tested
- [ ] Queries are optimized (no N+1)
- [ ] PHPStan passes
- [ ] Unit tests written for calculations

---

### Issue #3: Create StudentAnalyticsController

**Priority**: P0 (Blocker)
**Estimate**: 2 hours
**Dependencies**: Issue #2

**File**: `app/Http/Controllers/Api/V1/Admin/Analytics/StudentAnalyticsController.php`

**Description**: Create controller with all analytics endpoints.

**Implementation**:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Analytics\StudentSearchRequest;
use App\Http\Requests\Admin\Analytics\StudentExportRequest;
use App\Http\Requests\Admin\Analytics\StudentCompareRequest;
use App\Http\Requests\Admin\Analytics\ActivityHistoryRequest;
use App\Http\Requests\Admin\Analytics\TimeInvestmentRequest;
use App\Http\Requests\Admin\Analytics\PerformanceComparisonRequest;
use App\Http\Resources\Admin\Analytics\StudentOverviewResource;
use App\Http\Resources\Admin\Analytics\StudentProgressResource;
use App\Http\Resources\Admin\Analytics\TimeInvestmentResource;
use App\Http\Resources\Admin\Analytics\EngagementPatternsResource;
use App\Http\Resources\Admin\Analytics\ActivityHistoryResource;
use App\Http\Resources\Admin\Analytics\PerformanceComparisonResource;
use App\Http\Resources\Admin\Analytics\RiskAssessmentResource;
use App\Http\Resources\Admin\Analytics\StudentSearchResource;
use App\Models\Student;
use App\Services\Contracts\StudentAnalyticsInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentAnalyticsController extends Controller
{
    public function __construct(
        private readonly StudentAnalyticsInterface $analyticsService
    ) {}

    /**
     * GET /admin/students/{student}/analytics/overview
     */
    public function overview(Student $student): JsonResponse
    {
        $data = $this->analyticsService->getOverview($student);

        return response()->json([
            'success' => true,
            'data' => new StudentOverviewResource($data),
        ]);
    }

    /**
     * GET /admin/students/{student}/analytics/progress
     */
    public function progress(Request $request, Student $student): JsonResponse
    {
        $courseId = $request->query('course_id');
        $data = $this->analyticsService->getProgress($student, $courseId ? (int) $courseId : null);

        return response()->json([
            'success' => true,
            'data' => new StudentProgressResource($data),
        ]);
    }

    /**
     * GET /admin/students/{student}/analytics/time-investment
     */
    public function timeInvestment(TimeInvestmentRequest $request, Student $student): JsonResponse
    {
        $data = $this->analyticsService->getTimeInvestment(
            $student,
            $request->validated('period', '30d'),
            $request->validated('granularity', 'daily')
        );

        return response()->json([
            'success' => true,
            'data' => new TimeInvestmentResource($data),
        ]);
    }

    /**
     * GET /admin/students/{student}/analytics/engagement
     */
    public function engagementPatterns(Student $student): JsonResponse
    {
        $data = $this->analyticsService->getEngagementPatterns($student);

        return response()->json([
            'success' => true,
            'data' => new EngagementPatternsResource($data),
        ]);
    }

    /**
     * GET /admin/students/{student}/analytics/activity-history
     */
    public function activityHistory(ActivityHistoryRequest $request, Student $student): JsonResponse
    {
        $data = $this->analyticsService->getActivityHistory(
            $student,
            $request->validated('from'),
            $request->validated('to'),
            $request->validated('include_abandoned', true),
            $request->validated('per_page', 50)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityHistoryResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin/students/{student}/analytics/performance-comparison
     */
    public function performanceComparison(PerformanceComparisonRequest $request, Student $student): JsonResponse
    {
        $data = $this->analyticsService->getPerformanceComparison(
            $student,
            $request->validated('compare_to', 'center'),
            $request->validated('course_id')
        );

        return response()->json([
            'success' => true,
            'data' => new PerformanceComparisonResource($data),
        ]);
    }

    /**
     * GET /admin/students/{student}/analytics/risk-assessment
     */
    public function riskAssessment(Student $student): JsonResponse
    {
        $data = $this->analyticsService->getRiskAssessment($student);

        return response()->json([
            'success' => true,
            'data' => new RiskAssessmentResource($data),
        ]);
    }

    /**
     * GET /admin/students/search
     */
    public function search(StudentSearchRequest $request): JsonResponse
    {
        $admin = $request->user();
        $filters = $request->validated();

        // Scope to center unless super admin
        if (!$admin->isSuper()) {
            $filters['center_id'] = $admin->center_id;
        }

        $data = $this->analyticsService->searchStudents($filters, $request->validated('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => StudentSearchResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ],
        ]);
    }

    /**
     * GET /admin/students/analytics/export
     */
    public function export(StudentExportRequest $request): mixed
    {
        return $this->analyticsService->exportStudentAnalytics(
            $request->validated('student_ids'),
            $request->validated('format'),
            $request->validated('date_from'),
            $request->validated('date_to')
        );
    }

    /**
     * POST /admin/students/analytics/compare
     */
    public function compare(StudentCompareRequest $request): JsonResponse
    {
        $data = $this->analyticsService->compareStudents(
            $request->validated('student_ids'),
            $request->validated('metrics'),
            $request->validated('courses', [])
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
```

**Acceptance Criteria**:
- [ ] All 10 endpoints implemented
- [ ] Proper dependency injection
- [ ] Correct HTTP methods used
- [ ] Consistent response format
- [ ] PHPStan passes

---

### Issue #4: Create Form Requests

**Priority**: P0 (Blocker)
**Estimate**: 2 hours
**Dependencies**: None

**Files to Create**:

#### 4.1 `app/Http/Requests/Admin/Analytics/TimeInvestmentRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use Illuminate\Foundation\Http\FormRequest;

final class TimeInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', 'in:7d,30d,90d,1y,all'],
            'granularity' => ['nullable', 'string', 'in:hourly,daily,weekly,monthly'],
        ];
    }
}
```

#### 4.2 `app/Http/Requests/Admin/Analytics/ActivityHistoryRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use Illuminate\Foundation\Http\FormRequest;

final class ActivityHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'before_or_equal:today'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'before_or_equal:today'],
            'include_abandoned' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

#### 4.3 `app/Http/Requests/Admin/Analytics/PerformanceComparisonRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use Illuminate\Foundation\Http\FormRequest;

final class PerformanceComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'compare_to' => ['nullable', 'string', 'in:center,course,cohort'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id', 'required_if:compare_to,course'],
        ];
    }
}
```

#### 4.4 `app/Http/Requests/Admin/Analytics/StudentSearchRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use Illuminate\Foundation\Http\FormRequest;

final class StudentSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'engagement_level' => ['nullable', 'string', 'in:high,medium,low,at-risk'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'last_active' => ['nullable', 'string', 'in:7d,14d,30d,90d'],
            'completion_rate_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'completion_rate_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:completion_rate_min'],
            'sort_by' => ['nullable', 'string', 'in:name,last_active,completion_rate,watch_time,engagement_score'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

#### 4.5 `app/Http/Requests/Admin/Analytics/StudentExportRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use Illuminate\Foundation\Http\FormRequest;

final class StudentExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Additional center-based validation in controller
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'format' => ['required', 'string', 'in:csv,pdf,json'],
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
        ];
    }
}
```

#### 4.6 `app/Http/Requests/Admin/Analytics/StudentCompareRequest.php`
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use Illuminate\Foundation\Http\FormRequest;

final class StudentCompareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:2', 'max:10'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'metrics' => ['required', 'array', 'min:1'],
            'metrics.*' => ['string', 'in:completion_rate,watch_time,engagement_score,session_duration'],
            'courses' => ['nullable', 'array'],
            'courses.*' => ['integer', 'exists:courses,id'],
        ];
    }
}
```

**Acceptance Criteria**:
- [ ] All 6 request files created
- [ ] Validation rules complete
- [ ] Date validations consistent (before_or_equal:today)
- [ ] PHPStan passes

---

### Issue #5: Register Routes

**Priority**: P0 (Blocker)
**Estimate**: 30 minutes
**Dependencies**: Issue #3

**File**: `routes/api.php`

**Add to existing admin routes**:
```php
use App\Http\Controllers\Api\V1\Admin\Analytics\StudentAnalyticsController;

Route::prefix('v1')->group(function () {
    // ... existing routes

    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'admin', ScopeToCenterMiddleware::class])
        ->group(function () {
            // ... existing admin routes

            // Student Analytics Routes
            Route::prefix('students/{student}/analytics')->group(function () {
                Route::get('overview', [StudentAnalyticsController::class, 'overview'])
                    ->name('admin.students.analytics.overview');
                Route::get('progress', [StudentAnalyticsController::class, 'progress'])
                    ->name('admin.students.analytics.progress');
                Route::get('time-investment', [StudentAnalyticsController::class, 'timeInvestment'])
                    ->name('admin.students.analytics.time-investment');
                Route::get('engagement', [StudentAnalyticsController::class, 'engagementPatterns'])
                    ->name('admin.students.analytics.engagement');
                Route::get('activity-history', [StudentAnalyticsController::class, 'activityHistory'])
                    ->name('admin.students.analytics.activity-history');
                Route::get('performance-comparison', [StudentAnalyticsController::class, 'performanceComparison'])
                    ->name('admin.students.analytics.performance-comparison');
                Route::get('risk-assessment', [StudentAnalyticsController::class, 'riskAssessment'])
                    ->name('admin.students.analytics.risk-assessment');
            });

            // Bulk Analytics Operations
            Route::get('students/search', [StudentAnalyticsController::class, 'search'])
                ->name('admin.students.search');
            Route::get('students/analytics/export', [StudentAnalyticsController::class, 'export'])
                ->name('admin.students.analytics.export');
            Route::post('students/analytics/compare', [StudentAnalyticsController::class, 'compare'])
                ->name('admin.students.analytics.compare');
        });
});
```

**Acceptance Criteria**:
- [ ] All routes registered
- [ ] Named routes for easy reference
- [ ] Middleware properly applied
- [ ] Route model binding works for {student}

---

### Issue #6: Register Service in AppServiceProvider

**Priority**: P0 (Blocker)
**Estimate**: 15 minutes
**Dependencies**: Issue #1, Issue #2

**File**: `app/Providers/AppServiceProvider.php`

**Add binding**:
```php
use App\Services\Analytics\StudentAnalyticsService;
use App\Services\Contracts\StudentAnalyticsInterface;

public function register(): void
{
    // ... existing bindings

    $this->app->bind(StudentAnalyticsInterface::class, StudentAnalyticsService::class);
}
```

**Acceptance Criteria**:
- [ ] Service binding added
- [ ] Application boots without errors
- [ ] Dependency injection works

---

## Phase 2: API Resources

### Issue #7: Create StudentOverviewResource

**Priority**: P1
**Estimate**: 1 hour
**Dependencies**: Issue #2

**File**: `app/Http/Resources/Admin/Analytics/StudentOverviewResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array $resource
 */
final class StudentOverviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'student' => [
                'id' => $this->resource['student']['id'],
                'name' => $this->resource['student']['name'],
                'email' => $this->resource['student']['email'],
                'center_id' => $this->resource['student']['center_id'],
                'center_name' => $this->resource['student']['center_name'],
                'enrolled_at' => $this->resource['student']['enrolled_at'],
                'last_active' => $this->resource['student']['last_active'],
                'is_active' => $this->resource['student']['is_active'],
            ],
            'summary' => [
                'total_watch_time' => $this->resource['summary']['total_watch_time'],
                'total_watch_time_formatted' => $this->formatDuration($this->resource['summary']['total_watch_time']),
                'videos_started' => $this->resource['summary']['videos_started'],
                'videos_completed' => $this->resource['summary']['videos_completed'],
                'completion_rate' => round($this->resource['summary']['completion_rate'], 1),
                'current_streak_days' => $this->resource['summary']['current_streak_days'],
                'longest_streak_days' => $this->resource['summary']['longest_streak_days'],
                'total_sessions' => $this->resource['summary']['total_sessions'],
                'avg_session_duration' => $this->resource['summary']['avg_session_duration'],
                'avg_session_duration_formatted' => $this->formatDuration($this->resource['summary']['avg_session_duration']),
                'engagement_score' => $this->resource['summary']['engagement_score'],
                'risk_level' => $this->resource['summary']['risk_level'],
            ],
            'enrollments' => $this->resource['enrollments'],
            'recent_activity' => $this->resource['recent_activity'],
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }
}
```

**Acceptance Criteria**:
- [ ] Resource created with proper structure
- [ ] Duration formatting helper works
- [ ] All fields mapped correctly

---

### Issue #8: Create Additional Resources

**Priority**: P1
**Estimate**: 3 hours
**Dependencies**: Issue #7

**Files to Create**:
- `app/Http/Resources/Admin/Analytics/StudentProgressResource.php`
- `app/Http/Resources/Admin/Analytics/TimeInvestmentResource.php`
- `app/Http/Resources/Admin/Analytics/EngagementPatternsResource.php`
- `app/Http/Resources/Admin/Analytics/ActivityHistoryResource.php`
- `app/Http/Resources/Admin/Analytics/PerformanceComparisonResource.php`
- `app/Http/Resources/Admin/Analytics/RiskAssessmentResource.php`
- `app/Http/Resources/Admin/Analytics/StudentSearchResource.php`

**Note**: Follow same pattern as Issue #7. Each resource should:
- Have proper type declarations
- Include PHPDoc comments
- Handle null values gracefully
- Format dates and durations consistently

**Acceptance Criteria**:
- [ ] All 7 resources created
- [ ] Consistent formatting across resources
- [ ] PHPStan passes

---

## Phase 3: Service Implementation Details

### Issue #9: Implement getOverview Method

**Priority**: P1
**Estimate**: 2 hours
**Dependencies**: Issue #2

**Implementation in StudentAnalyticsService**:

```php
public function getOverview(Student $student): array
{
    $student->load(['center', 'enrollments.course']);

    // Get aggregated session data
    $sessionStats = $student->playbackSessions()
        ->selectRaw('
            COUNT(*) as total_sessions,
            SUM(watch_duration) as total_watch_time,
            AVG(watch_duration) as avg_session_duration,
            COUNT(DISTINCT video_id) as videos_started,
            COUNT(DISTINCT CASE WHEN completion_percentage >= 95 THEN video_id END) as videos_completed,
            MAX(started_at) as last_active
        ')
        ->first();

    // Calculate streaks
    $streaks = $this->calculateStreak($student);

    // Calculate engagement score
    $engagementScore = $this->calculateEngagementScore($student);

    // Calculate risk level
    $riskLevel = $this->calculateRiskLevel($engagementScore, $student);

    // Get enrolled course video counts
    $totalEnrolledVideos = $this->getTotalEnrolledVideos($student);
    $completionRate = $totalEnrolledVideos > 0
        ? ($sessionStats->videos_completed / $totalEnrolledVideos) * 100
        : 0;

    // Get recent activity summary
    $recentActivity = $this->getRecentActivitySummary($student);

    // Format enrollments
    $enrollments = $student->enrollments->map(function ($enrollment) use ($student) {
        $courseProgress = $this->getCourseProgress($student, $enrollment->course);
        return [
            'course_id' => $enrollment->course_id,
            'course_title' => $enrollment->course->title_translations,
            'enrolled_at' => $enrollment->created_at->toIso8601String(),
            'progress_percentage' => $courseProgress['percentage'],
            'videos_completed' => $courseProgress['completed'],
            'total_videos' => $courseProgress['total'],
            'last_watched_at' => $courseProgress['last_watched_at'],
            'status' => $enrollment->status,
        ];
    });

    return [
        'student' => [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'center_id' => $student->center_id,
            'center_name' => $student->center->name ?? null,
            'enrolled_at' => $student->created_at->toIso8601String(),
            'last_active' => $sessionStats->last_active?->toIso8601String(),
            'is_active' => $student->is_active,
        ],
        'summary' => [
            'total_watch_time' => (int) ($sessionStats->total_watch_time ?? 0),
            'videos_started' => (int) ($sessionStats->videos_started ?? 0),
            'videos_completed' => (int) ($sessionStats->videos_completed ?? 0),
            'completion_rate' => round($completionRate, 1),
            'current_streak_days' => $streaks['current'],
            'longest_streak_days' => $streaks['longest'],
            'total_sessions' => (int) ($sessionStats->total_sessions ?? 0),
            'avg_session_duration' => (int) ($sessionStats->avg_session_duration ?? 0),
            'engagement_score' => $engagementScore,
            'risk_level' => $riskLevel,
        ],
        'enrollments' => $enrollments->toArray(),
        'recent_activity' => $recentActivity,
    ];
}

private function getTotalEnrolledVideos(Student $student): int
{
    return Video::whereHas('section.course.enrollments', function ($query) use ($student) {
        $query->where('student_id', $student->id);
    })->count();
}

private function getRecentActivitySummary(Student $student): array
{
    $last7Days = $student->playbackSessions()
        ->where('started_at', '>=', now()->subDays(7))
        ->selectRaw('
            COUNT(*) as sessions,
            SUM(watch_duration) as watch_time,
            COUNT(DISTINCT CASE WHEN completion_percentage >= 95 THEN video_id END) as videos_completed
        ')
        ->first();

    $last30Days = $student->playbackSessions()
        ->where('started_at', '>=', now()->subDays(30))
        ->selectRaw('
            COUNT(*) as sessions,
            SUM(watch_duration) as watch_time,
            COUNT(DISTINCT CASE WHEN completion_percentage >= 95 THEN video_id END) as videos_completed
        ')
        ->first();

    return [
        'last_7_days' => [
            'sessions' => (int) ($last7Days->sessions ?? 0),
            'watch_time' => (int) ($last7Days->watch_time ?? 0),
            'videos_completed' => (int) ($last7Days->videos_completed ?? 0),
        ],
        'last_30_days' => [
            'sessions' => (int) ($last30Days->sessions ?? 0),
            'watch_time' => (int) ($last30Days->watch_time ?? 0),
            'videos_completed' => (int) ($last30Days->videos_completed ?? 0),
        ],
    ];
}

private function getCourseProgress(Student $student, Course $course): array
{
    $totalVideos = Video::whereHas('section', fn($q) => $q->where('course_id', $course->id))->count();

    $completed = $student->playbackSessions()
        ->whereHas('video.section', fn($q) => $q->where('course_id', $course->id))
        ->where('completion_percentage', '>=', 95)
        ->distinct('video_id')
        ->count('video_id');

    $lastWatched = $student->playbackSessions()
        ->whereHas('video.section', fn($q) => $q->where('course_id', $course->id))
        ->latest('started_at')
        ->first();

    return [
        'total' => $totalVideos,
        'completed' => $completed,
        'percentage' => $totalVideos > 0 ? round(($completed / $totalVideos) * 100, 1) : 0,
        'last_watched_at' => $lastWatched?->started_at?->toIso8601String(),
    ];
}
```

**Acceptance Criteria**:
- [ ] Method returns complete overview data
- [ ] All calculations are accurate
- [ ] No N+1 queries
- [ ] Handles students with no sessions gracefully

---

### Issue #10: Implement getProgress Method

**Priority**: P1
**Estimate**: 2 hours
**Dependencies**: Issue #9

**Implementation**:
```php
public function getProgress(Student $student, ?int $courseId = null): array
{
    $enrollmentsQuery = $student->enrollments()->with(['course.sections.videos']);

    if ($courseId) {
        $enrollmentsQuery->where('course_id', $courseId);
    }

    $enrollments = $enrollmentsQuery->get();

    $courses = $enrollments->map(function ($enrollment) use ($student) {
        $course = $enrollment->course;

        $sections = $course->sections->sortBy('order')->map(function ($section) use ($student, $course) {
            $videos = $section->videos->sortBy('order')->map(function ($video) use ($student) {
                $session = $student->playbackSessions()
                    ->where('video_id', $video->id)
                    ->selectRaw('
                        MAX(completion_percentage) as best_completion,
                        SUM(watch_duration) as total_watch_time,
                        SUM(view_count) as total_views,
                        MIN(started_at) as first_watched_at,
                        MAX(started_at) as last_watched_at
                    ')
                    ->first();

                $status = 'not_started';
                if ($session && $session->best_completion >= 95) {
                    $status = 'completed';
                } elseif ($session && $session->best_completion > 0) {
                    $status = 'in_progress';
                }

                return [
                    'video_id' => $video->id,
                    'title' => $video->title_translations,
                    'order' => $video->order,
                    'duration_seconds' => $video->duration_seconds,
                    'status' => $status,
                    'completion_percentage' => (float) ($session->best_completion ?? 0),
                    'watch_time' => (int) ($session->total_watch_time ?? 0),
                    'view_count' => (int) ($session->total_views ?? 0),
                    'first_watched_at' => $session?->first_watched_at?->toIso8601String(),
                    'last_watched_at' => $session?->last_watched_at?->toIso8601String(),
                ];
            });

            $completedInSection = $videos->where('status', 'completed')->count();

            return [
                'section_id' => $section->id,
                'section_title' => $section->title_translations,
                'order' => $section->order,
                'videos' => $videos->values()->toArray(),
                'section_completion' => $videos->count() > 0
                    ? round(($completedInSection / $videos->count()) * 100, 1)
                    : 0,
                'videos_completed' => $completedInSection,
                'total_videos' => $videos->count(),
            ];
        });

        $totalVideos = $sections->sum('total_videos');
        $completedVideos = $sections->sum('videos_completed');
        $timeInvested = $student->playbackSessions()
            ->whereHas('video.section', fn($q) => $q->where('course_id', $course->id))
            ->sum('watch_duration');

        return [
            'course_id' => $course->id,
            'course_title' => $course->title_translations,
            'sections' => $sections->values()->toArray(),
            'overall_progress' => $totalVideos > 0
                ? round(($completedVideos / $totalVideos) * 100, 1)
                : 0,
            'time_invested' => (int) $timeInvested,
            'videos_completed' => $completedVideos,
            'total_videos' => $totalVideos,
        ];
    });

    return [
        'courses' => $courses->toArray(),
        'milestones' => $this->getMilestones($student, $courseId),
    ];
}

private function getMilestones(Student $student, ?int $courseId): array
{
    $milestones = [];

    // First video completed
    $firstCompleted = $student->playbackSessions()
        ->where('completion_percentage', '>=', 95)
        ->when($courseId, fn($q) => $q->whereHas('video.section', fn($sq) => $sq->where('course_id', $courseId)))
        ->oldest('started_at')
        ->first();

    if ($firstCompleted) {
        $milestones[] = [
            'type' => 'first_video_completed',
            'achieved_at' => $firstCompleted->started_at->toIso8601String(),
            'video_id' => $firstCompleted->video_id,
        ];
    }

    // More milestones can be added: 50% completion, course completed, etc.

    return $milestones;
}
```

**Acceptance Criteria**:
- [ ] Returns detailed progress per course/section/video
- [ ] Correctly calculates completion status
- [ ] Supports filtering by course_id
- [ ] Includes milestones

---

### Issue #11: Implement getTimeInvestment Method

**Priority**: P1
**Estimate**: 2 hours
**Dependencies**: Issue #9

**Implementation**:
```php
public function getTimeInvestment(Student $student, string $period = '30d', string $granularity = 'daily'): array
{
    $startDate = $this->getPeriodStartDate($period);

    $sessions = $student->playbackSessions()
        ->where('started_at', '>=', $startDate)
        ->get();

    // Summary stats
    $summary = [
        'total_watch_time' => $sessions->sum('watch_duration'),
        'total_sessions' => $sessions->count(),
        'avg_session_duration' => (int) $sessions->avg('watch_duration'),
        'active_days' => $sessions->groupBy(fn($s) => $s->started_at->format('Y-m-d'))->count(),
        'total_days' => (int) $startDate->diffInDays(now()),
    ];
    $summary['consistency_score'] = $summary['total_days'] > 0
        ? round(($summary['active_days'] / $summary['total_days']) * 100, 1)
        : 0;

    // Time series data
    $timeSeries = $this->buildTimeSeries($sessions, $startDate, $granularity);

    // Patterns
    $patterns = $this->analyzePatterns($sessions);

    // Streaks
    $streaks = $this->calculateStreak($student);

    // Device breakdown
    $devices = $this->getDeviceBreakdown($sessions);

    return [
        'summary' => $summary,
        'time_series' => $timeSeries,
        'patterns' => $patterns,
        'streaks' => [
            'current_streak' => $streaks['current'],
            'longest_streak' => $streaks['longest'],
        ],
        'devices' => $devices,
    ];
}

private function getPeriodStartDate(string $period): Carbon
{
    return match ($period) {
        '7d' => now()->subDays(7),
        '30d' => now()->subDays(30),
        '90d' => now()->subDays(90),
        '1y' => now()->subYear(),
        'all' => now()->subYears(10),
        default => now()->subDays(30),
    };
}

private function buildTimeSeries(Collection $sessions, Carbon $startDate, string $granularity): array
{
    $format = match ($granularity) {
        'hourly' => 'Y-m-d H:00',
        'daily' => 'Y-m-d',
        'weekly' => 'Y-W',
        'monthly' => 'Y-m',
        default => 'Y-m-d',
    };

    $grouped = $sessions->groupBy(fn($s) => $s->started_at->format($format));

    return $grouped->map(fn($group, $date) => [
        'date' => $date,
        'watch_time' => $group->sum('watch_duration'),
        'sessions' => $group->count(),
        'videos_completed' => $group->where('completion_percentage', '>=', 95)->unique('video_id')->count(),
    ])->values()->toArray();
}

private function analyzePatterns(Collection $sessions): array
{
    $byDayOfWeek = $sessions->groupBy(fn($s) => $s->started_at->format('l'));
    $byHour = $sessions->groupBy(fn($s) => $s->started_at->format('G'));

    $avgByDay = $byDayOfWeek->map(fn($g) => (int) $g->avg('watch_duration'));
    $avgByHour = $byHour->map(fn($g) => $g->count())->sortDesc();

    $mostActiveDay = $avgByDay->keys()->first();
    $mostActiveHour = $avgByHour->keys()->first();

    return [
        'most_active_day_of_week' => $mostActiveDay,
        'most_active_hour' => (int) $mostActiveHour,
        'avg_watch_time_by_day' => $avgByDay->toArray(),
        'peak_hours' => $avgByHour->take(3)->map(fn($count, $hour) => [
            'hour' => (int) $hour,
            'avg_sessions' => round($count / max(1, $sessions->groupBy(fn($s) => $s->started_at->format('Y-m-d'))->count()), 1),
        ])->values()->toArray(),
    ];
}

private function getDeviceBreakdown(Collection $sessions): array
{
    return $sessions->groupBy('device_info')
        ->map(function ($group, $device) use ($sessions) {
            $totalTime = $sessions->sum('watch_duration');
            return [
                'device_info' => $device ?: 'Unknown',
                'sessions_count' => $group->count(),
                'total_watch_time' => $group->sum('watch_duration'),
                'percentage' => $totalTime > 0
                    ? round(($group->sum('watch_duration') / $totalTime) * 100, 1)
                    : 0,
            ];
        })
        ->sortByDesc('total_watch_time')
        ->values()
        ->toArray();
}
```

**Acceptance Criteria**:
- [ ] Supports all period options (7d, 30d, 90d, 1y, all)
- [ ] Supports all granularity options
- [ ] Returns accurate time series data
- [ ] Analyzes patterns correctly

---

### Issue #12: Implement getEngagementPatterns Method

**Priority**: P1
**Estimate**: 2 hours
**Dependencies**: Issue #9

```php
public function getEngagementPatterns(Student $student): array
{
    $engagementScore = $this->calculateEngagementScore($student);
    $sessions = $student->playbackSessions()->get();

    // Score components breakdown
    $avgCompletion = $sessions->avg('completion_percentage') ?? 0;
    $scoreComponents = [
        'completion_quality' => round($avgCompletion, 1),
        'consistency' => $this->calculateConsistencyScore($student),
        'time_investment' => $this->calculateTimeInvestmentScore($student),
        'recency' => $this->calculateRecencyScore($student),
    ];

    // Engagement level
    $engagementLevel = match (true) {
        $engagementScore >= 75 => 'high',
        $engagementScore >= 50 => 'medium',
        $engagementScore >= 25 => 'low',
        default => 'at-risk',
    };

    // Behavior profile
    $behaviorProfile = $this->determineBehaviorProfile($sessions);

    // Completion patterns
    $completionPatterns = [
        'single_session_completions' => $sessions->where('completion_percentage', '>=', 95)
            ->where('view_count', 1)->unique('video_id')->count(),
        'multi_session_completions' => $sessions->where('completion_percentage', '>=', 95)
            ->where('view_count', '>', 1)->unique('video_id')->count(),
        'abandonments' => $sessions->where('completion_percentage', '<', 25)->count(),
        'avg_completion_percentage' => round($avgCompletion, 1),
    ];

    // Rewatch behavior
    $rewatchBehavior = $this->analyzeRewatchBehavior($student);

    // Dropout indicators
    $dropoutIndicators = $this->analyzeDropoutIndicators($student, $engagementScore);

    // Comparison to peers
    $peerComparison = $this->compareToPeers($student);

    return [
        'engagement_score' => $engagementScore,
        'score_components' => $scoreComponents,
        'engagement_level' => $engagementLevel,
        'behavior_profile' => $behaviorProfile,
        'completion_patterns' => $completionPatterns,
        'rewatch_behavior' => $rewatchBehavior,
        'dropout_indicators' => $dropoutIndicators,
        'comparison_to_peers' => $peerComparison,
    ];
}

private function determineBehaviorProfile(Collection $sessions): string
{
    if ($sessions->isEmpty()) {
        return 'inactive';
    }

    $avgSessionsPerDay = $sessions->groupBy(fn($s) => $s->started_at->format('Y-m-d'))
        ->avg(fn($g) => $g->count());

    $avgCompletionTime = $sessions->avg(fn($s) =>
        $s->ended_at && $s->started_at
            ? $s->ended_at->diffInMinutes($s->started_at)
            : 0
    );

    if ($avgSessionsPerDay >= 3) {
        return 'binge_learner';
    } elseif ($avgCompletionTime > 60) {
        return 'deep_diver';
    } elseif ($avgSessionsPerDay < 1) {
        return 'occasional_learner';
    }

    return 'steady_learner';
}

private function analyzeRewatchBehavior(Student $student): array
{
    $rewatched = $student->playbackSessions()
        ->where('view_count', '>', 1)
        ->with('video:id,title_translations')
        ->orderByDesc('view_count')
        ->limit(5)
        ->get();

    return [
        'videos_rewatched' => $student->playbackSessions()->where('view_count', '>', 1)->distinct('video_id')->count('video_id'),
        'total_rewatches' => $student->playbackSessions()->where('view_count', '>', 1)->sum('view_count') -
            $student->playbackSessions()->where('view_count', '>', 1)->distinct('video_id')->count('video_id'),
        'avg_views_per_video' => round($student->playbackSessions()->avg('view_count') ?? 1, 1),
        'most_rewatched_videos' => $rewatched->map(fn($s) => [
            'video_id' => $s->video_id,
            'title' => $s->video->title_translations ?? 'Unknown',
            'view_count' => $s->view_count,
        ])->toArray(),
    ];
}

private function analyzeDropoutIndicators(Student $student, float $engagementScore): array
{
    $riskLevel = $this->calculateRiskLevel($engagementScore, $student);

    $factors = [];

    // Check activity decline
    $recentSessions = $student->playbackSessions()->where('started_at', '>=', now()->subWeeks(2))->count();
    $previousSessions = $student->playbackSessions()
        ->whereBetween('started_at', [now()->subWeeks(4), now()->subWeeks(2)])
        ->count();

    if ($previousSessions > 0 && $recentSessions < $previousSessions * 0.5) {
        $factors[] = [
            'factor' => 'declining_activity',
            'severity' => 'high',
            'description' => 'Session frequency decreased significantly',
        ];
    }

    // Check abandonment rate increase
    $recentAbandonRate = $this->getAbandonmentRate($student, now()->subWeeks(2));
    $previousAbandonRate = $this->getAbandonmentRate($student, now()->subWeeks(4), now()->subWeeks(2));

    if ($recentAbandonRate > $previousAbandonRate * 1.5) {
        $factors[] = [
            'factor' => 'increasing_abandonment',
            'severity' => 'medium',
            'description' => 'Abandonment rate has increased',
        ];
    }

    return [
        'risk_level' => $riskLevel,
        'risk_score' => $this->calculateRiskScore($student),
        'contributing_factors' => $factors,
    ];
}

private function getAbandonmentRate(Student $student, Carbon $from, ?Carbon $to = null): float
{
    $query = $student->playbackSessions()->where('started_at', '>=', $from);
    if ($to) {
        $query->where('started_at', '<', $to);
    }

    $total = $query->count();
    $abandoned = (clone $query)->where('completion_percentage', '<', 25)->count();

    return $total > 0 ? ($abandoned / $total) * 100 : 0;
}

private function calculateRiskScore(Student $student): float
{
    // 0-100 score, higher = more risk
    $score = 0;

    // Days since last activity (max 30 points)
    $lastSession = $student->playbackSessions()->latest('started_at')->first();
    $daysSinceActive = $lastSession ? $lastSession->started_at->diffInDays(now()) : 30;
    $score += min(30, $daysSinceActive);

    // Abandonment rate (max 30 points)
    $abandonRate = $this->getAbandonmentRate($student, now()->subDays(30));
    $score += $abandonRate * 0.3;

    // Activity decline (max 20 points)
    $recentCount = $student->playbackSessions()->where('started_at', '>=', now()->subWeeks(2))->count();
    $previousCount = $student->playbackSessions()
        ->whereBetween('started_at', [now()->subWeeks(4), now()->subWeeks(2)])
        ->count();
    if ($previousCount > 0) {
        $decline = max(0, ($previousCount - $recentCount) / $previousCount) * 100;
        $score += $decline * 0.2;
    }

    // Low completion rate (max 20 points)
    $avgCompletion = $student->playbackSessions()->avg('completion_percentage') ?? 0;
    $score += max(0, (100 - $avgCompletion)) * 0.2;

    return round(min(100, $score), 1);
}

private function compareToPeers(Student $student): array
{
    $studentMetrics = [
        'completion_rate' => $student->playbackSessions()->avg('completion_percentage') ?? 0,
        'watch_time' => $student->playbackSessions()->sum('watch_duration'),
    ];

    $peerMetrics = PlaybackSession::whereHas('student', fn($q) => $q->where('center_id', $student->center_id))
        ->selectRaw('
            AVG(completion_percentage) as avg_completion,
            SUM(watch_duration) / COUNT(DISTINCT student_id) as avg_watch_time
        ')
        ->first();

    $percentile = $this->calculatePercentile($student, 'engagement_score');

    return [
        'percentile' => $percentile,
        'above_average_metrics' => array_filter([
            $studentMetrics['completion_rate'] > ($peerMetrics->avg_completion ?? 0) ? 'completion_rate' : null,
            $studentMetrics['watch_time'] > ($peerMetrics->avg_watch_time ?? 0) ? 'watch_time' : null,
        ]),
    ];
}

private function calculatePercentile(Student $student, string $metric): int
{
    $studentScore = $this->calculateEngagementScore($student);

    $allScores = Student::where('center_id', $student->center_id)
        ->get()
        ->map(fn($s) => $this->calculateEngagementScore($s))
        ->sort()
        ->values();

    $position = $allScores->search(fn($score) => $score >= $studentScore);

    return $allScores->count() > 0
        ? (int) round(($position / $allScores->count()) * 100)
        : 50;
}
```

**Acceptance Criteria**:
- [ ] All engagement metrics calculated correctly
- [ ] Behavior profiles assigned appropriately
- [ ] Risk indicators accurate
- [ ] Peer comparison works

---

### Issue #13: Implement Remaining Service Methods

**Priority**: P1
**Estimate**: 4 hours
**Dependencies**: Issue #12

**Methods to Implement**:
- `getActivityHistory()` - Paginated session history
- `getPerformanceComparison()` - Compare to center/course/cohort
- `getRiskAssessment()` - Detailed risk analysis with recommendations
- `searchStudents()` - Advanced search with analytics filters
- `exportStudentAnalytics()` - CSV/PDF/JSON export
- `compareStudents()` - Multi-student comparison

**Note**: Each method should follow patterns established in Issues #9-12.

**Acceptance Criteria**:
- [ ] All interface methods implemented
- [ ] Export functionality works (at least CSV)
- [ ] Search includes all filter options
- [ ] Compare handles up to 10 students

---

## Phase 4: Testing

### Issue #14: Unit Tests for Service Methods

**Priority**: P1
**Estimate**: 4 hours
**Dependencies**: Phase 3 complete

**File**: `tests/Unit/Services/StudentAnalyticsServiceTest.php`

**Test Cases**:
```php
// Test streak calculation
public function test_calculates_current_streak_correctly(): void
public function test_calculates_longest_streak_correctly(): void
public function test_streak_resets_after_missed_day(): void

// Test engagement score
public function test_calculates_engagement_score_for_active_student(): void
public function test_engagement_score_zero_for_student_with_no_sessions(): void
public function test_engagement_score_considers_all_components(): void

// Test risk assessment
public function test_identifies_high_risk_student(): void
public function test_identifies_low_risk_student(): void
public function test_risk_factors_detected_correctly(): void

// Test completion rate
public function test_calculates_completion_rate_correctly(): void
public function test_completion_rate_zero_when_no_completed_videos(): void
```

**Acceptance Criteria**:
- [ ] All calculation methods tested
- [ ] Edge cases covered (empty data, null values)
- [ ] Tests pass with 100% coverage of service methods

---

### Issue #15: Feature Tests for API Endpoints

**Priority**: P1
**Estimate**: 4 hours
**Dependencies**: Issue #14

**File**: `tests/Feature/Admin/Analytics/StudentAnalyticsControllerTest.php`

**Test Cases**:
```php
// Authorization tests
public function test_admin_can_view_student_analytics_in_their_center(): void
public function test_admin_cannot_view_student_analytics_in_other_center(): void
public function test_super_admin_can_view_any_student_analytics(): void

// Endpoint tests
public function test_overview_returns_correct_structure(): void
public function test_progress_returns_course_breakdown(): void
public function test_time_investment_supports_period_filter(): void
public function test_activity_history_is_paginated(): void
public function test_search_filters_by_engagement_level(): void
public function test_export_returns_csv_file(): void
public function test_compare_validates_student_count(): void
```

**Acceptance Criteria**:
- [ ] All endpoints tested
- [ ] Authorization tested thoroughly
- [ ] Response structures validated
- [ ] Error cases covered

---

## Phase 5: Optimization

### Issue #16: Add Database Indexes

**Priority**: P2
**Estimate**: 1 hour
**Dependencies**: Phase 4 complete

**File**: `database/migrations/xxxx_add_analytics_indexes_to_playback_sessions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playback_sessions', function (Blueprint $table) {
            // Composite index for student analytics queries
            $table->index(['student_id', 'started_at'], 'idx_playback_student_started');

            // Index for completion filtering
            $table->index(['student_id', 'completion_percentage'], 'idx_playback_student_completion');

            // Index for video analytics
            $table->index(['video_id', 'completion_percentage'], 'idx_playback_video_completion');

            // Index for active session queries
            $table->index(['is_active', 'last_active_at'], 'idx_playback_active');
        });
    }

    public function down(): void
    {
        Schema::table('playback_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_playback_student_started');
            $table->dropIndex('idx_playback_student_completion');
            $table->dropIndex('idx_playback_video_completion');
            $table->dropIndex('idx_playback_active');
        });
    }
};
```

**Acceptance Criteria**:
- [ ] Migration created and runs successfully
- [ ] Query performance improved (measure before/after)

---

### Issue #17: Implement Caching

**Priority**: P2
**Estimate**: 2 hours
**Dependencies**: Issue #16

**Implementation**:
- Cache student overview for 15 minutes
- Cache engagement score for 1 hour
- Invalidate cache on new playback session

```php
// In StudentAnalyticsService
public function getOverview(Student $student): array
{
    $cacheKey = "student_analytics_overview:{$student->id}";

    return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($student) {
        return $this->calculateOverview($student);
    });
}

// Cache invalidation - add observer or event listener
// PlaybackSession::created triggers cache clear for student
```

**Acceptance Criteria**:
- [ ] Caching implemented for expensive queries
- [ ] Cache invalidation works correctly
- [ ] TTL values are appropriate

---

## Summary

| Phase | Issues | Estimate |
|-------|--------|----------|
| Phase 1: Foundation | #1-#6 | ~10 hours |
| Phase 2: Resources | #7-#8 | ~4 hours |
| Phase 3: Service Implementation | #9-#13 | ~12 hours |
| Phase 4: Testing | #14-#15 | ~8 hours |
| Phase 5: Optimization | #16-#17 | ~3 hours |

**Total Estimated Effort**: ~37 hours (approximately 1 week)

---

## Quick Start Checklist

To begin implementation:

1. [ ] Create `app/Services/Contracts/StudentAnalyticsInterface.php` (Issue #1)
2. [ ] Create `app/Services/Analytics/StudentAnalyticsService.php` (Issue #2)
3. [ ] Create `app/Http/Controllers/Api/V1/Admin/Analytics/StudentAnalyticsController.php` (Issue #3)
4. [ ] Create Form Requests in `app/Http/Requests/Admin/Analytics/` (Issue #4)
5. [ ] Add routes to `routes/api.php` (Issue #5)
6. [ ] Register service in `AppServiceProvider` (Issue #6)
7. [ ] Run `php artisan route:list` to verify routes
8. [ ] Test first endpoint with Postman/curl

---

## File Structure After Implementation

```
app/
├── Http/
│   ├── Controllers/Api/V1/Admin/
│   │   └── Analytics/
│   │       └── StudentAnalyticsController.php
│   ├── Requests/Admin/Analytics/
│   │   ├── ActivityHistoryRequest.php
│   │   ├── PerformanceComparisonRequest.php
│   │   ├── StudentCompareRequest.php
│   │   ├── StudentExportRequest.php
│   │   ├── StudentSearchRequest.php
│   │   └── TimeInvestmentRequest.php
│   └── Resources/Admin/Analytics/
│       ├── ActivityHistoryResource.php
│       ├── EngagementPatternsResource.php
│       ├── PerformanceComparisonResource.php
│       ├── RiskAssessmentResource.php
│       ├── StudentOverviewResource.php
│       ├── StudentProgressResource.php
│       ├── StudentSearchResource.php
│       └── TimeInvestmentResource.php
├── Services/
│   ├── Analytics/
│   │   └── StudentAnalyticsService.php
│   └── Contracts/
│       └── StudentAnalyticsInterface.php
tests/
├── Feature/Admin/Analytics/
│   └── StudentAnalyticsControllerTest.php
└── Unit/Services/
    └── StudentAnalyticsServiceTest.php
```
