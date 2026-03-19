# Quiz Mobile UX Handoff (Survey-like Flow)

## Scope
This handoff defines a **native mobile, survey-like quiz experience** using the existing backend APIs.

It covers:
- Screen architecture
- Component structure
- State machine and transitions
- API call order and payloads
- Error and edge-case handling
- QA checklist for frontend/mobile teams

Related backend contract:
- `/docs/features/assignments_quizzes_api_contract.md`
- `/docs/features/contracts/assignments_quizzes.types.ts`

---

## Product Direction
Build quizzes exactly like surveys UX-wise:
- one question at a time
- explicit progress
- saved answer indicator
- next/previous navigation
- resumable attempt
- submit confirmation

Do **not** use WebView for quiz attempts.

---

## APIs Used

## 1) Quiz entry and metadata
- `GET /api/v1/centers/{center}/quizzes/{quiz}`

## 2) Start/resume attempt
- `POST /api/v1/centers/{center}/assets/quiz/{quiz}/attempts`

## 3) Save answer (autosave + manual next)
- `POST /api/v1/centers/{center}/quiz-attempts/{attempt}/answer`
- Body:
```json
{
  "question_id": 123,
  "answer_ids": [10]
}
```

## 4) Submit
- `POST /api/v1/centers/{center}/quiz-attempts/{attempt}/submit`

## 5) Results
- `GET /api/v1/centers/{center}/quiz-attempts/{attempt}/results`

## 6) History and resume support
- `GET /api/v1/centers/{center}/quizzes/{quiz}/my-attempts`

---

## Screen Map

## A) `QuizIntroScreen`
Purpose:
- show quiz rules and constraints before starting

Data source:
- `GET /quizzes/{quiz}`

Show:
- title, description
- passing score
- attempts left
- time limit (or “No time limit”)
- question count
- availability note

Primary CTA:
- `Start Quiz`

Secondary CTA:
- `View Attempts`

---

## B) `QuizAttemptScreen` (core survey-like screen)
Purpose:
- render one question per page and collect answers

Data source:
- `POST /quizzes/{quiz}/start` (new or resumed)

Layout:
- header: back, timer, question progress (`3 / 10`)
- body: current question card
- footer: previous/next buttons
- bottom sticky action: `Review & Submit`

Question UX:
- single-choice question (`question_type=0`): radio buttons
- multiple-choice question (`question_type=1`): checkboxes
- prefill from `selected_answer_ids`

Behavior:
- selecting answers updates local state immediately
- answer save is triggered by:
  - debounce (recommended 600–1000ms), and/or
  - on Next/Previous
- show “Saving...” and “Saved” status inline

---

## C) `QuizReviewScreen`
Purpose:
- final check before submit

Show:
- question index chips (answered vs unanswered)
- summary:
  - answered count
  - unanswered count
  - remaining time

Actions:
- tap chip -> jump to question in `QuizAttemptScreen`
- `Submit Quiz` -> open confirmation sheet

---

## D) `QuizSubmitConfirmSheet`
Purpose:
- avoid accidental submission

Text:
- “Once submitted, you can’t edit answers for this attempt.”

Actions:
- cancel
- confirm submit

---

## E) `QuizSubmittingScreen` (blocking state)
Purpose:
- show progress while final submit call runs

API:
- `POST /quiz-attempts/{attempt}/submit`

On success:
- navigate to `QuizResultScreen`

On failure:
- inline retry + return to review

---

## F) `QuizResultScreen`
Purpose:
- show submitted result respecting backend visibility flags

Data source:
- submit response data directly
- or `GET /quiz-attempts/{attempt}/results` on refresh/reopen

Rules:
- if `show_score_immediately=false`:
  - hide numeric score, points, pass/fail outcome
- if `show_correct_answers=true`:
  - show per-question corrections and explanation

Actions:
- `Back to Quiz List`
- `View Attempt History`

---

## G) `QuizAttemptsHistoryScreen`
Purpose:
- show previous attempts and resume support

API:
- `GET /quizzes/{quiz}/my-attempts`

Show:
- stats: lowest, average, highest, opened, completed, failed
- attempts table/list:
  - attempt number
  - status
  - score
  - started/submitted times
  - answered/total
  - resume badge for in-progress

Action:
- if `can_resume=true` -> `Resume`
  - call `POST /assets/quiz/{quiz}/attempts` (backend returns existing in-progress)

---

## Component Structure (Exact)

## `QuizIntroScreen`
- `QuizIntroHeader`
- `QuizMetaCard`
- `QuizRulesList`
- `QuizIntroActions`

Props contract:
- `quiz: QuizMobileInfo`
- `onStart()`
- `onViewHistory()`

## `QuizAttemptScreen`
- `QuizAttemptHeader`
  - `QuizTimer`
  - `QuestionProgress`
- `QuestionPager`
  - `QuestionCard`
    - `QuestionPrompt`
    - `SingleChoiceOptions` or `MultiChoiceOptions`
- `AnswerSaveStatus`
- `QuestionNavigationBar`
- `QuizAttemptBottomBar`

Props contract:
- `attempt: QuizAttemptDetailMobile`
- `currentQuestionIndex: number`
- `draftAnswers: Record<number, number[]>`
- `saveStatusByQuestion: Record<number, 'idle'|'saving'|'saved'|'error'>`
- callbacks:
  - `onSelectAnswer(questionId, answerIds)`
  - `onNext()`
  - `onPrev()`
  - `onOpenReview()`

## `QuizReviewScreen`
- `ReviewHeader`
- `AnsweredSummaryCard`
- `QuestionStatusGrid`
- `ReviewActions`

Props contract:
- `questions: QuizAttemptQuestionMobile[]`
- `draftAnswers: Record<number, number[]>`
- `remainingTimeSeconds: number | null`
- `onJumpToQuestion(index)`
- `onSubmit()`

## `QuizResultScreen`
- `ResultHeader`
- `ScoreCard` (conditional)
- `PassFailBadge` (conditional)
- `QuestionResultList` (conditional by `show_correct_answers`)
- `ResultActions`

Props contract:
- `result: QuizResultMobile`

## `QuizAttemptsHistoryScreen`
- `AttemptStatsCards`
- `AttemptsList`
- `AttemptListItem`

Props contract:
- `history: QuizAttemptHistoryMobile`
- `onResume(attemptId)`

---

## State Machine

Use one central state store per active quiz attempt.

## States
- `idle`
- `loading_quiz_info`
- `ready_to_start`
- `starting_attempt`
- `attempt_active`
- `saving_answer`
- `reviewing`
- `submitting`
- `submitted`
- `error`

## Events
- `LOAD_INFO_SUCCESS`
- `START_SUCCESS`
- `START_RESUME_SUCCESS`
- `ANSWER_SELECTED`
- `SAVE_SUCCESS`
- `SAVE_FAILED`
- `OPEN_REVIEW`
- `BACK_TO_QUESTIONS`
- `SUBMIT_SUCCESS`
- `SUBMIT_FAILED`
- `TIME_EXPIRED`

## Transition Table
1. `idle -> loading_quiz_info` on screen open
2. `loading_quiz_info -> ready_to_start` on success
3. `ready_to_start -> starting_attempt` on Start CTA
4. `starting_attempt -> attempt_active` on attempt payload
5. `attempt_active -> saving_answer` on save trigger
6. `saving_answer -> attempt_active` on save success
7. `saving_answer -> attempt_active` on save failure (with per-question error flag)
8. `attempt_active -> reviewing` on Review CTA
9. `reviewing -> attempt_active` on Edit answer
10. `reviewing -> submitting` on Submit confirm
11. `submitting -> submitted` on success
12. `submitting -> reviewing` on failure
13. `attempt_active -> submitted` on backend `TIME_EXPIRED` flow followed by results fetch

---

## Data Flow and Caching

## At entry
1. Fetch quiz metadata (`GET quiz show`)
2. Render intro

## On start
1. Call `POST start`
2. Save returned attempt and questions in store
3. Initialize:
- `currentQuestionIndex=0`
- `draftAnswers` from `selected_answer_ids`

## During attempt
- Local source of truth: `draftAnswers`
- Remote persistence: `POST answer` per changed question

Recommended save strategy:
- debounce save after selection
- force flush save on Next/Previous/Review
- force flush all dirty answers before submit

## On resume
- Always call `POST start`
- Backend returns existing in-progress attempt if present from `POST /assets/quiz/{quiz}/attempts`
- Replace local attempt state with response payload

---

## Timer and Expiry Behavior

If `time_limit_minutes` is present:
- countdown from backend `remaining_time_seconds`
- timer continues while screen active
- if app background/foreground:
  - recompute by wall clock delta
  - do not trust only local interval

On save API returning `TIME_EXPIRED`:
- stop interaction
- show “Time expired, submitting...”
- fetch result by:
  - first trying `GET /quiz-attempts/{attempt}/results`

---

## Validation and UI Constraints

Backend validation for save answer:
- `question_id` required, valid
- `answer_ids` required array min 1

Frontend constraints:
- single-choice: enforce exactly one selected answer in UI
- multiple-choice: allow multi-select, at least one before leaving question (recommended)

Optional UX policy:
- allow unanswered questions and submit anyway
- or enforce all answered before enabling submit

If enforcing all answered, keep backend-compatible fallback:
- still handle submission if unanswered allowed by backend rules

---

## Error Handling Map

## Intro/start
- `NOT_ENROLLED` -> block with enrollment message and back action
- `NOT_AVAILABLE` -> disabled start state
- `NO_ATTEMPTS_LEFT` -> show exhausted attempts state

## Save answer
- `ATTEMPT_CLOSED` -> navigate to result/history
- `INVALID_QUESTION` -> refresh attempt payload then retry
- `TIME_EXPIRED` -> move to expiry flow

## Submit
- `ALREADY_SUBMITTED` -> fetch results directly
- network timeout -> keep in review with retry button

## Generic
- 401/403 -> auth recovery (refresh/login)
- 404 -> stale resource, return to quiz list
- 500 -> retry affordance + telemetry log

---

## Analytics Events (Recommended)

Track:
- `quiz_intro_viewed`
- `quiz_start_clicked`
- `quiz_attempt_started`
- `quiz_attempt_resumed`
- `quiz_answer_saved`
- `quiz_review_opened`
- `quiz_submit_clicked`
- `quiz_submit_success`
- `quiz_submit_failed`
- `quiz_result_viewed`

Attach dimensions:
- `center_id`
- `course_id`
- `quiz_id`
- `attempt_id`
- `attempt_number`
- `question_id` (for answer save)

---

## Accessibility and UX Quality

Required:
- touch targets >= 44px
- clear focus states
- answer controls with proper accessibility labels
- timer color change near expiry, not only color-based warning
- resilient orientation handling
- preserve current question index on app resume

---

## QA Checklist

1. Student can start quiz from intro.
2. Student sees one question per page.
3. Selecting answer saves and persists after screen reopen.
4. Previous answers are prefilled on resume.
5. Timer decrements correctly and handles backgrounding.
6. Review screen shows correct answered/unanswered counts.
7. Submit confirms and transitions to result.
8. Result visibility respects:
- `show_score_immediately`
- `show_correct_answers`
9. `my-attempts` stats render correctly.
10. In-progress attempt shows Resume action.
11. Error codes map to correct UI states.
12. Locale switch (`en/ar`) renders translated question/answer text.

---

## Engineering Notes

1. Keep quiz state isolated by `quiz_id + attempt_id` to avoid collisions.
2. Avoid optimistic final submit without server confirmation.
3. For answer save requests, cancel previous in-flight save for same `question_id` and keep latest only.
4. If using React Native, prefer `react-query` + lightweight local state store for pager interaction.
