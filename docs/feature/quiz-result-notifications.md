# Quiz Result Notifications to Parent Phone

## Objective

Allow admin to send quiz results to student's parent phone number via WhatsApp (Evolution API). Both single and bulk sending.

## Current State

- **Quiz system**: Complete — models, grading, analytics, admin endpoints all exist
- **Parent phone**: `users.parent_phone` field exists, exposed in admin resources
- **WhatsApp infra**: Evolution API client, bulk job/item pattern, retry/pause/cancel all exist
- **Gap**: No service, endpoint, or job to connect quiz results → WhatsApp delivery

## Architecture Decision

Reuse the existing Evolution API transport (same as video access codes). Do NOT use the OTP sender (Facebook Graph API) — that's for OTP only.

**Transport**: `EvolutionApiClient::sendText()` for plain text messages.

## Data Chain

```
QuizAttempt (score, passed, points_earned, points_possible, submitted_at)
    → User (parent_phone, country_code, name)
    → Quiz (title_translations, passing_score)
    → Center (center_id for scoping)
```

## Implementation Plan

### Phase 1: Service Layer

**New file**: `app/Services/Assessments/QuizResultNotificationService.php`
**New interface**: `app/Services/Assessments/Contracts/QuizResultNotificationServiceInterface.php`

Methods:
- `sendToParent(User $admin, QuizAttempt $attempt): void`
  - Validate attempt is Graded
  - Validate student has `parent_phone` + `country_code`
  - Build message from attempt data
  - Send via `EvolutionApiClient::sendText()`
  - Audit log
- `sendBulkToParents(User $admin, Quiz $quiz, array $attemptIds): array`
  - Filter to Graded attempts with parent_phone
  - Dispatch individual jobs with delay (reuse whatsapp_bulk_settings from system)
  - Return summary: {total, queued, skipped_no_phone}
- `buildResultMessage(QuizAttempt $attempt): string`
  - Template: "Quiz Result — {quiz_title}\nStudent: {student_name}\nScore: {score}%\nResult: {Passed/Failed}\nDate: {submitted_at}"
  - Use center locale for pass/fail label

### Phase 2: Job

**New file**: `app/Jobs/SendQuizResultToParentJob.php`

- Accepts `QuizAttempt $attemptId`
- Loads attempt with user, quiz relationships
- Calls `QuizResultNotificationService::sendToParent()`
- Retry: 2 attempts, 10s delay
- On failure: log error, don't block

### Phase 3: API

**New routes** (in `routes/api/v1/admin/quizzes.php`):
```
POST /centers/{center}/quizzes/{quiz}/attempts/{attempt}/notify-parent
POST /centers/{center}/quizzes/{quiz}/notify-parents  (bulk)
```

**Middleware**: `require.permission:quiz.manage`, `scope.center`

**New request**: `app/Http/Requests/Admin/Quiz/NotifyParentRequest.php` (bulk: `attempt_ids` array)

**Controller**: Add methods to existing `QuizAnalyticsController`:
- `notifyParent(Center, Quiz, QuizAttempt)` — single
- `notifyParents(NotifyParentRequest, Center, Quiz)` — bulk

**Responses**:
```json
// Single
{"success": true, "message": "Quiz result sent to parent"}

// Bulk
{"success": true, "data": {"total": 25, "queued": 20, "skipped_no_phone": 5}}
```

### Phase 4: Validation Rules

Before sending:
- Attempt must be `Graded` status
- Student must have non-empty `parent_phone`
- Student must have `country_code`
- Center must have `features.whatsapp_bulk` enabled (for bulk)
- `require_parent_phone` setting is NOT a prerequisite — admin can send to any student who has a phone, regardless of whether it's required

### Phase 5: Feature Flag Integration

Gate behind `features.whatsapp_bulk` — if disabled for a center, bulk notify is blocked. Single notify still works (uses Evolution API directly, not bulk queue).

---

## Files to Create

| File | Type |
|------|------|
| `app/Services/Assessments/QuizResultNotificationService.php` | Service |
| `app/Services/Assessments/Contracts/QuizResultNotificationServiceInterface.php` | Interface |
| `app/Jobs/SendQuizResultToParentJob.php` | Job |
| `app/Http/Requests/Admin/Quiz/NotifyParentRequest.php` | Request |

## Files to Modify

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/Assessments/QuizAnalyticsController.php` | Add `notifyParent()`, `notifyParents()` |
| `routes/api/v1/admin/quizzes.php` | Add notification routes |
| `app/Providers/AppServiceProvider.php` | Register interface binding |

## Risks

| Risk | Mitigation |
|------|------------|
| Student has no parent_phone | Skip with clear response, don't fail |
| Evolution API down | Job retries 2x, then fails gracefully |
| Bulk spam risk | Rate-limit via `whatsapp_bulk_settings` system config |
| Wrong phone format | Reuse existing phone normalization from `VideoApprovalCodeService` |
