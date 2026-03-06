# Certificates & Achievements System

> Full gamification system with course completion certificates, achievement badges, points, leaderboards, and student levels.

## Overview

This feature adds a comprehensive gamification layer to the LMS:
- **Course Completion Tracking**: Track video progress at course level
- **Certificates**: Auto-generated or manually issued completion certificates
- **Badges**: Achievement badges for milestones and accomplishments
- **Points**: Earn points for activities (watching videos, completing courses, streaks)
- **Leaderboards**: Center-scoped rankings by points
- **Levels**: Student progression levels based on accumulated points

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    CERTIFICATES & ACHIEVEMENTS SYSTEM                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  COMPLETION TRACKING                                                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      course_progress                                 │   │
│  │  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐                │   │
│  │  │ enrollment  │──▶│ video_progress│──▶│ completion  │               │   │
│  │  │   + user    │   │  (per video) │   │  (100%)     │               │   │
│  │  └─────────────┘   └─────────────┘   └─────────────┘                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                          │                                                  │
│                          ▼                                                  │
│  CERTIFICATES                           ACHIEVEMENTS                        │
│  ┌───────────────────────────┐         ┌───────────────────────────────┐   │
│  │ certificate_templates     │         │ badge_definitions             │   │
│  │ (per course)              │         │ (system + center)             │   │
│  │          │                │         │          │                    │   │
│  │          ▼                │         │          ▼                    │   │
│  │ student_certificates      │         │ student_badges                │   │
│  │ (issued certificates)     │         │ (earned badges)               │   │
│  └───────────────────────────┘         └───────────────────────────────┘   │
│                                                                             │
│  GAMIFICATION                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐                │   │
│  │  │   points    │──▶│   levels    │──▶│ leaderboard │                │   │
│  │  │ (activities)│   │ (thresholds)│   │  (ranking)  │                │   │
│  │  └─────────────┘   └─────────────┘   └─────────────┘                │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### course_progress

Aggregated progress per enrollment.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `enrollment_id` | FK → enrollments | Unique per enrollment |
| `user_id` | FK → users | Student |
| `course_id` | FK → courses | Course |
| `center_id` | FK → centers | Center scope |
| `total_videos` | int | Total videos in course |
| `completed_videos` | int | Videos with 80%+ progress |
| `progress_percent` | decimal(5,2) | Overall progress (0-100) |
| `is_completed` | boolean | All videos completed |
| `completed_at` | timestamp | When completion achieved |
| `last_activity_at` | timestamp | Last video watch |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `UNIQUE [enrollment_id]`
- `[user_id, course_id]`
- `[center_id, is_completed]`
- `[completed_at]`

### certificate_templates

Per-course certificate templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `course_id` | FK → courses | Course this template belongs to |
| `center_id` | FK → centers | Center scope |
| `name_translations` | JSON | Template name |
| `description_translations` | JSON | Certificate description text |
| `template_data` | JSON | Design configuration (colors, layout, fields) |
| `background_image_url` | varchar | Background image URL |
| `signature_image_url` | varchar | Signature image URL |
| `logo_url` | varchar | Logo override (defaults to center logo) |
| `is_active` | boolean | Template is active |
| `auto_issue` | boolean | Auto-issue on completion |
| `created_by` | FK → users | Admin who created |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [course_id]` (one template per course)
- `[center_id, is_active]`

### student_certificates

Issued certificates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `uuid` | uuid | Public unique identifier for verification |
| `user_id` | FK → users | Student |
| `course_id` | FK → courses | Completed course |
| `center_id` | FK → centers | Center scope |
| `enrollment_id` | FK → enrollments | Related enrollment |
| `certificate_template_id` | FK → certificate_templates | Template used |
| `certificate_number` | varchar | Unique certificate number (e.g., "NAJ-2026-00001") |
| `student_name` | varchar | Name as appears on certificate |
| `course_title` | varchar | Course title as appears on certificate |
| `issue_type` | tinyint | 0=auto, 1=manual |
| `issued_at` | timestamp | Issue date |
| `issued_by` | FK → users | Admin who issued (null for auto) |
| `expires_at` | timestamp | Optional expiration |
| `revoked_at` | timestamp | If revoked |
| `revoked_by` | FK → users | Admin who revoked |
| `revoke_reason` | text | Reason for revocation |
| `pdf_url` | varchar | Generated PDF URL |
| `metadata` | JSON | Additional data (completion date, grade, etc.) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [uuid]`
- `UNIQUE [certificate_number]`
- `[user_id, course_id]`
- `[center_id]`
- `[issued_at]`

### badge_definitions

Badge types that can be earned.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Null for system badges, FK for center badges |
| `slug` | varchar | Unique identifier (e.g., "first_course", "streak_7") |
| `name_translations` | JSON | Badge name |
| `description_translations` | JSON | How to earn this badge |
| `icon_url` | varchar | Badge icon image |
| `category` | tinyint | 0=milestone, 1=streak, 2=achievement, 3=special |
| `trigger_type` | tinyint | 0=auto, 1=manual |
| `trigger_config` | JSON | Auto-trigger configuration |
| `points_reward` | int | Points awarded when earned |
| `is_active` | boolean | Badge is earnable |
| `is_hidden` | boolean | Hidden until earned |
| `order` | int | Display order |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [center_id, slug]`
- `[center_id, category, is_active]`

**Trigger Config Examples:**
```json
// First course completed
{"type": "courses_completed", "count": 1}

// 5 courses completed
{"type": "courses_completed", "count": 5}

// 7-day streak
{"type": "daily_streak", "days": 7}

// Watch 100 videos
{"type": "videos_watched", "count": 100}

// Complete course in 7 days
{"type": "fast_completion", "days": 7}
```

### student_badges

Badges earned by students.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Student |
| `badge_definition_id` | FK → badge_definitions | Badge earned |
| `center_id` | FK → centers | Center context |
| `earned_at` | timestamp | When badge was earned |
| `earned_reason` | varchar | Specific trigger (e.g., "Completed Course: Laravel Basics") |
| `points_awarded` | int | Points given for this badge |
| `granted_by` | FK → users | Admin (for manual grants) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `UNIQUE [user_id, badge_definition_id, center_id]` (one badge per type per center)
- `[user_id, earned_at]`
- `[center_id]`

### student_points

Points transactions log.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Student |
| `center_id` | FK → centers | Center scope |
| `points` | int | Points earned (positive) or spent (negative) |
| `balance_after` | int | Running balance after transaction |
| `source_type` | varchar | Polymorphic: "badge", "video", "course", "manual" |
| `source_id` | bigint | Related entity ID |
| `description` | varchar | Human-readable description |
| `created_at` | timestamp | |

**Indexes:**
- `[user_id, center_id]`
- `[center_id, created_at]`
- `[source_type, source_id]`

### student_levels

Student level progression.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Student |
| `center_id` | FK → centers | Center scope (levels are per-center) |
| `current_level` | int | Current level number |
| `total_points` | int | Lifetime points earned |
| `current_streak` | int | Current daily activity streak |
| `longest_streak` | int | Best streak achieved |
| `last_activity_date` | date | Last activity for streak tracking |
| `courses_completed` | int | Total courses completed |
| `videos_watched` | int | Total videos watched (full plays) |
| `certificates_earned` | int | Total certificates |
| `badges_earned` | int | Total badges |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `UNIQUE [user_id, center_id]`
- `[center_id, total_points]` (for leaderboard)
- `[center_id, current_level]`

### level_definitions

Level thresholds configuration.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Null for system defaults, FK for center override |
| `level` | int | Level number (1, 2, 3...) |
| `name_translations` | JSON | Level name (e.g., "Beginner", "Intermediate") |
| `points_required` | int | Minimum points for this level |
| `icon_url` | varchar | Level badge/icon |
| `color` | varchar | Level color for UI |
| `perks` | JSON | Optional perks at this level |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `UNIQUE [center_id, level]`
- `[center_id, points_required]`

---

## Enums

### CertificateIssueType

```php
enum CertificateIssueType: int
{
    case Auto = 0;
    case Manual = 1;
}
```

### BadgeCategory

```php
enum BadgeCategory: int
{
    case Milestone = 0;   // Course completions, video counts
    case Streak = 1;      // Daily activity streaks
    case Achievement = 2; // Special accomplishments
    case Special = 3;     // Admin-granted, events
}
```

### BadgeTriggerType

```php
enum BadgeTriggerType: int
{
    case Auto = 0;    // System triggers automatically
    case Manual = 1;  // Admin grants manually
}
```

### PointSourceType

```php
enum PointSourceType: string
{
    case Badge = 'badge';
    case Video = 'video';
    case Course = 'course';
    case Certificate = 'certificate';
    case Streak = 'streak';
    case Manual = 'manual';
}
```

---

## Point Values Configuration

Default point values (configurable per center):

| Activity | Points |
|----------|--------|
| Watch video (full play) | 10 |
| Complete course | 100 |
| Earn certificate | 50 |
| Daily login streak (per day) | 5 |
| First course completed | 50 (badge bonus) |
| 5 courses completed | 100 (badge bonus) |
| 7-day streak | 50 (badge bonus) |
| 30-day streak | 200 (badge bonus) |

---

## Default Level Thresholds

| Level | Name | Points Required |
|-------|------|-----------------|
| 1 | Newcomer | 0 |
| 2 | Beginner | 100 |
| 3 | Learner | 500 |
| 4 | Achiever | 1,500 |
| 5 | Expert | 5,000 |
| 6 | Master | 15,000 |
| 7 | Legend | 50,000 |

---

## Default System Badges

| Slug | Name | Trigger | Points |
|------|------|---------|--------|
| `first_video` | First Steps | Watch 1 video | 10 |
| `first_course` | Course Conqueror | Complete 1 course | 50 |
| `five_courses` | Learning Machine | Complete 5 courses | 100 |
| `ten_courses` | Knowledge Seeker | Complete 10 courses | 200 |
| `streak_7` | Week Warrior | 7-day streak | 50 |
| `streak_30` | Monthly Master | 30-day streak | 200 |
| `streak_100` | Centurion | 100-day streak | 500 |
| `early_bird` | Early Bird | Complete course in 7 days | 75 |
| `night_owl` | Night Owl | Study after midnight | 25 |
| `perfectionist` | Perfectionist | 100% on all course videos | 100 |

---

## Service Layer

### CourseProgressService

```php
interface CourseProgressServiceInterface
{
    // Update progress when video is watched
    public function updateVideoProgress(User $student, Video $video, Course $course, int $percent): void;

    // Recalculate course progress
    public function recalculate(Enrollment $enrollment): CourseProgress;

    // Check if course is completed
    public function isCompleted(Enrollment $enrollment): bool;

    // Get progress for enrollment
    public function getProgress(Enrollment $enrollment): ?CourseProgress;

    // Get all course progress for student
    public function getStudentProgress(User $student, ?Center $center = null): Collection;
}
```

### CertificateService

```php
interface CertificateServiceInterface
{
    // Auto-issue certificate on completion
    public function autoIssue(Enrollment $enrollment): ?StudentCertificate;

    // Manual issue by admin
    public function manualIssue(User $admin, User $student, Course $course): StudentCertificate;

    // Revoke certificate
    public function revoke(User $admin, StudentCertificate $certificate, string $reason): StudentCertificate;

    // Generate PDF
    public function generatePdf(StudentCertificate $certificate): string;

    // Verify certificate by UUID
    public function verify(string $uuid): ?StudentCertificate;

    // Get student certificates
    public function getStudentCertificates(User $student, ?Center $center = null): Collection;
}
```

### CertificateTemplateService

```php
interface CertificateTemplateServiceInterface
{
    // CRUD for templates
    public function create(Course $course, array $data): CertificateTemplate;
    public function update(CertificateTemplate $template, array $data): CertificateTemplate;
    public function delete(CertificateTemplate $template): void;

    // Get template for course
    public function getForCourse(Course $course): ?CertificateTemplate;
}
```

### BadgeService

```php
interface BadgeServiceInterface
{
    // Check and award badges after an activity
    public function checkAndAward(User $student, Center $center, string $activityType, array $context = []): array;

    // Manual grant by admin
    public function grant(User $admin, User $student, BadgeDefinition $badge): StudentBadge;

    // Get student badges
    public function getStudentBadges(User $student, ?Center $center = null): Collection;

    // Get available badges (earned + unearned)
    public function getAvailableBadges(User $student, Center $center): Collection;
}
```

### PointsService

```php
interface PointsServiceInterface
{
    // Award points
    public function award(User $student, Center $center, int $points, string $sourceType, ?int $sourceId, string $description): StudentPoints;

    // Deduct points (for redemption features)
    public function deduct(User $student, Center $center, int $points, string $reason): StudentPoints;

    // Get balance
    public function getBalance(User $student, Center $center): int;

    // Get transaction history
    public function getHistory(User $student, Center $center, ?int $limit = null): Collection;
}
```

### LevelService

```php
interface LevelServiceInterface
{
    // Update level based on points
    public function updateLevel(User $student, Center $center): StudentLevel;

    // Get current level info
    public function getLevel(User $student, Center $center): StudentLevel;

    // Update streak
    public function updateStreak(User $student, Center $center): void;

    // Get level definitions for center
    public function getLevelDefinitions(Center $center): Collection;
}
```

### LeaderboardService

```php
interface LeaderboardServiceInterface
{
    // Get leaderboard for center
    public function getLeaderboard(Center $center, int $limit = 100, ?string $period = null): Collection;

    // Get student rank
    public function getStudentRank(User $student, Center $center): int;

    // Get students around a rank
    public function getAroundRank(User $student, Center $center, int $range = 5): Collection;
}
```

---

## API Endpoints

### Admin - Certificate Templates

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/courses/{course}/certificate-template` | Get template |
| POST | `/api/v1/admin/centers/{center}/courses/{course}/certificate-template` | Create template |
| PUT | `/api/v1/admin/centers/{center}/courses/{course}/certificate-template` | Update template |
| DELETE | `/api/v1/admin/centers/{center}/courses/{course}/certificate-template` | Delete template |
| POST | `/api/v1/admin/centers/{center}/courses/{course}/certificate-template/preview` | Preview PDF |

### Admin - Certificates Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/certificates` | List issued certificates |
| POST | `/api/v1/admin/centers/{center}/students/{student}/certificates` | Manual issue |
| GET | `/api/v1/admin/centers/{center}/certificates/{certificate}` | Get certificate details |
| POST | `/api/v1/admin/centers/{center}/certificates/{certificate}/revoke` | Revoke certificate |
| GET | `/api/v1/admin/centers/{center}/certificates/{certificate}/download` | Download PDF |

**List Filters:**
- `course_id`, `user_id`, `issue_type`, `issued_from`, `issued_to`, `search`, `page`, `per_page`

### Admin - Badge Definitions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/badges` | List badge definitions |
| POST | `/api/v1/admin/centers/{center}/badges` | Create custom badge |
| PUT | `/api/v1/admin/centers/{center}/badges/{badge}` | Update badge |
| DELETE | `/api/v1/admin/centers/{center}/badges/{badge}` | Delete badge |
| POST | `/api/v1/admin/centers/{center}/students/{student}/badges` | Grant badge manually |

### Admin - Level Definitions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/levels` | List level definitions |
| PUT | `/api/v1/admin/centers/{center}/levels` | Update level thresholds |

### Admin - Leaderboard & Analytics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/leaderboard` | Get center leaderboard |
| GET | `/api/v1/admin/centers/{center}/students/{student}/gamification` | Student gamification profile |

### Mobile - Certificates

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/certificates` | My certificates (all centers) |
| GET | `/api/v1/centers/{center}/certificates` | My certificates (center) |
| GET | `/api/v1/certificates/{uuid}` | View certificate |
| GET | `/api/v1/certificates/{uuid}/download` | Download PDF |
| GET | `/api/v1/certificates/{uuid}/verify` | Verify certificate (public) |

### Mobile - Achievements

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/achievements` | My achievements (badges, level, points) |
| GET | `/api/v1/centers/{center}/badges` | Available badges with earn status |
| GET | `/api/v1/centers/{center}/leaderboard` | Center leaderboard |
| GET | `/api/v1/centers/{center}/leaderboard/me` | My rank + nearby students |

### Mobile - Progress

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/courses/{course}/progress` | Course progress details |
| GET | `/api/v1/centers/{center}/progress` | All course progress summary |

### Public - Certificate Verification

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/certificates/verify/{uuid}` | Public verification (no auth) |

---

## Events & Listeners

### Events

| Event | Trigger | Data |
|-------|---------|------|
| `VideoCompleted` | Video reaches 80% | user, video, course, enrollment |
| `CourseCompleted` | All videos completed | user, course, enrollment |
| `CertificateIssued` | Certificate created | certificate |
| `BadgeEarned` | Badge awarded | student_badge |
| `LevelUp` | Student levels up | student_level, old_level, new_level |
| `StreakUpdated` | Daily streak changes | student_level, streak_count |

### Listeners

| Listener | Handles | Actions |
|----------|---------|---------|
| `UpdateCourseProgress` | `VideoCompleted` | Recalculate progress, check completion |
| `AutoIssueCertificate` | `CourseCompleted` | Issue certificate if auto_issue enabled |
| `CheckBadges` | `VideoCompleted`, `CourseCompleted`, `CertificateIssued` | Check and award applicable badges |
| `AwardPoints` | `VideoCompleted`, `CourseCompleted`, `BadgeEarned` | Award points for activities |
| `UpdateLevel` | Points awarded | Recalculate level |
| `UpdateStreak` | Any activity | Update daily streak |

---

## Implementation Checklist

### Phase 1: Database Architecture (7 migrations)
- [ ] Create `course_progress` table
- [ ] Create `certificate_templates` table
- [ ] Create `student_certificates` table
- [ ] Create `badge_definitions` table
- [ ] Create `student_badges` table
- [ ] Create `student_points` table
- [ ] Create `student_levels` table
- [ ] Create `level_definitions` table
- [ ] Seed default badges and levels

### Phase 2: Enums & Models (8 models)
- [ ] Create `CertificateIssueType` enum
- [ ] Create `BadgeCategory` enum
- [ ] Create `BadgeTriggerType` enum
- [ ] Create `PointSourceType` enum
- [ ] Create `CourseProgress` model
- [ ] Create `CertificateTemplate` model
- [ ] Create `StudentCertificate` model
- [ ] Create `BadgeDefinition` model
- [ ] Create `StudentBadge` model
- [ ] Create `StudentPoints` model
- [ ] Create `StudentLevel` model
- [ ] Create `LevelDefinition` model

### Phase 3: Service Layer (7 services)
- [ ] Create `CourseProgressService`
- [ ] Create `CertificateService`
- [ ] Create `CertificateTemplateService`
- [ ] Create `BadgeService`
- [ ] Create `PointsService`
- [ ] Create `LevelService`
- [ ] Create `LeaderboardService`
- [ ] Create PDF generation service (using DOMPDF or similar)

### Phase 4: Events & Listeners
- [ ] Create `VideoCompleted` event
- [ ] Create `CourseCompleted` event
- [ ] Create `CertificateIssued` event
- [ ] Create `BadgeEarned` event
- [ ] Create `LevelUp` event
- [ ] Create listeners for each event
- [ ] Register event-listener mappings

### Phase 5: Admin API
- [ ] Certificate template CRUD controller
- [ ] Certificate management controller
- [ ] Badge definition CRUD controller
- [ ] Level definition controller
- [ ] Leaderboard controller
- [ ] Form requests for all endpoints
- [ ] Admin resources
- [ ] Routes registration

### Phase 6: Mobile API
- [ ] Certificates controller
- [ ] Achievements controller
- [ ] Progress controller
- [ ] Leaderboard controller
- [ ] Public verification controller
- [ ] Mobile resources
- [ ] Routes registration

### Phase 7: Integration
- [ ] Hook into PlaybackService for video completion detection
- [ ] Create scheduled job for streak resets (daily)
- [ ] Add gamification data to student profile responses

### Phase 8: Quality & Testing
- [ ] Create factories for all models
- [ ] Feature tests for certificate flow
- [ ] Feature tests for badge earning
- [ ] Feature tests for points and levels
- [ ] Feature tests for leaderboard
- [ ] Unit tests for services
- [ ] Run quality checks

---

## File Summary (~75 files)

```
Migrations (8):
- create_course_progress_table.php
- create_certificate_templates_table.php
- create_student_certificates_table.php
- create_badge_definitions_table.php
- create_student_badges_table.php
- create_student_points_table.php
- create_student_levels_table.php
- create_level_definitions_table.php

Enums (4):
- CertificateIssueType.php
- BadgeCategory.php
- BadgeTriggerType.php
- PointSourceType.php

Models (8):
- CourseProgress.php
- CertificateTemplate.php
- StudentCertificate.php
- BadgeDefinition.php
- StudentBadge.php
- StudentPoints.php
- StudentLevel.php
- LevelDefinition.php

Services (14):
- CourseProgressServiceInterface.php + CourseProgressService.php
- CertificateServiceInterface.php + CertificateService.php
- CertificateTemplateServiceInterface.php + CertificateTemplateService.php
- BadgeServiceInterface.php + BadgeService.php
- PointsServiceInterface.php + PointsService.php
- LevelServiceInterface.php + LevelService.php
- LeaderboardServiceInterface.php + LeaderboardService.php

Events (5):
- VideoCompleted.php
- CourseCompleted.php
- CertificateIssued.php
- BadgeEarned.php
- LevelUp.php

Listeners (6):
- UpdateCourseProgress.php
- AutoIssueCertificate.php
- CheckBadges.php
- AwardPoints.php
- UpdateLevel.php
- UpdateStreak.php

Controllers (10):
- Admin/CertificateTemplateController.php
- Admin/CertificateController.php
- Admin/BadgeDefinitionController.php
- Admin/LevelDefinitionController.php
- Admin/LeaderboardController.php
- Mobile/CertificateController.php
- Mobile/AchievementController.php
- Mobile/ProgressController.php
- Mobile/LeaderboardController.php
- Public/CertificateVerificationController.php

Form Requests (~15)
Resources (~12)
Routes (3):
- admin/gamification.php
- mobile/gamification.php
- public/certificates.php

Factories (8)
Tests (~12)
Seeders (2):
- DefaultBadgesSeeder.php
- DefaultLevelsSeeder.php
```

---

## Testing Plan

```bash
# Run all gamification tests
php artisan test --filter="Certificate"
php artisan test --filter="Badge"
php artisan test --filter="Points"
php artisan test --filter="Level"
php artisan test --filter="Leaderboard"
php artisan test --filter="Progress"
```

### Key Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Video completion triggers progress update | Feature | High |
| Course completion triggers certificate | Feature | High |
| Certificate auto-issue when enabled | Feature | High |
| Badge earned on milestone | Feature | High |
| Points awarded for activities | Feature | High |
| Level up when points threshold reached | Feature | High |
| Leaderboard ranking accuracy | Feature | High |
| Certificate PDF generation | Feature | High |
| Certificate verification (public) | Feature | High |
| Streak resets after missed day | Feature | Medium |
| Manual badge grant by admin | Feature | Medium |
| Certificate revocation | Feature | Medium |
| Duplicate badge prevention | Unit | Medium |
