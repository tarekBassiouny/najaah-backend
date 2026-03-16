# Assignments & Quizzes Quick Reference (Frontend + Mobile)

This is the short integration checklist. For full details use:
- `/docs/features/assignments_quizzes_api_contract.md`

## Headers
- `Authorization: Bearer <token>`
- `X-Locale: en|ar` (recommended)

## Admin Auth/Scope
- Base: `/api/v1/admin`
- Quiz module: `quiz.manage` + `scope.center`
- Assignment module: `assignment.manage` + `scope.center`
- AI content create/list/show/discard: `ai_content.generate` + `scope.center`
- AI content review/approve/publish: `ai_content.review_publish` + `scope.center`
- Course Builder asset catalog: `course.manage` + `scope.center`
- Learning asset admin: `learning_asset.manage` + `scope.center`

## Mobile Auth/Scope
- Base: `/api/v1`
- Student JWT required

## Enums
- `attempt_score_policy`: `0` best, `1` latest, `2` average
- `question_type`: `0` single, `1` multiple
- `quiz_attempt.status`: `0` in_progress, `1` submitted, `2` timed_out, `3` graded
- `submission_type`: `0` file, `1` text, `2` link
- `submission.status`: `0` draft, `1` submitted, `2` graded, `3` returned

---

## Admin Quizzes

## List
- `GET /centers/{center}/courses/{course}/quizzes`
- Query: `active_only?=true|false`

## Create
- `POST /centers/{center}/courses/{course}/quizzes`
- Required body:
  - `title_translations.en`
- Common optional:
  - `description_translations`, `attachable_type`, `attachable_id`
  - `passing_score`, `max_attempts`, `attempt_score_policy`
  - `time_limit_minutes`, `is_active`, `is_required`

## Show / Update / Delete / Duplicate
- `GET /centers/{center}/quizzes/{quiz}`
- `PUT /centers/{center}/quizzes/{quiz}`
- `DELETE /centers/{center}/quizzes/{quiz}`
- `POST /centers/{center}/quizzes/{quiz}/duplicate`

## Questions
- List: `GET /centers/{center}/quizzes/{quiz}/questions`
- Create: `POST /centers/{center}/quizzes/{quiz}/questions`
  - Required: `question_translations.en`, `answers[]` (2..10)
- Update: `PUT /centers/{center}/quizzes/{quiz}/questions/{question}`
- Delete: `DELETE /centers/{center}/quizzes/{quiz}/questions/{question}`
- Reorder: `PUT /centers/{center}/quizzes/{quiz}/questions/reorder`
  - Required: `question_ids: number[]`

Notes:
- Supported quiz question types are `single_choice` and `multiple_choice` only.
- Do not design short-answer quiz authoring against this API.

## AI Content Jobs
- Create draft job: `POST /centers/{center}/ai-content/jobs`
  - Required: `course_id`, `source_type`, `source_id`, `target_type`
  - Optional: `target_id`, `generation_config`
- Create batch: `POST /centers/{center}/ai-content/batches`
  - Required: `course_id`, `source_type=video|pdf`, `source_id`, `assets[]`
  - `assets[].target_type`: `summary|quiz|flashcards|assignment`
  - `assets[].target_id`: optional regenerate target for same source
  - `assets[].generation_config`: target-specific options
- List jobs: `GET /centers/{center}/ai-content/jobs`
  - Query: `course_id`, `batch_key`, `status`, `target_type`
- Get job: `GET /centers/{center}/ai-content/jobs/{job}`
- Review payload: `PATCH /centers/{center}/ai-content/jobs/{job}/review`
  - Required: `reviewed_payload`
- Approve: `POST /centers/{center}/ai-content/jobs/{job}/approve`
- Publish: `POST /centers/{center}/ai-content/jobs/{job}/publish`
- Discard: `DELETE /centers/{center}/ai-content/jobs/{job}`

Notes:
- One job still generates one asset type. Batch create just creates multiple jobs with one `batch_key`.
- `target_id` supports regenerate/update-existing flows.
- If `target_id` is used from Course Builder, it must belong to the same source item.
- Publishing to an existing live quiz or assignment now creates a new inactive version path and swaps on publish.
- Publishing currently makes canonical content live immediately.

## Course Builder Assets
- Asset catalog: `GET /centers/{center}/courses/{course}/asset-catalog`
- Query: `section_id`, `source_type=video|pdf`, `source_id`
- Returns:
  - `course`
  - flat `sources[]`
  - each source has `section` snapshot + `assets[]`
- Each asset slot has:
  - `asset_type`
  - `slot_state`
  - `canonical`
  - `latest_job`

Slot states:
- `missing`
- `draft`
- `generating`
- `review_required`
- `approved`
- `published`
- `failed`

Frontend note:
- use `GET /ai-content/jobs?batch_key=...` for polling
- refetch `asset-catalog` after publish or manual create

## Admin Learning Assets
- List: `GET /centers/{center}/courses/{course}/learning-assets`
- Show: `GET /centers/{center}/learning-assets/{asset}`
- Update: `PUT /centers/{center}/learning-assets/{asset}`
- Status: `PATCH /centers/{center}/learning-assets/{asset}/status`

Notes:
- use these for generated `summary` and `flashcards`
- no delete endpoint in v1
- archive by status instead of delete

## Analytics
- `GET /centers/{center}/quizzes/{quiz}/attempts`
- `GET /centers/{center}/quizzes/{quiz}/analytics`
- `GET /centers/{center}/quizzes/{quiz}/attempts/{attempt}`

---

## Admin Assignments

## List / Create
- `GET /centers/{center}/courses/{course}/assignments`
- `POST /centers/{center}/courses/{course}/assignments`
  - Required: `title_translations.en`
  - If `is_group_assignment=true` => `max_group_size` required

## Show / Update / Delete
- `GET /centers/{center}/assignments/{assignment}`
- `PUT /centers/{center}/assignments/{assignment}`
- `DELETE /centers/{center}/assignments/{assignment}`

## Submissions
- List: `GET /centers/{center}/assignments/{assignment}/submissions`
- Stats: `GET /centers/{center}/assignments/{assignment}/statistics`
- Detail: `GET /centers/{center}/submissions/{submission}`
- Grade: `POST /centers/{center}/submissions/{submission}/grade`
  - Required: `score`
- Return: `POST /centers/{center}/submissions/{submission}/return`
  - Required: `feedback`
- Download file: `GET /centers/{center}/submissions/{submission}/files/{file}/download`

---

## Mobile Quizzes

## List / Show / History
- `GET /centers/{center}/courses/{course}/quizzes`
- `GET /centers/{center}/quizzes/{quiz}`
- `GET /centers/{center}/quizzes/{quiz}/my-attempts`

## Attempt lifecycle
- Start/resume: `POST /centers/{center}/quizzes/{quiz}/start`
- Attempt details: `GET /centers/{center}/quiz-attempts/{attempt}`
- Save answer: `POST /centers/{center}/quiz-attempts/{attempt}/answer`
  - Required: `question_id`, `answer_ids[]`
- Submit: `POST /centers/{center}/quiz-attempts/{attempt}/submit`
- Results: `GET /centers/{center}/quiz-attempts/{attempt}/results`

---

## Mobile Assignments

## List / Show / My submission
- `GET /centers/{center}/courses/{course}/assignments`
- `GET /centers/{center}/assignments/{assignment}`
- `GET /centers/{center}/assignments/{assignment}/my-submission`

## Submission lifecycle
- Create/update draft: `POST /centers/{center}/assignments/{assignment}/submissions`
- Upload file: `POST /centers/{center}/submissions/{submission}/files` (multipart)
- Remove file: `DELETE /centers/{center}/submissions/{submission}/files/{file}`
- Submit final: `POST /centers/{center}/submissions/{submission}/submit`

## Groups (group assignments only)
- `GET /centers/{center}/assignments/{assignment}/groups`
- `POST /centers/{center}/assignments/{assignment}/groups`
- `GET /centers/{center}/assignment-groups/{group}`
- `POST /centers/{center}/assignment-groups/{group}/join`
- `POST /centers/{center}/assignment-groups/{group}/leave`

---

## Weekly Activity (Mobile Dashboard)
- `GET /centers/{center}/activity/weekly?days=7`
- `days`: optional `1..30`
- Returns:
  - `series[].watch_duration_seconds`
  - `series[].quiz_attempts_count`
  - `series[].assignment_submissions_count`

---

## Error Codes To Handle In UI

Mobile common:
- `UNAUTHORIZED`
- `NOT_ENROLLED`
- `NOT_FOUND`
- `NOT_AVAILABLE`
- `NO_ATTEMPTS_LEFT`
- `ATTEMPT_CLOSED`
- `TIME_EXPIRED`
- `ALREADY_SUBMITTED`
- `EMPTY_SUBMISSION`
- `CANNOT_SUBMIT`
- `FILE_LIMIT_REACHED`
- `INVALID_FILE_TYPE`
- `FILE_TOO_LARGE`
- `NOT_GROUP_ASSIGNMENT`
- `ALREADY_IN_GROUP`
- `GROUP_FULL`
- `NOT_IN_GROUP`

Admin common:
- `VALIDATION_ERROR` (422)
- `INVALID_STATE` (invalid lifecycle transition, like approve before completion)
- `SUBMISSION_NOT_GRADABLE`
- `FILE_NOT_FOUND`
