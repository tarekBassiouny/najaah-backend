# Assignments & Quizzes API Contract (Frontend + Mobile)

## Purpose
This document is the implementation contract for the existing Assignments and Quizzes backend feature set.

It is written for:
- Admin frontend (dashboard) integration
- Mobile app integration

It includes:
- Exact endpoints
- Auth/scope expectations
- Request payloads and validation rules
- Response shapes (what UI can safely rely on)
- Business-rule errors returned by backend

---

## Base URLs and Auth

### Admin APIs
- Base prefix: `/api/v1/admin`
- Auth: `Authorization: Bearer <admin_jwt>`
- Required middleware for this module:
  - Quizzes: `require.permission:quiz.manage` + `scope.center`
  - Assignments: `require.permission:assignment.manage` + `scope.center`
  - AI content create/list/show/discard: `require.permission:ai_content.generate` + `scope.center`
  - AI content review/approve/publish: `require.permission:ai_content.review_publish` + `scope.center`
  - `learning_asset.manage` exists as a permission, but admin CRUD endpoints for `learning_assets` are not part of this contract yet.

### Mobile APIs
- Base prefix: `/api/v1`
- Auth: `Authorization: Bearer <mobile_jwt>`
- Student-only for all endpoints in this document

### Locale and language behavior
- Optional headers:
  - `X-Locale: en|ar` (preferred)
  - `Accept-Language: en|ar`
- Translation fields are resolved server-side with fallback locale.

---

## Response Envelope

### Admin (normalized)
Admin responses are normalized by middleware.

Success shape:
```json
{
  "success": true,
  "message": "Request completed successfully",
  "data": {},
  "meta": {}
}
```

Error shape:
```json
{
  "success": false,
  "message": "Validation failed",
  "code": "VALIDATION_ERROR",
  "errors": {
    "field": ["..."]
  },
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "field": ["..."]
    }
  }
}
```

### Mobile
Mobile endpoints in this module already return `success` and mostly return `message` + `data`.
Validation and domain errors follow the same `error.code` + `error.message` style.

---

## Shared Enum Values

### `attempt_score_policy` (Quiz)
- `0` = Best Score
- `1` = Latest Score
- `2` = Average Score

### `question_type` (Quiz Question)
- `0` = Single Choice
- `1` = Multiple Choice

### `quiz_attempt.status`
- `0` = In Progress
- `1` = Submitted
- `2` = Timed Out
- `3` = Graded

### `submission_type` (Assignment + Submission)
- `0` = File Upload
- `1` = Text Response
- `2` = Link Submission

### `submission.status`
- `0` = Draft
- `1` = Submitted
- `2` = Graded
- `3` = Returned for Revision

### `attachable_type`
- `video`
- `pdf`
- `section`
- `course`

---

## Admin: Quiz Endpoints

## 1) List quizzes for course
- `GET /api/v1/admin/centers/{center}/courses/{course}/quizzes`
- Query:
  - `active_only` (optional, boolean)

Validation:
- `center` + `course` must match (course must belong to center)

Response `data[]` item (QuizResource):
- `id, course_id`
- `title, title_translations`
- `description`
- `attachable_type, attachable_id`
- `passing_score, max_attempts`
- `attempt_score_policy, attempt_score_policy_label`
- `time_limit_minutes`
- `shuffle_questions, shuffle_answers`
- `show_correct_answers, show_score_immediately`
- `is_required, is_active`
- `available_from, available_until`
- `order_index`
- `questions_count, attempts_count`
- `created_at, updated_at`

## 2) Create quiz
- `POST /api/v1/admin/centers/{center}/courses/{course}/quizzes`

Body validation:
- `title_translations` required array
- `title_translations.en` required string max 255
- `title_translations.ar` optional string max 255
- `description_translations` optional nullable array
- `description_translations.en|ar` optional string
- `attachable_type` optional nullable in `[video,pdf,section,course]`
- `attachable_id` required_with `attachable_type`, nullable integer
- `passing_score` optional numeric min 0 max 100
- `max_attempts` optional integer min 0 (`0` = unlimited)
- `attempt_score_policy` optional enum (0/1/2)
- `time_limit_minutes` optional nullable integer min 1
- `shuffle_questions` optional boolean
- `shuffle_answers` optional boolean
- `show_correct_answers` optional boolean
- `show_score_immediately` optional boolean
- `is_required` optional boolean
- `is_active` optional boolean
- `available_from` optional nullable date
- `available_until` optional nullable date `after_or_equal:available_from`
- `order_index` optional integer min 0

Response:
- `201`
- `data` is QuizDetailResource

## 3) Show quiz
- `GET /api/v1/admin/centers/{center}/quizzes/{quiz}`

Validation:
- Quiz must belong to center

Response `data` (QuizDetailResource):
- All config fields from create/update
- `has_unlimited_attempts`
- `has_time_limit`
- `is_available`
- `total_points`
- `questions_count, attempts_count`
- `questions[]` (if loaded)
- `creator`

## 4) Update quiz
- `PUT /api/v1/admin/centers/{center}/quizzes/{quiz}`
- Same shape as create, all fields optional (`sometimes`)

## 5) Delete quiz
- `DELETE /api/v1/admin/centers/{center}/quizzes/{quiz}`

## 6) Duplicate quiz
- `POST /api/v1/admin/centers/{center}/quizzes/{quiz}/duplicate`
- Copies quiz + questions + answers
- New copy is forced inactive (`is_active=false`)

---

## Admin: Quiz Questions

## 7) List questions
- `GET /api/v1/admin/centers/{center}/quizzes/{quiz}/questions`

Response `data[]` (QuizQuestionResource):
- `id, quiz_id`
- `question, question_translations`
- `question_type, question_type_label, allows_multiple_answers`
- `explanation, explanation_translations`
- `points, order_index, is_active`
- AI metadata: `ai_generated, ai_source_type, ai_source_id`
- `answers[]` (`id, answer, answer_translations, is_correct, order_index`)

## 8) Create question
- `POST /api/v1/admin/centers/{center}/quizzes/{quiz}/questions`

Body validation:
- `question_translations` required array
- `question_translations.en` required string
- `question_translations.ar` optional string
- `question_type` optional enum (0/1)
- `explanation_translations` optional nullable array
- `explanation_translations.en|ar` optional string
- `points` optional numeric min 0
- `order_index` optional integer min 0
- `is_active` optional boolean
- `answers` required array min 2 max 10
- `answers.*.answer_translations` required array
- `answers.*.answer_translations.en` required string
- `answers.*.answer_translations.ar` optional string
- `answers.*.is_correct` required boolean
- `answers.*.order_index` optional integer min 0

## 9) Update question
- `PUT /api/v1/admin/centers/{center}/quizzes/{quiz}/questions/{question}`

Body validation:
- Same fields as create, all optional
- If `answers` is sent:
  - must be full valid answers array (min 2 max 10)
  - backend deletes old answers and recreates from request

## 10) Delete question
- `DELETE /api/v1/admin/centers/{center}/quizzes/{quiz}/questions/{question}`

## 11) Reorder questions
- `PUT /api/v1/admin/centers/{center}/quizzes/{quiz}/questions/reorder`

Body validation:
- `question_ids` required array min 1
- `question_ids.*` required integer exists `quiz_questions.id`

Behavior:
- Order in request becomes `order_index` order

---

## Admin: AI Content Jobs

## 12) Create AI content job
- `POST /api/v1/admin/centers/{center}/ai-content/jobs`

Implementation notes:
- One job produces one target asset type.
- Multi-asset generation in admin must create multiple jobs with the current API.
- `target_id` is optional and is used for regenerate/update-existing flows.

Body validation:
- `course_id` required integer exists `courses.id`
- `source_type` required enum `video|pdf|section|course`
- `source_id` required integer
- `target_type` required enum `quiz|assignment|summary|flashcards|interactive_activity`
- `target_id` optional nullable integer
- `generation_config` optional object

Response:
- `202 Accepted`
- `data` = `AIContentJobResource`

## 13) List AI content jobs
- `GET /api/v1/admin/centers/{center}/ai-content/jobs`

Query validation:
- `course_id` optional integer exists `courses.id`
- `status` optional integer enum `0..6`
- `target_type` optional enum `quiz|assignment|summary|flashcards|interactive_activity`
- `page` optional integer min 1
- `per_page` optional integer min 1 max 100

## 14) Get AI content job
- `GET /api/v1/admin/centers/{center}/ai-content/jobs/{job}`

## 15) Review AI content job
- `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`

Body validation:
- `reviewed_payload` required object

## 16) Approve AI content job
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/approve`

Behavior:
- Job must be completed (or previously approved for re-approve after review update)
- Invalid transition returns `422` with `error.code=INVALID_STATE`

## 17) Publish AI content job
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/publish`

Behavior:
- Job must be approved
- Publishes into canonical target (`quiz`, `assignment`, or `learning_assets`)
- Publishing currently makes canonical content live immediately:
  - quiz => `is_active=true`
  - assignment => `is_active=true`
  - learning asset => `status=published` and `is_active=true`
- If `target_id` points to an existing quiz, generated questions are appended to that quiz. Existing questions are not replaced.

## 18) Discard AI content job
- `DELETE /api/v1/admin/centers/{center}/ai-content/jobs/{job}`

---

## Admin: Quiz Analytics

## 19) List attempts
- `GET /api/v1/admin/centers/{center}/quizzes/{quiz}/attempts`

Query validation:
- `status` optional enum QuizAttemptStatus
- `user_id` optional integer exists `users.id`
- `passed` optional boolean
- `page` optional integer min 1
- `per_page` optional integer min 1 max 100

Response:
- `data[]` = QuizAttemptResource
- `meta`: `page, per_page, total, last_page`

## 20) Quiz analytics summary
- `GET /api/v1/admin/centers/{center}/quizzes/{quiz}/analytics`

Response `data`:
- `quiz_id`
- `summary`:
  - `total_attempts`
  - `completed_attempts`
  - `passed_attempts`
  - `unique_students`
  - `pass_rate`
  - `average_score`
  - `average_time_seconds`
- `score_distribution` buckets: `0-20,21-40,41-60,61-80,81-100`
- `question_analytics[]`:
  - `question_id, question`
  - `total_answers, correct_answers, correct_rate`
  - `points`

## 21) Attempt detail
- `GET /api/v1/admin/centers/{center}/quizzes/{quiz}/attempts/{attempt}`

Response `data`:
- attempt summary + `questions[]` with selected/correct answers and explanation visibility rules

---

## Admin: Assignment Endpoints

## 20) List assignments for course
- `GET /api/v1/admin/centers/{center}/courses/{course}/assignments`
- Query:
  - `active_only` optional boolean

Response `data[]` (AssignmentResource):
- `id, course_id`
- `title, title_translations, description`
- `attachable_type, attachable_id`
- `submission_types`
- `max_points, passing_score`
- `is_group_assignment, max_group_size`
- `is_required, is_active`
- `due_date, is_past_due`
- `late_submission_allowed`
- `available_from, available_until`
- `order_index`
- `submissions_count`
- `created_at, updated_at`

## 21) Create assignment
- `POST /api/v1/admin/centers/{center}/courses/{course}/assignments`

Body validation:
- `title_translations` required array
- `title_translations.en` required string max 255
- `title_translations.ar` optional string max 255
- `description_translations` optional nullable array
- `description_translations.en|ar` optional string
- `attachable_type` optional nullable in `[video,pdf,section,course]`
- `attachable_id` required_with `attachable_type`, nullable integer
- `submission_types` optional array min 1
- `submission_types.*` enum SubmissionType (0/1/2)
- `allowed_file_types` optional nullable array
- `allowed_file_types.*` string max 20
- `max_file_size_mb` optional integer min 1 max 100
- `max_files` optional integer min 1 max 20
- `is_group_assignment` optional boolean
- `max_group_size` required if `is_group_assignment=true`, nullable integer min 2 max 20
- `max_points` optional numeric min 1
- `passing_score` optional numeric min 0 max 100
- `is_required` optional boolean
- `is_active` optional boolean
- `due_date` optional nullable date
- `late_submission_allowed` optional boolean
- `late_penalty_percent` optional numeric min 0 max 100
- `available_from` optional nullable date
- `available_until` optional nullable date `after_or_equal:available_from`
- `order_index` optional integer min 0

Response:
- `201`
- `data` = AssignmentDetailResource

## 22) Show assignment
- `GET /api/v1/admin/centers/{center}/assignments/{assignment}`

Response `data` (AssignmentDetailResource):
- All config fields
- `is_available`
- `submissions_count, pending_grading_count`
- `submissions[]` (if loaded)
- `creator`

## 23) Update assignment
- `PUT /api/v1/admin/centers/{center}/assignments/{assignment}`
- Same shape as create, all optional

## 24) Delete assignment
- `DELETE /api/v1/admin/centers/{center}/assignments/{assignment}`

---

## Admin: Assignment Submissions

## 25) List submissions for assignment
- `GET /api/v1/admin/centers/{center}/assignments/{assignment}/submissions`

Query validation:
- `status` optional enum SubmissionStatus
- `user_id` optional integer exists `users.id`
- `passed` optional boolean
- `is_late` optional boolean
- `page` optional integer min 1
- `per_page` optional integer min 1 max 100

Response:
- `data[]` = AssignmentSubmissionResource
- `meta`: `page, per_page, total, last_page`

## 26) Submission statistics
- `GET /api/v1/admin/centers/{center}/assignments/{assignment}/statistics`

Response `data`:
- `assignment_id`
- `total_submissions`
- `pending_grading`
- `graded`
- `passed`
- `late_submissions`
- `average_score`
- `pass_rate`

## 27) Submission details
- `GET /api/v1/admin/centers/{center}/submissions/{submission}`

Response `data` (AssignmentSubmissionDetailResource):
- assignment snapshot (`id, title, max_points, passing_score`)
- user snapshot (`id, name, email, phone`)
- group info (if group submission)
- submission payload (`submission_type, text_content, link_url`)
- workflow state (`status`, `submitted_at`, `is_late`, `days_late`)
- grade data (`score`, `score_after_penalty`, `passed`, `feedback`)
- files[]
- grader info
- timestamps

## 28) Grade submission
- `POST /api/v1/admin/centers/{center}/submissions/{submission}/grade`

Body validation:
- `score` required numeric min 0
- `feedback` optional nullable string max 5000

Business validation:
- Must be gradable in current state (`Submitted`)
- Else: `422`, `error.code=SUBMISSION_NOT_GRADABLE`

## 29) Return for revision
- `POST /api/v1/admin/centers/{center}/submissions/{submission}/return`

Body validation:
- `feedback` required string max 5000

Business validation:
- Only `Submitted` state can be returned
- Else: `422`, `error.code=INVALID_STATE`

## 30) Download submission file
- `GET /api/v1/admin/centers/{center}/submissions/{submission}/files/{file}/download`

Behavior:
- Returns streamed file response (not JSON) when file exists
- `404` JSON with `error.code=FILE_NOT_FOUND` if missing in storage

---

## Mobile: Quiz Endpoints

All endpoints below require:
- Authenticated student
- Center/course enrollment checks where applicable

## 31) List quizzes for a course
- `GET /api/v1/centers/{center}/courses/{course}/quizzes`

Business rules:
- student must be enrolled with active enrollment in this center+course

Response `data[]` (QuizListResource):
- `id, title, description`
- `attachable_type, attachable_id`
- `passing_score, max_attempts, time_limit_minutes`
- `is_required, is_available`
- `remaining_attempts` (null if unlimited)
- `best_score`
- `can_attempt`
- `questions_count`

Common errors:
- `403 UNAUTHORIZED`
- `403 NOT_ENROLLED`

## 32) Get quiz pre-start info
- `GET /api/v1/centers/{center}/quizzes/{quiz}`

Business rules:
- quiz must belong to center
- student must be actively enrolled
- quiz must be active and available now

Response `data` (QuizInfoResource):
- all key quiz settings
- `shuffle_questions, shuffle_answers`
- `show_correct_answers, show_score_immediately`
- availability window
- attempts summary and totals (`total_questions`, `total_points`)

Common errors:
- `404 NOT_FOUND`
- `403 NOT_ENROLLED`
- `400 NOT_AVAILABLE`

## 33) My attempts history + stats
- `GET /api/v1/centers/{center}/quizzes/{quiz}/my-attempts`

Response `data`:
- `attempts[]`:
  - `id, attempt_number`
  - `status, status_label`
  - `score, passed`
  - `started_at, submitted_at`
  - `time_spent_seconds`
  - `answered_questions, total_questions`
  - `can_resume` (true only for in-progress)
- `stats`:
  - `lowest_score`
  - `average_score`
  - `highest_score`
  - `opened_count`
  - `completed_count`
  - `failed_count`
- `best_score`
- `remaining_attempts`

---

## Mobile: Quiz Attempt Lifecycle

## 34) Start attempt (or resume existing)
- `POST /api/v1/centers/{center}/assets/quiz/{quiz}/attempts`
- Body: none

Behavior:
- If in-progress attempt exists, returns it with message `Resuming existing attempt`
- Else creates new attempt and returns `201`

Response `data` (QuizAttemptDetailResource):
- attempt meta (`id`, `attempt_number`, `status`, timer fields)
- `total_questions`, `answered_questions`
- `questions[]`:
  - `id, question, question_type, question_type_label`
  - `points`
  - `is_answered`
  - `selected_answer_ids`
  - `answers[]` (`id`, `answer`)

Common errors:
- `404 NOT_FOUND`
- `403 NOT_ENROLLED`
- `400 NOT_AVAILABLE`
- `400 NO_ATTEMPTS_LEFT`

## 35) Get current attempt
- `GET /api/v1/centers/{center}/quiz-attempts/{attempt}`

Rules:
- attempt must belong to current student and center

## 36) Save answer
- `POST /api/v1/centers/{center}/quiz-attempts/{attempt}/answer`

Body validation:
- `question_id` required integer exists `quiz_questions.id`
- `answer_ids` required array min 1
- `answer_ids.*` required integer exists `quiz_answers.id`

Business validation:
- attempt must be in progress
- time limit expiration auto-submits the attempt
- `question_id` must belong to the attempt quiz

Response:
- saved `question_id`, `answer_ids`, `remaining_time_seconds`

Common errors:
- `404 NOT_FOUND`
- `400 ATTEMPT_CLOSED`
- `400 TIME_EXPIRED`
- `400 INVALID_QUESTION`

## 37) Submit attempt
- `POST /api/v1/centers/{center}/quiz-attempts/{attempt}/submit`

Rules:
- only owner student and matching center
- only in-progress attempt can be submitted

Response:
- `QuizResultResource`

Common errors:
- `400 ALREADY_SUBMITTED`

## 38) Attempt results
- `GET /api/v1/centers/{center}/quiz-attempts/{attempt}/results`

Rules:
- only owner student and matching center
- attempt must not be in-progress

Response `data` (QuizResultResource):
- base fields:
  - `id, quiz_id, quiz_title`
  - `attempt_number, status, status_label`
  - `started_at, submitted_at, time_spent_seconds`
  - `score, points_earned, points_possible`
  - `passed, passing_score`
- visibility notes:
  - if `show_score_immediately=false`: `score/points_earned/passed` become `null`
  - if `show_correct_answers=true`: includes `questions[]` with selected + correct answer details

Common errors:
- `400 NOT_SUBMITTED`

---

## Mobile: Assignment Endpoints

## 39) List assignments for a course
- `GET /api/v1/centers/{center}/courses/{course}/assignments`

Rules:
- active enrollment in center+course required

Response `data[]` (AssignmentListResource):
- `id, title, description`
- `attachable_type, attachable_id`
- `submission_types`
- `is_group_assignment`
- `max_points, passing_score`
- `is_required`
- due and availability state (`due_date`, `is_past_due`, `late_submission_allowed`, `is_available`)
- student state:
  - `can_submit`
  - `submission_status`, `submission_status_label`
  - `score`, `passed`

## 40) Assignment details
- `GET /api/v1/centers/{center}/assignments/{assignment}`

Rules:
- assignment must belong to center
- student must be enrolled in the assignment course
- assignment must be active/available

Response `data` (AssignmentDetailResource):
- assignment config (types, file constraints, group settings, points)
- timing/late fields
- `can_submit`
- `my_submission` (nullable)

Common errors:
- `404 NOT_FOUND`
- `403 NOT_ENROLLED`
- `400 NOT_AVAILABLE`

## 41) My submission for assignment
- `GET /api/v1/centers/{center}/assignments/{assignment}/my-submission`

Response:
- `AssignmentSubmissionResource`

Common errors:
- `404 NO_SUBMISSION` (when student has no submission yet)

---

## Mobile: Assignment Submission Lifecycle

## 42) Create/update draft
- `POST /api/v1/centers/{center}/assets/assignment/{assignment}/submission`

Body validation:
- `submission_type` optional enum SubmissionType
- `text_content` optional nullable string max 50000
- `link_url` optional nullable URL max 2000

Business validation:
- assignment must belong to center
- student must be enrolled in assignment course
- `canSubmit` must be true for assignment
- if submission exists and status is not `Draft` or `Returned`: `ALREADY_SUBMITTED`

Behavior:
- creates draft if missing
- updates existing editable draft otherwise

Response:
- `201` for newly created draft, `200` for update
- `data` = AssignmentSubmissionResource

Common errors:
- `400 CANNOT_SUBMIT`
- `400 ALREADY_SUBMITTED`

## 43) Upload file to submission
- `POST /api/v1/centers/{center}/assets/assignment/submissions/{submission}/files`
- Content-Type: `multipart/form-data`

Body validation:
- `file` required file max 102400 KB

Business validation:
- submission must belong to student and center
- submission must be editable (`Draft` or `Returned`)
- max files not exceeded
- extension must be in assignment `allowed_file_types`
- file size must be <= assignment `max_file_size_mb`

Success response `data`:
- `id, file_name, file_size_kb, file_type`

Common errors:
- `400 CANNOT_MODIFY`
- `400 FILE_LIMIT_REACHED`
- `400 INVALID_FILE_TYPE`
- `400 FILE_TOO_LARGE`

## 44) Remove file
- `DELETE /api/v1/centers/{center}/assets/assignment/submissions/{submission}/files/{file}`

Rules:
- same ownership/editability checks as upload
- file must belong to submission

## 45) Submit assignment
- `POST /api/v1/centers/{center}/assets/assignment/submissions/{submission}/submit`

Business validation:
- ownership + center checks
- submission status must be `Draft` or `Returned`
- submission must contain at least one of:
  - `text_content`
  - `link_url`
  - uploaded file

Response:
- `data` = AssignmentSubmissionResource (submitted state)

Common errors:
- `400 ALREADY_SUBMITTED`
- `400 EMPTY_SUBMISSION`

---

## Mobile: Assignment Groups

## 46) List groups for assignment
- `GET /api/v1/centers/{center}/assets/assignment/{assignment}/groups`

Rules:
- assignment must belong to center
- assignment must be group assignment

Response `data`:
- `groups[]` (AssignmentGroupResource)
- `my_group_id` nullable
- `max_group_size`

## 47) Create group
- `POST /api/v1/centers/{center}/assets/assignment/{assignment}/groups`

Body validation:
- `name` required string min 2 max 100

Rules:
- assignment must be group assignment
- student must not already be in a group for this assignment

Response:
- `201`, `data` = AssignmentGroupResource

## 48) Group details
- `GET /api/v1/centers/{center}/assets/assignment/groups/{group}`

## 49) Join group
- `POST /api/v1/centers/{center}/assets/assignment/groups/{group}/join`

Rules:
- student must not already be in another group for same assignment
- group capacity must not be exceeded

## 50) Leave group
- `POST /api/v1/centers/{center}/assets/assignment/groups/{group}/leave`

Rules:
- student must be member of group

Note:
- On service-level failures, this endpoint currently returns generic `LEAVE_FAILED` 500.

---

## Mobile: Weekly Activity (quiz + assignment dashboard support)

## 51) Weekly activity series
- `GET /api/v1/centers/{center}/activity/weekly`

Query validation:
- `days` optional integer min 1 max 30 (default 7)

Behavior:
- uses resolved timezone from request context
- aggregates per-day:
  - watch duration seconds
  - quiz attempts count
  - assignment submissions count

Response:
```json
{
  "success": true,
  "data": {
    "range": {
      "days": 7,
      "timezone": "Africa/Cairo",
      "start_date": "2026-03-05",
      "end_date": "2026-03-11"
    },
    "series": [
      {
        "date": "2026-03-05",
        "watch_duration_seconds": 0,
        "quiz_attempts_count": 0,
        "assignment_submissions_count": 0
      }
    ],
    "totals": {
      "watch_duration_seconds": 0,
      "quiz_attempts_count": 0,
      "assignment_submissions_count": 0
    }
  }
}
```

---

## Frontend Integration Notes

## Admin UI notes
- For question update, send full `answers` list if editing answers.
- For list endpoints, rely on `meta` pagination object.
- For attempt/submission statuses, use numeric value for logic and `*_label` for display.

## Mobile UI notes
- Always gate quiz/assignment screens by enrollment and center context.
- Quiz start flow:
  - call `start`
  - if existing in-progress attempt, backend returns resumable payload directly
- For timed quizzes, handle `TIME_EXPIRED` by showing finalization state and navigating to results.
- For submissions, use draft pattern:
  - create/update draft first
  - then upload files
  - then submit final
- For group assignments, group membership is managed via dedicated group endpoints.

## Error handling recommendation
- Use `error.code` for programmatic handling.
- Use `message` for user-facing fallback text.
- Keep localized UI copy client-side, but backend messages are already locale-aware for `en/ar`.

---

## Quick Smoke Test Matrix

### Admin
1. Create quiz -> add questions -> list -> show -> update -> duplicate -> delete.
2. Generate AI questions from video/pdf -> poll job -> approve/discard.
3. Create assignment -> list submissions -> grade -> return for revision -> download file.

### Mobile
1. Student enrolled -> list quizzes/assignments -> show details.
2. Start quiz -> answer -> submit -> results + attempts stats.
3. Create submission draft -> upload/remove file -> submit.
4. Group assignment -> list/create/join/leave.
5. Weekly activity chart fetch with `days=7` and `days=30`.

---

## Postman Ready Examples

Use these variables in Postman environment:
- `baseUrl` (example: `http://api.najaah.local`)
- `adminToken`
- `mobileToken`
- `centerId`
- `courseId`
- `quizId`
- `assignmentId`
- `attemptId`
- `submissionId`

### 1) Admin: Create Quiz

`POST {{baseUrl}}/api/v1/admin/centers/{{centerId}}/courses/{{courseId}}/quizzes`

Headers:
- `Authorization: Bearer {{adminToken}}`
- `Content-Type: application/json`
- `X-Locale: en`

Body:
```json
{
  "title_translations": {
    "en": "Module 1 Quiz",
    "ar": "اختبار الوحدة 1"
  },
  "description_translations": {
    "en": "Please answer all questions",
    "ar": "يرجى الإجابة على كل الأسئلة"
  },
  "passing_score": 70,
  "max_attempts": 3,
  "attempt_score_policy": 0,
  "time_limit_minutes": 15,
  "shuffle_questions": true,
  "shuffle_answers": true,
  "show_correct_answers": true,
  "show_score_immediately": true,
  "is_required": true,
  "is_active": true,
  "order_index": 1
}
```

Success (201) sample:
```json
{
  "success": true,
  "message": "Quiz created successfully.",
  "data": {
    "id": 44,
    "center_id": 3,
    "course_id": 12,
    "title": "Module 1 Quiz",
    "title_translations": {
      "en": "Module 1 Quiz",
      "ar": "اختبار الوحدة 1"
    },
    "passing_score": 70,
    "max_attempts": 3,
    "attempt_score_policy": 0,
    "time_limit_minutes": 15,
    "is_active": true,
    "questions_count": 0
  }
}
```

### 2) Admin: Add Quiz Question

`POST {{baseUrl}}/api/v1/admin/centers/{{centerId}}/quizzes/{{quizId}}/questions`

Headers:
- `Authorization: Bearer {{adminToken}}`
- `Content-Type: application/json`

Body:
```json
{
  "question_translations": {
    "en": "What is 2 + 2?",
    "ar": "كم يساوي 2 + 2؟"
  },
  "question_type": 0,
  "points": 1,
  "answers": [
    {
      "answer_translations": {
        "en": "3",
        "ar": "3"
      },
      "is_correct": false,
      "order_index": 0
    },
    {
      "answer_translations": {
        "en": "4",
        "ar": "4"
      },
      "is_correct": true,
      "order_index": 1
    }
  ]
}
```

Validation error (422) sample:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "answers": [
      "The answers field must have at least 2 items."
    ]
  }
}
```

### 3) Mobile: Start Quiz Attempt (Resume Supported)

`POST {{baseUrl}}/api/v1/centers/{{centerId}}/quizzes/{{quizId}}/start`

Headers:
- `Authorization: Bearer {{mobileToken}}`
- `X-Locale: en`

Body: empty

Success sample:
```json
{
  "success": true,
  "message": "Quiz attempt started",
  "data": {
    "id": 200,
    "quiz_id": 44,
    "attempt_number": 1,
    "status": 0,
    "status_label": "In Progress",
    "remaining_time_seconds": 900,
    "total_questions": 10,
    "answered_questions": 0,
    "questions": []
  }
}
```

### 4) Mobile: Save Answer

`POST {{baseUrl}}/api/v1/centers/{{centerId}}/quiz-attempts/{{attemptId}}/answer`

Headers:
- `Authorization: Bearer {{mobileToken}}`
- `Content-Type: application/json`

Body:
```json
{
  "question_id": 501,
  "answer_ids": [1201]
}
```

Success sample:
```json
{
  "success": true,
  "message": "Answer saved",
  "data": {
    "question_id": 501,
    "answer_ids": [1201],
    "remaining_time_seconds": 843
  }
}
```

### 5) Mobile: Submit Quiz Attempt

`POST {{baseUrl}}/api/v1/centers/{{centerId}}/quiz-attempts/{{attemptId}}/submit`

Headers:
- `Authorization: Bearer {{mobileToken}}`

Body: empty

Success sample:
```json
{
  "success": true,
  "message": "Quiz submitted successfully",
  "data": {
    "id": 200,
    "quiz_id": 44,
    "attempt_number": 1,
    "status": 3,
    "status_label": "Graded",
    "score": 90,
    "points_earned": 9,
    "points_possible": 10,
    "passed": true,
    "passing_score": 70
  }
}
```

### 6) Mobile: Create/Update Assignment Draft

`POST {{baseUrl}}/api/v1/centers/{{centerId}}/assignments/{{assignmentId}}/submissions`

Headers:
- `Authorization: Bearer {{mobileToken}}`
- `Content-Type: application/json`

Body:
```json
{
  "submission_type": 1,
  "text_content": "My assignment answer goes here"
}
```

Success sample:
```json
{
  "success": true,
  "message": "Draft created",
  "data": {
    "id": 301,
    "assignment_id": 77,
    "submission_type": 1,
    "status": 0,
    "status_label": "Draft",
    "text_content": "My assignment answer goes here"
  }
}
```

### 7) Mobile: Upload Submission File (multipart/form-data)

`POST {{baseUrl}}/api/v1/centers/{{centerId}}/submissions/{{submissionId}}/files`

Headers:
- `Authorization: Bearer {{mobileToken}}`

Body (form-data):
- key: `file`
- type: File
- value: choose local file

Success sample:
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 901,
    "file_name": "solution.pdf",
    "file_size_kb": 532,
    "file_type": "application/pdf"
  }
}
```

### 8) Mobile: Submit Assignment

`POST {{baseUrl}}/api/v1/centers/{{centerId}}/submissions/{{submissionId}}/submit`

Headers:
- `Authorization: Bearer {{mobileToken}}`

Body: empty

Success sample:
```json
{
  "success": true,
  "message": "Assignment submitted successfully",
  "data": {
    "id": 301,
    "assignment_id": 77,
    "status": 1,
    "status_label": "Submitted",
    "submitted_at": "2026-03-11T10:00:00+00:00"
  }
}
```

### 9) Mobile: Weekly Activity

`GET {{baseUrl}}/api/v1/centers/{{centerId}}/activity/weekly?days=7`

Headers:
- `Authorization: Bearer {{mobileToken}}`

Success sample:
```json
{
  "success": true,
  "data": {
    "range": {
      "days": 7,
      "timezone": "Africa/Cairo",
      "start_date": "2026-03-05",
      "end_date": "2026-03-11"
    },
    "series": [
      {
        "date": "2026-03-11",
        "watch_duration_seconds": 420,
        "quiz_attempts_count": 1,
        "assignment_submissions_count": 1
      }
    ],
    "totals": {
      "watch_duration_seconds": 420,
      "quiz_attempts_count": 1,
      "assignment_submissions_count": 1
    }
  }
}
```

### 10) Common Business Errors for Mobile UI Handling

Not enrolled:
```json
{
  "success": false,
  "error": {
    "code": "NOT_ENROLLED",
    "message": "You are not enrolled in this course."
  }
}
```

No attempts left:
```json
{
  "success": false,
  "error": {
    "code": "NO_ATTEMPTS_LEFT",
    "message": "You have no remaining attempts for this quiz."
  }
}
```

Empty submission:
```json
{
  "success": false,
  "error": {
    "code": "EMPTY_SUBMISSION",
    "message": "Submission must have content (text, link, or files)."
  }
}
```
