# Assignments & Quizzes System

> Comprehensive assessment system with MCQ quizzes (manual + AI-generated), full assignment types, configurable attempts, and optional course completion requirements.

## Overview

This feature adds a complete assessment layer to the LMS:
- **Quizzes**: MCQ questions with auto-grading, attachable to any content level
- **AI Generation**: Generate questions from video transcripts and PDF content
- **Assignments**: File upload, text response, link submission, group assignments
- **Attempts**: Configurable attempt limits with score policies
- **Time Limits**: Optional timed quizzes with auto-submit
- **Completion**: Optional requirement for course completion

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      ASSIGNMENTS & QUIZZES SYSTEM                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  CONTENT ATTACHMENT LEVELS                                                  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │   Course ──┬── Section ──┬── Video ── Quiz/Assignment               │   │
│  │            │             │                                          │   │
│  │            │             └── PDF ──── Quiz/Assignment               │   │
│  │            │                                                        │   │
│  │            └── Quiz/Assignment (section level)                      │   │
│  │                                                                      │   │
│  │            └── Quiz/Assignment (course level / standalone)          │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  QUIZ FLOW                                                                  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐         │   │
│  │  │  Quiz    │──▶│ Questions│──▶│ Attempt  │──▶│ Grading  │         │   │
│  │  │ (config) │   │  (MCQ)   │   │ (answers)│   │ (auto)   │         │   │
│  │  └──────────┘   └──────────┘   └──────────┘   └──────────┘         │   │
│  │                       ▲                                              │   │
│  │                       │                                              │   │
│  │              ┌────────┴────────┐                                    │   │
│  │              │  AI Generation  │                                    │   │
│  │              │ (transcript/PDF)│                                    │   │
│  │              └─────────────────┘                                    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ASSIGNMENT FLOW                                                            │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐         │   │
│  │  │Assignment│──▶│Submission│──▶│  Review  │──▶│  Grade   │         │   │
│  │  │ (config) │   │(file/txt)│   │ (admin)  │   │ (manual) │         │   │
│  │  └──────────┘   └──────────┘   └──────────┘   └──────────┘         │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### quizzes

Quiz configuration.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Center scope |
| `course_id` | FK → courses | Course this quiz belongs to |
| `title_translations` | JSON | Quiz title |
| `description_translations` | JSON | Quiz description/instructions |
| `attachable_type` | varchar | Polymorphic: 'video', 'pdf', 'section', 'course' |
| `attachable_id` | bigint | Related entity ID |
| `passing_score` | decimal(5,2) | Minimum % to pass (e.g., 70.00) |
| `max_attempts` | int | Max attempts allowed (0 = unlimited) |
| `attempt_score_policy` | tinyint | 0=best, 1=latest, 2=average |
| `time_limit_minutes` | int | Time limit in minutes (null = no limit) |
| `shuffle_questions` | boolean | Randomize question order |
| `shuffle_answers` | boolean | Randomize answer order |
| `show_correct_answers` | boolean | Show correct answers after submission |
| `show_score_immediately` | boolean | Show score right after submission |
| `is_required` | boolean | Required for course completion |
| `is_active` | boolean | Quiz is available to students |
| `available_from` | timestamp | Start availability |
| `available_until` | timestamp | End availability |
| `order_index` | int | Display order |
| `created_by` | FK → users | Admin who created |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[center_id, course_id]`
- `[attachable_type, attachable_id]`
- `[course_id, is_active, is_required]`

### quiz_questions

MCQ questions for quizzes.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `quiz_id` | FK → quizzes | Parent quiz |
| `question_translations` | JSON | Question text (supports markdown) |
| `question_type` | tinyint | 0=single_choice, 1=multiple_choice |
| `explanation_translations` | JSON | Explanation shown after answer |
| `points` | decimal(5,2) | Points for this question |
| `order_index` | int | Question order |
| `is_active` | boolean | Include in quiz |
| `ai_generated` | boolean | Generated by AI |
| `ai_source_type` | varchar | 'video_transcript' or 'pdf_content' |
| `ai_source_id` | bigint | Video or PDF ID |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[quiz_id, order_index]`
- `[quiz_id, is_active]`

### quiz_answers

Answer options for questions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `quiz_question_id` | FK → quiz_questions | Parent question |
| `answer_translations` | JSON | Answer text |
| `is_correct` | boolean | This is a correct answer |
| `order_index` | int | Answer order |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[quiz_question_id, order_index]`

### quiz_attempts

Student quiz attempts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `quiz_id` | FK → quizzes | Quiz taken |
| `user_id` | FK → users | Student |
| `enrollment_id` | FK → enrollments | Related enrollment |
| `center_id` | FK → centers | Center scope |
| `attempt_number` | int | Which attempt (1, 2, 3...) |
| `status` | tinyint | 0=in_progress, 1=submitted, 2=timed_out, 3=graded |
| `started_at` | timestamp | When attempt started |
| `submitted_at` | timestamp | When submitted |
| `time_spent_seconds` | int | Total time spent |
| `score` | decimal(5,2) | Score achieved (%) |
| `points_earned` | decimal(8,2) | Points earned |
| `points_possible` | decimal(8,2) | Total possible points |
| `passed` | boolean | Met passing score |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[quiz_id, user_id]`
- `[user_id, enrollment_id]`
- `[center_id, status]`

### quiz_attempt_answers

Individual answers in an attempt.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `quiz_attempt_id` | FK → quiz_attempts | Parent attempt |
| `quiz_question_id` | FK → quiz_questions | Question answered |
| `selected_answer_ids` | JSON | Array of selected answer IDs |
| `is_correct` | boolean | Answer was correct |
| `points_earned` | decimal(5,2) | Points for this answer |
| `answered_at` | timestamp | When answered |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[quiz_attempt_id]`
- `[quiz_question_id]`

### assignments

Assignment configuration.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Center scope |
| `course_id` | FK → courses | Course this belongs to |
| `title_translations` | JSON | Assignment title |
| `description_translations` | JSON | Instructions (markdown) |
| `attachable_type` | varchar | Polymorphic: 'video', 'pdf', 'section', 'course' |
| `attachable_id` | bigint | Related entity ID |
| `submission_types` | JSON | Allowed types: ['file', 'text', 'link'] |
| `allowed_file_types` | JSON | e.g., ['pdf', 'doc', 'docx', 'jpg', 'png'] |
| `max_file_size_mb` | int | Max file size |
| `max_files` | int | Max files per submission |
| `is_group_assignment` | boolean | Group submission allowed |
| `max_group_size` | int | Max students per group |
| `max_points` | decimal(8,2) | Maximum points |
| `passing_score` | decimal(5,2) | Minimum % to pass |
| `is_required` | boolean | Required for course completion |
| `is_active` | boolean | Assignment is available |
| `due_date` | timestamp | Submission deadline |
| `late_submission_allowed` | boolean | Accept late submissions |
| `late_penalty_percent` | decimal(5,2) | Penalty per day late |
| `available_from` | timestamp | Start availability |
| `available_until` | timestamp | Hard cutoff |
| `order_index` | int | Display order |
| `created_by` | FK → users | Admin who created |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[center_id, course_id]`
- `[attachable_type, attachable_id]`
- `[course_id, is_active, is_required]`

### assignment_submissions

Student submissions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `assignment_id` | FK → assignments | Assignment |
| `user_id` | FK → users | Submitting student |
| `enrollment_id` | FK → enrollments | Related enrollment |
| `center_id` | FK → centers | Center scope |
| `group_id` | FK → assignment_groups | Group (if group assignment) |
| `submission_type` | tinyint | 0=file, 1=text, 2=link |
| `text_content` | text | Text submission |
| `link_url` | varchar | Link submission |
| `status` | tinyint | 0=draft, 1=submitted, 2=graded, 3=returned |
| `submitted_at` | timestamp | Submission time |
| `is_late` | boolean | Submitted after due date |
| `days_late` | int | Days after deadline |
| `score` | decimal(8,2) | Score given |
| `score_after_penalty` | decimal(8,2) | Score after late penalty |
| `passed` | boolean | Met passing score |
| `feedback` | text | Instructor feedback |
| `graded_by` | FK → users | Admin who graded |
| `graded_at` | timestamp | When graded |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[assignment_id, user_id]`
- `[user_id, enrollment_id]`
- `[center_id, status]`
- `[assignment_id, status]`

### assignment_submission_files

Files attached to submissions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `assignment_submission_id` | FK | Parent submission |
| `file_name` | varchar | Original filename |
| `file_path` | varchar | Storage path |
| `file_size_kb` | int | File size |
| `file_type` | varchar | MIME type |
| `storage_provider` | varchar | 'local', 'bunny', 's3' |
| `created_at` | timestamp | |

**Indexes:**
- `[assignment_submission_id]`

### assignment_groups

Groups for group assignments.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `assignment_id` | FK → assignments | Assignment |
| `name` | varchar | Group name |
| `created_by` | FK → users | Student who created |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[assignment_id]`

### assignment_group_members

Group membership.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `assignment_group_id` | FK | Group |
| `user_id` | FK → users | Student |
| `role` | tinyint | 0=member, 1=leader |
| `joined_at` | timestamp | |
| `created_at` | timestamp | |

**Indexes:**
- `UNIQUE [assignment_group_id, user_id]`

### ai_generation_jobs

Track AI question generation jobs.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `quiz_id` | FK → quizzes | Target quiz |
| `source_type` | varchar | 'video' or 'pdf' |
| `source_id` | bigint | Video or PDF ID |
| `status` | tinyint | 0=pending, 1=processing, 2=completed, 3=failed |
| `questions_requested` | int | Number of questions to generate |
| `questions_generated` | int | Actually generated |
| `ai_provider` | varchar | 'openai', 'anthropic', etc. |
| `ai_model` | varchar | Model used |
| `prompt_used` | text | Prompt sent to AI |
| `error_message` | text | Error if failed |
| `started_at` | timestamp | |
| `completed_at` | timestamp | |
| `created_by` | FK → users | Admin who initiated |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[quiz_id]`
- `[status]`

---

## Enums

### QuestionType

```php
enum QuestionType: int
{
    case SingleChoice = 0;   // One correct answer
    case MultipleChoice = 1; // Multiple correct answers
}
```

### AttemptScorePolicy

```php
enum AttemptScorePolicy: int
{
    case Best = 0;    // Keep highest score
    case Latest = 1;  // Keep most recent
    case Average = 2; // Average of all attempts
}
```

### QuizAttemptStatus

```php
enum QuizAttemptStatus: int
{
    case InProgress = 0;
    case Submitted = 1;
    case TimedOut = 2;
    case Graded = 3;
}
```

### SubmissionType

```php
enum SubmissionType: int
{
    case File = 0;
    case Text = 1;
    case Link = 2;
}
```

### SubmissionStatus

```php
enum SubmissionStatus: int
{
    case Draft = 0;
    case Submitted = 1;
    case Graded = 2;
    case Returned = 3; // Returned for revision
}
```

### AIGenerationStatus

```php
enum AIGenerationStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Completed = 2;
    case Failed = 3;
}
```

---

## Service Layer

### QuizService

```php
interface QuizServiceInterface
{
    // CRUD
    public function create(Course $course, array $data): Quiz;
    public function update(Quiz $quiz, array $data): Quiz;
    public function delete(Quiz $quiz): void;
    public function duplicate(Quiz $quiz): Quiz;

    // Questions
    public function addQuestion(Quiz $quiz, array $data): QuizQuestion;
    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion;
    public function deleteQuestion(QuizQuestion $question): void;
    public function reorderQuestions(Quiz $quiz, array $questionIds): void;

    // Availability
    public function isAvailable(Quiz $quiz, User $student): bool;
    public function canAttempt(Quiz $quiz, User $student): bool;
    public function getRemainingAttempts(Quiz $quiz, User $student): int;
}
```

### QuizAttemptService

```php
interface QuizAttemptServiceInterface
{
    // Attempt lifecycle
    public function start(Quiz $quiz, User $student, Enrollment $enrollment): QuizAttempt;
    public function saveAnswer(QuizAttempt $attempt, QuizQuestion $question, array $answerIds): QuizAttemptAnswer;
    public function submit(QuizAttempt $attempt): QuizAttempt;
    public function autoSubmitTimedOut(): int; // Scheduled job

    // Grading
    public function grade(QuizAttempt $attempt): QuizAttempt;
    public function calculateFinalScore(Quiz $quiz, User $student): float;

    // Queries
    public function getAttempts(Quiz $quiz, User $student): Collection;
    public function getAttemptDetails(QuizAttempt $attempt): array;
}
```

### AIQuizGeneratorService

```php
interface AIQuizGeneratorServiceInterface
{
    // Generate from video
    public function generateFromVideo(Quiz $quiz, Video $video, int $questionCount): AIGenerationJob;

    // Generate from PDF
    public function generateFromPdf(Quiz $quiz, Pdf $pdf, int $questionCount): AIGenerationJob;

    // Process job (called by queue worker)
    public function processJob(AIGenerationJob $job): void;

    // Get transcript from video
    public function extractVideoTranscript(Video $video): string;

    // Extract text from PDF
    public function extractPdfContent(Pdf $pdf): string;
}
```

### AssignmentService

```php
interface AssignmentServiceInterface
{
    // CRUD
    public function create(Course $course, array $data): Assignment;
    public function update(Assignment $assignment, array $data): Assignment;
    public function delete(Assignment $assignment): void;

    // Availability
    public function isAvailable(Assignment $assignment, User $student): bool;
    public function canSubmit(Assignment $assignment, User $student): bool;
    public function isLate(Assignment $assignment): bool;
}
```

### AssignmentSubmissionService

```php
interface AssignmentSubmissionServiceInterface
{
    // Submission
    public function createDraft(Assignment $assignment, User $student, Enrollment $enrollment): AssignmentSubmission;
    public function updateDraft(AssignmentSubmission $submission, array $data): AssignmentSubmission;
    public function attachFile(AssignmentSubmission $submission, UploadedFile $file): AssignmentSubmissionFile;
    public function removeFile(AssignmentSubmissionFile $file): void;
    public function submit(AssignmentSubmission $submission): AssignmentSubmission;

    // Grading
    public function grade(AssignmentSubmission $submission, float $score, string $feedback, User $grader): AssignmentSubmission;
    public function returnForRevision(AssignmentSubmission $submission, string $feedback, User $grader): AssignmentSubmission;

    // Group
    public function createGroup(Assignment $assignment, User $leader, string $name): AssignmentGroup;
    public function joinGroup(AssignmentGroup $group, User $student): void;
    public function leaveGroup(AssignmentGroup $group, User $student): void;
}
```

### AssessmentProgressService

```php
interface AssessmentProgressServiceInterface
{
    // Check completion status
    public function getRequiredAssessments(Course $course): Collection;
    public function getCompletedAssessments(User $student, Course $course): Collection;
    public function hasCompletedRequiredAssessments(User $student, Course $course): bool;

    // Progress summary
    public function getProgressSummary(User $student, Course $course): array;
}
```

---

## API Endpoints

### Admin - Quiz Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/courses/{course}/quizzes` | List quizzes |
| POST | `/api/v1/admin/centers/{center}/courses/{course}/quizzes` | Create quiz |
| GET | `/api/v1/admin/centers/{center}/quizzes/{quiz}` | Get quiz details |
| PUT | `/api/v1/admin/centers/{center}/quizzes/{quiz}` | Update quiz |
| DELETE | `/api/v1/admin/centers/{center}/quizzes/{quiz}` | Delete quiz |
| POST | `/api/v1/admin/centers/{center}/quizzes/{quiz}/duplicate` | Duplicate quiz |

### Admin - Quiz Questions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/quizzes/{quiz}/questions` | List questions |
| POST | `/api/v1/admin/centers/{center}/quizzes/{quiz}/questions` | Add question |
| PUT | `/api/v1/admin/centers/{center}/quizzes/{quiz}/questions/{question}` | Update question |
| DELETE | `/api/v1/admin/centers/{center}/quizzes/{quiz}/questions/{question}` | Delete question |
| PUT | `/api/v1/admin/centers/{center}/quizzes/{quiz}/questions/reorder` | Reorder questions |

### Admin - AI Generation

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/admin/centers/{center}/quizzes/{quiz}/generate-from-video` | Generate from video |
| POST | `/api/v1/admin/centers/{center}/quizzes/{quiz}/generate-from-pdf` | Generate from PDF |
| GET | `/api/v1/admin/centers/{center}/ai-generation-jobs/{job}` | Check job status |
| POST | `/api/v1/admin/centers/{center}/ai-generation-jobs/{job}/approve` | Approve generated questions |
| DELETE | `/api/v1/admin/centers/{center}/ai-generation-jobs/{job}` | Discard generated questions |

### Admin - Quiz Analytics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/quizzes/{quiz}/attempts` | List all attempts |
| GET | `/api/v1/admin/centers/{center}/quizzes/{quiz}/analytics` | Quiz statistics |
| GET | `/api/v1/admin/centers/{center}/quizzes/{quiz}/attempts/{attempt}` | Attempt details |

### Admin - Assignment Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/courses/{course}/assignments` | List assignments |
| POST | `/api/v1/admin/centers/{center}/courses/{course}/assignments` | Create assignment |
| GET | `/api/v1/admin/centers/{center}/assignments/{assignment}` | Get assignment |
| PUT | `/api/v1/admin/centers/{center}/assignments/{assignment}` | Update assignment |
| DELETE | `/api/v1/admin/centers/{center}/assignments/{assignment}` | Delete assignment |

### Admin - Assignment Grading

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/assignments/{assignment}/submissions` | List submissions |
| GET | `/api/v1/admin/centers/{center}/submissions/{submission}` | Get submission |
| POST | `/api/v1/admin/centers/{center}/submissions/{submission}/grade` | Grade submission |
| POST | `/api/v1/admin/centers/{center}/submissions/{submission}/return` | Return for revision |
| GET | `/api/v1/admin/centers/{center}/submissions/{submission}/files/{file}/download` | Download file |

### Mobile - Quizzes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/courses/{course}/quizzes` | List available quizzes |
| GET | `/api/v1/centers/{center}/quizzes/{quiz}` | Get quiz info (before starting) |
| POST | `/api/v1/centers/{center}/quizzes/{quiz}/start` | Start attempt |
| GET | `/api/v1/centers/{center}/quiz-attempts/{attempt}` | Get current attempt |
| POST | `/api/v1/centers/{center}/quiz-attempts/{attempt}/answer` | Save answer |
| POST | `/api/v1/centers/{center}/quiz-attempts/{attempt}/submit` | Submit attempt |
| GET | `/api/v1/centers/{center}/quiz-attempts/{attempt}/results` | Get results |
| GET | `/api/v1/centers/{center}/quizzes/{quiz}/my-attempts` | My attempt history |

### Mobile - Assignments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/courses/{course}/assignments` | List assignments |
| GET | `/api/v1/centers/{center}/assignments/{assignment}` | Get assignment details |
| POST | `/api/v1/centers/{center}/assignments/{assignment}/submissions` | Create/update draft |
| POST | `/api/v1/centers/{center}/submissions/{submission}/files` | Upload file |
| DELETE | `/api/v1/centers/{center}/submissions/{submission}/files/{file}` | Remove file |
| POST | `/api/v1/centers/{center}/submissions/{submission}/submit` | Submit assignment |
| GET | `/api/v1/centers/{center}/assignments/{assignment}/my-submission` | My submission |

### Mobile - Groups (for group assignments)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/assignments/{assignment}/groups` | List available groups |
| POST | `/api/v1/centers/{center}/assignments/{assignment}/groups` | Create group |
| POST | `/api/v1/centers/{center}/assignment-groups/{group}/join` | Join group |
| POST | `/api/v1/centers/{center}/assignment-groups/{group}/leave` | Leave group |
| GET | `/api/v1/centers/{center}/assignment-groups/{group}` | Group details |

---

## AI Generation Flow

### Video Quiz Generation

```
1. Admin selects video and requests N questions
2. System creates AIGenerationJob (status: pending)
3. Queue job processes:
   a. Extract transcript from Bunny Stream or stored transcript
   b. Send to AI with prompt:
      "Generate {N} multiple choice questions from this transcript.
       Each question should have 4 options with 1 correct answer.
       Include explanation for correct answer.
       Format as JSON array."
   c. Parse AI response
   d. Store as draft questions (not yet added to quiz)
4. Admin reviews generated questions
5. Admin approves/edits/discards questions
6. Approved questions added to quiz
```

### PDF Quiz Generation

```
1. Admin selects PDF and requests N questions
2. System creates AIGenerationJob (status: pending)
3. Queue job processes:
   a. Extract text from PDF (using pdftotext or similar)
   b. Send to AI with same prompt structure
   c. Parse AI response
   d. Store as draft questions
4. Admin reviews and approves
```

### AI Prompt Template

```
You are an educational assessment expert. Based on the following content, generate {count} multiple choice questions.

CONTENT:
{content}

REQUIREMENTS:
- Each question should test understanding, not just recall
- Provide exactly 4 answer options labeled A, B, C, D
- Mark the correct answer(s)
- Include a brief explanation for why the answer is correct
- Questions should be clear and unambiguous
- Vary difficulty levels

OUTPUT FORMAT (JSON):
[
  {
    "question": "Question text here?",
    "options": [
      {"label": "A", "text": "Option A text", "is_correct": false},
      {"label": "B", "text": "Option B text", "is_correct": true},
      {"label": "C", "text": "Option C text", "is_correct": false},
      {"label": "D", "text": "Option D text", "is_correct": false}
    ],
    "explanation": "Explanation of correct answer",
    "difficulty": "medium"
  }
]
```

---

## Integration with Course Completion

When `is_required = true` on a quiz or assignment:

```php
// In CourseProgressService (from Certificates feature)
public function isCompleted(Enrollment $enrollment): bool
{
    // Check videos completed
    $videosComplete = $this->allVideosWatched($enrollment);

    // Check required assessments
    $assessmentsComplete = $this->assessmentProgressService
        ->hasCompletedRequiredAssessments($enrollment->user, $enrollment->course);

    return $videosComplete && $assessmentsComplete;
}
```

---

## Implementation Checklist

### Phase 1: Database Architecture (10 migrations)
- [ ] Create `quizzes` table
- [ ] Create `quiz_questions` table
- [ ] Create `quiz_answers` table
- [ ] Create `quiz_attempts` table
- [ ] Create `quiz_attempt_answers` table
- [ ] Create `assignments` table
- [ ] Create `assignment_submissions` table
- [ ] Create `assignment_submission_files` table
- [ ] Create `assignment_groups` table
- [ ] Create `assignment_group_members` table
- [ ] Create `ai_generation_jobs` table

### Phase 2: Enums & Models (11 models)
- [ ] Create enums (6 enums)
- [ ] Create `Quiz` model
- [ ] Create `QuizQuestion` model
- [ ] Create `QuizAnswer` model
- [ ] Create `QuizAttempt` model
- [ ] Create `QuizAttemptAnswer` model
- [ ] Create `Assignment` model
- [ ] Create `AssignmentSubmission` model
- [ ] Create `AssignmentSubmissionFile` model
- [ ] Create `AssignmentGroup` model
- [ ] Create `AssignmentGroupMember` model
- [ ] Create `AIGenerationJob` model

### Phase 3: Service Layer (6 services)
- [ ] Create `QuizService`
- [ ] Create `QuizAttemptService`
- [ ] Create `AIQuizGeneratorService`
- [ ] Create `AssignmentService`
- [ ] Create `AssignmentSubmissionService`
- [ ] Create `AssessmentProgressService`

### Phase 4: Admin Quiz API
- [ ] Quiz CRUD controller
- [ ] Question CRUD controller
- [ ] AI generation controller
- [ ] Quiz analytics controller
- [ ] Form requests
- [ ] Resources

### Phase 5: Admin Assignment API
- [ ] Assignment CRUD controller
- [ ] Submission grading controller
- [ ] Form requests
- [ ] Resources

### Phase 6: Mobile Quiz API
- [ ] Quiz listing controller
- [ ] Attempt controller
- [ ] Form requests
- [ ] Resources

### Phase 7: Mobile Assignment API
- [ ] Assignment controller
- [ ] Submission controller
- [ ] Group controller
- [ ] Form requests
- [ ] Resources

### Phase 8: AI Integration
- [ ] Video transcript extraction (Bunny API or stored)
- [ ] PDF text extraction service
- [ ] AI provider integration (OpenAI/Anthropic)
- [ ] Job processing queue
- [ ] Question review/approval flow

### Phase 9: Integration
- [ ] Hook into course completion (Certificates feature)
- [ ] Add assessments to course content listing
- [ ] Scheduled job for auto-submit timed-out attempts

### Phase 10: Quality & Testing
- [ ] Create factories for all models
- [ ] Feature tests for quiz flow
- [ ] Feature tests for assignment flow
- [ ] Feature tests for AI generation
- [ ] Unit tests for grading logic
- [ ] Run quality checks

---

## File Summary (~90 files)

```
Migrations (11):
- create_quizzes_table.php
- create_quiz_questions_table.php
- create_quiz_answers_table.php
- create_quiz_attempts_table.php
- create_quiz_attempt_answers_table.php
- create_assignments_table.php
- create_assignment_submissions_table.php
- create_assignment_submission_files_table.php
- create_assignment_groups_table.php
- create_assignment_group_members_table.php
- create_ai_generation_jobs_table.php

Enums (6):
- QuestionType.php
- AttemptScorePolicy.php
- QuizAttemptStatus.php
- SubmissionType.php
- SubmissionStatus.php
- AIGenerationStatus.php

Models (11):
- Quiz.php
- QuizQuestion.php
- QuizAnswer.php
- QuizAttempt.php
- QuizAttemptAnswer.php
- Assignment.php
- AssignmentSubmission.php
- AssignmentSubmissionFile.php
- AssignmentGroup.php
- AssignmentGroupMember.php
- AIGenerationJob.php

Services (12):
- QuizServiceInterface.php + QuizService.php
- QuizAttemptServiceInterface.php + QuizAttemptService.php
- AIQuizGeneratorServiceInterface.php + AIQuizGeneratorService.php
- AssignmentServiceInterface.php + AssignmentService.php
- AssignmentSubmissionServiceInterface.php + AssignmentSubmissionService.php
- AssessmentProgressServiceInterface.php + AssessmentProgressService.php

Controllers (12):
- Admin/QuizController.php
- Admin/QuizQuestionController.php
- Admin/AIGenerationController.php
- Admin/QuizAnalyticsController.php
- Admin/AssignmentController.php
- Admin/AssignmentSubmissionController.php
- Mobile/QuizController.php
- Mobile/QuizAttemptController.php
- Mobile/AssignmentController.php
- Mobile/AssignmentSubmissionController.php
- Mobile/AssignmentGroupController.php

Jobs (2):
- ProcessAIGenerationJob.php
- AutoSubmitTimedOutAttempts.php

Form Requests (~20)
Resources (~15)
Routes (4)
Factories (11)
Tests (~15)
```

---

## Testing Plan

```bash
# Run all assessment tests
php artisan test --filter="Quiz"
php artisan test --filter="Assignment"
php artisan test --filter="AIGeneration"
```

### Key Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Start quiz attempt | Feature | High |
| Save answer during attempt | Feature | High |
| Submit attempt and auto-grade | Feature | High |
| Time limit auto-submit | Feature | High |
| Multiple attempts with score policy | Feature | High |
| Create assignment submission | Feature | High |
| Upload file to submission | Feature | High |
| Grade submission with feedback | Feature | High |
| AI generates questions from video | Feature | High |
| AI generates questions from PDF | Feature | High |
| Group assignment creation | Feature | Medium |
| Late submission penalty | Feature | Medium |
| Required assessment blocks completion | Feature | Medium |
