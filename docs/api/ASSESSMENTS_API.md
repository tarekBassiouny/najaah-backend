# Assessments API Documentation

> Complete API reference for Quizzes and Assignments feature - for Frontend (Admin) and Mobile teams.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Admin API - Quizzes](#admin-api---quizzes)
4. [Admin API - Assignments](#admin-api---assignments)
5. [Mobile API - Quizzes](#mobile-api---quizzes)
6. [Mobile API - Assignments](#mobile-api---assignments)
7. [Enums & Constants](#enums--constants)
8. [Error Codes](#error-codes)

---

## Overview

The Assessments system provides two types of assessable content:

### Quizzes
- **MCQ-based** with single or multiple choice questions
- **Auto-graded** upon submission
- **Timed** with optional time limits
- **Configurable attempts** with score policies (best/latest/average)
- **AI-generated** questions from video transcripts or PDFs

### Assignments
- **Multiple submission types**: file upload, text, or link
- **Group assignments** supported with team formation
- **Manual grading** with feedback
- **Late submission** handling with configurable penalties

Both can be attached to: Course, Section, Video, or PDF (polymorphic).

---

## Authentication

### Admin API
- **Auth**: Sanctum token in `Authorization: Bearer {token}` header
- **Middleware**: `require.permission:quiz.manage` or `require.permission:assignment.manage`
- **Scope**: `scope.center` - all endpoints are center-scoped

### Mobile API
- **Auth**: JWT token in `Authorization: Bearer {token}` header
- **Middleware**: `jwt.mobile`
- **User Type**: Students only (`is_student = true`)

---

## Admin API - Quizzes

### Base URL
```
/api/v1/admin
```

### Quiz CRUD

#### List Quizzes
```http
GET /centers/{center}/courses/{course}/quizzes
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `is_active` | boolean | Filter by active status |
| `is_required` | boolean | Filter by required status |
| `attachable_type` | string | Filter by attachment type |
| `page` | integer | Page number |
| `per_page` | integer | Items per page (default: 15) |

**Response:**
```json
{
  "success": true,
  "message": "Operation completed",
  "data": [
    {
      "id": 1,
      "title": "Quiz Title",
      "description": "Quiz description",
      "attachable_type": "video",
      "attachable_id": 123,
      "passing_score": 70.00,
      "max_attempts": 3,
      "time_limit_minutes": 30,
      "is_required": true,
      "is_active": true,
      "is_available": true,
      "questions_count": 10,
      "attempts_count": 25,
      "created_at": "2026-03-10T12:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 5, "total": 50 }
}
```

#### Create Quiz
```http
POST /centers/{center}/courses/{course}/quizzes
```

**Request Body:**
```json
{
  "title_translations": {
    "en": "Final Exam",
    "ar": "الامتحان النهائي"
  },
  "description_translations": {
    "en": "This quiz covers all course material",
    "ar": "يغطي هذا الاختبار جميع مواد الدورة"
  },
  "attachable_type": "course",
  "attachable_id": null,
  "passing_score": 70.00,
  "max_attempts": 3,
  "attempt_score_policy": 0,
  "time_limit_minutes": 60,
  "shuffle_questions": true,
  "shuffle_answers": true,
  "show_correct_answers": true,
  "show_score_immediately": true,
  "is_required": true,
  "is_active": true,
  "available_from": "2026-03-15T00:00:00Z",
  "available_until": "2026-06-15T23:59:59Z",
  "order_index": 1
}
```

**Response:** `201 Created` with quiz detail

#### Get Quiz Details
```http
GET /centers/{center}/quizzes/{quiz}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Final Exam",
    "title_translations": {"en": "Final Exam", "ar": "الامتحان النهائي"},
    "description": "This quiz covers all course material",
    "description_translations": {...},
    "attachable_type": "course",
    "attachable_id": 45,
    "passing_score": 70.00,
    "max_attempts": 3,
    "attempt_score_policy": 0,
    "attempt_score_policy_label": "Best",
    "time_limit_minutes": 60,
    "shuffle_questions": true,
    "shuffle_answers": true,
    "show_correct_answers": true,
    "show_score_immediately": true,
    "is_required": true,
    "is_active": true,
    "available_from": "2026-03-15T00:00:00Z",
    "available_until": "2026-06-15T23:59:59Z",
    "questions_count": 10,
    "attempts_count": 25,
    "submissions": [...],
    "created_at": "2026-03-10T12:00:00Z",
    "updated_at": "2026-03-10T12:00:00Z"
  }
}
```

#### Update Quiz
```http
PUT /centers/{center}/quizzes/{quiz}
```

**Request Body:** Same as create (all fields optional)

#### Delete Quiz
```http
DELETE /centers/{center}/quizzes/{quiz}
```

#### Duplicate Quiz
```http
POST /centers/{center}/quizzes/{quiz}/duplicate
```

**Response:** `201 Created` with new quiz detail (copies all questions)

---

### Quiz Questions

#### List Questions
```http
GET /centers/{center}/quizzes/{quiz}/questions
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "question": "What is the capital of France?",
      "question_translations": {"en": "What is the capital of France?"},
      "question_type": 0,
      "question_type_label": "Single Choice",
      "explanation": "Paris is the capital city of France.",
      "points": 1.00,
      "order_index": 0,
      "is_active": true,
      "ai_generated": false,
      "answers": [
        {"id": 1, "answer": "Paris", "is_correct": true, "order_index": 0},
        {"id": 2, "answer": "London", "is_correct": false, "order_index": 1},
        {"id": 3, "answer": "Berlin", "is_correct": false, "order_index": 2},
        {"id": 4, "answer": "Madrid", "is_correct": false, "order_index": 3}
      ]
    }
  ]
}
```

#### Add Question
```http
POST /centers/{center}/quizzes/{quiz}/questions
```

**Request Body:**
```json
{
  "question_translations": {
    "en": "What is the capital of France?",
    "ar": "ما هي عاصمة فرنسا؟"
  },
  "question_type": 0,
  "explanation_translations": {
    "en": "Paris is the capital city of France."
  },
  "points": 1.00,
  "is_active": true,
  "answers": [
    {"answer_translations": {"en": "Paris"}, "is_correct": true},
    {"answer_translations": {"en": "London"}, "is_correct": false},
    {"answer_translations": {"en": "Berlin"}, "is_correct": false},
    {"answer_translations": {"en": "Madrid"}, "is_correct": false}
  ]
}
```

#### Update Question
```http
PUT /centers/{center}/quizzes/{quiz}/questions/{question}
```

#### Delete Question
```http
DELETE /centers/{center}/quizzes/{quiz}/questions/{question}
```

#### Reorder Questions
```http
PUT /centers/{center}/quizzes/{quiz}/questions/reorder
```

**Request Body:**
```json
{
  "question_ids": [3, 1, 4, 2]
}
```

---

### AI Question Generation

#### Generate from Video
```http
POST /centers/{center}/quizzes/{quiz}/generate-from-video
```

**Request Body:**
```json
{
  "video_id": 123,
  "question_count": 5
}
```

**Response:** `202 Accepted` with job status
```json
{
  "success": true,
  "message": "AI generation job started",
  "data": {
    "job_id": 45,
    "status": "pending",
    "questions_requested": 5
  }
}
```

#### Generate from PDF
```http
POST /centers/{center}/quizzes/{quiz}/generate-from-pdf
```

**Request Body:**
```json
{
  "pdf_id": 456,
  "question_count": 10
}
```

#### Check Job Status
```http
GET /centers/{center}/ai-generation-jobs/{job}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 45,
    "status": "completed",
    "status_label": "Completed",
    "questions_requested": 5,
    "questions_generated": 5,
    "ai_provider": "openai",
    "ai_model": "gpt-4",
    "generated_questions": [
      {
        "question": "What is the main topic discussed?",
        "options": [
          {"label": "A", "text": "Option A", "is_correct": false},
          {"label": "B", "text": "Option B", "is_correct": true},
          {"label": "C", "text": "Option C", "is_correct": false},
          {"label": "D", "text": "Option D", "is_correct": false}
        ],
        "explanation": "Explanation here",
        "difficulty": "medium"
      }
    ],
    "started_at": "2026-03-10T12:00:00Z",
    "completed_at": "2026-03-10T12:01:30Z"
  }
}
```

#### Approve Questions
```http
POST /centers/{center}/ai-generation-jobs/{job}/approve
```

**Request Body:**
```json
{
  "question_indexes": [0, 2, 4]
}
```
*If `question_indexes` is omitted, all questions are approved.*

#### Discard Questions
```http
DELETE /centers/{center}/ai-generation-jobs/{job}
```

---

### Quiz Analytics

#### List Attempts
```http
GET /centers/{center}/quizzes/{quiz}/attempts
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `user_id` | integer | Filter by student |
| `status` | integer | Filter by status (0-3) |
| `passed` | boolean | Filter by pass/fail |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": {"id": 10, "name": "John Doe", "email": "john@example.com"},
      "attempt_number": 1,
      "status": 3,
      "status_label": "Graded",
      "score": 85.00,
      "points_earned": 8.50,
      "points_possible": 10.00,
      "passed": true,
      "started_at": "2026-03-10T12:00:00Z",
      "submitted_at": "2026-03-10T12:25:00Z",
      "time_spent_seconds": 1500
    }
  ]
}
```

#### Get Quiz Statistics
```http
GET /centers/{center}/quizzes/{quiz}/analytics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_attempts": 150,
    "unique_students": 50,
    "average_score": 72.5,
    "pass_rate": 68.0,
    "average_time_seconds": 1200,
    "score_distribution": {
      "0-20": 5,
      "21-40": 10,
      "41-60": 20,
      "61-80": 35,
      "81-100": 30
    },
    "question_stats": [
      {
        "question_id": 1,
        "correct_rate": 85.0,
        "average_time_seconds": 45
      }
    ]
  }
}
```

#### Get Attempt Details
```http
GET /centers/{center}/quizzes/{quiz}/attempts/{attempt}
```

---

## Admin API - Assignments

### Assignment CRUD

#### List Assignments
```http
GET /centers/{center}/courses/{course}/assignments
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `is_active` | boolean | Filter by active status |
| `is_required` | boolean | Filter by required status |
| `attachable_type` | string | Filter by attachment type |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Research Paper",
      "description": "Write a research paper on...",
      "attachable_type": "section",
      "attachable_id": 5,
      "submission_types": ["file", "text"],
      "is_group_assignment": false,
      "max_points": 100.00,
      "passing_score": 60.00,
      "is_required": true,
      "is_active": true,
      "due_date": "2026-04-15T23:59:59Z",
      "late_submission_allowed": true,
      "submissions_count": 25,
      "pending_grading_count": 5,
      "created_at": "2026-03-10T12:00:00Z"
    }
  ]
}
```

#### Create Assignment
```http
POST /centers/{center}/courses/{course}/assignments
```

**Request Body:**
```json
{
  "title_translations": {
    "en": "Research Paper",
    "ar": "ورقة بحثية"
  },
  "description_translations": {
    "en": "Write a research paper on the topic...",
    "ar": "اكتب ورقة بحثية حول الموضوع..."
  },
  "attachable_type": "section",
  "attachable_id": 5,
  "submission_types": ["file", "text"],
  "allowed_file_types": ["pdf", "doc", "docx"],
  "max_file_size_mb": 10,
  "max_files": 3,
  "is_group_assignment": false,
  "max_group_size": null,
  "max_points": 100.00,
  "passing_score": 60.00,
  "is_required": true,
  "is_active": true,
  "due_date": "2026-04-15T23:59:59Z",
  "late_submission_allowed": true,
  "late_penalty_percent": 10.00,
  "available_from": "2026-03-15T00:00:00Z",
  "available_until": "2026-04-30T23:59:59Z",
  "order_index": 1
}
```

#### Get Assignment Details
```http
GET /centers/{center}/assignments/{assignment}
```

#### Update Assignment
```http
PUT /centers/{center}/assignments/{assignment}
```

#### Delete Assignment
```http
DELETE /centers/{center}/assignments/{assignment}
```

---

### Assignment Submissions

#### List Submissions
```http
GET /centers/{center}/assignments/{assignment}/submissions
```

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `user_id` | integer | Filter by student |
| `status` | integer | Filter by status (0-3) |
| `is_late` | boolean | Filter by late submissions |
| `page` | integer | Page number |
| `per_page` | integer | Items per page |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": {"id": 10, "name": "John Doe", "email": "john@example.com"},
      "submission_type": 0,
      "submission_type_label": "File",
      "status": 1,
      "status_label": "Submitted",
      "submitted_at": "2026-04-14T10:30:00Z",
      "is_late": false,
      "days_late": 0,
      "score": null,
      "passed": null,
      "files_count": 2,
      "graded_at": null
    }
  ]
}
```

#### Get Submission Details
```http
GET /centers/{center}/submissions/{submission}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "assignment": {
      "id": 5,
      "title": "Research Paper",
      "max_points": 100.00,
      "passing_score": 60.00
    },
    "user": {"id": 10, "name": "John Doe", "email": "john@example.com", "phone": "..."},
    "group": null,
    "submission_type": 0,
    "submission_type_label": "File",
    "text_content": null,
    "link_url": null,
    "status": 1,
    "status_label": "Submitted",
    "submitted_at": "2026-04-14T10:30:00Z",
    "is_late": false,
    "days_late": 0,
    "score": null,
    "score_after_penalty": null,
    "passed": null,
    "feedback": null,
    "files": [
      {
        "id": 1,
        "file_name": "research_paper.pdf",
        "file_size_kb": 2048,
        "file_size_mb": 2.0,
        "file_type": "application/pdf",
        "file_extension": "pdf",
        "created_at": "2026-04-14T10:30:00Z"
      }
    ],
    "grader": null,
    "graded_at": null,
    "created_at": "2026-04-14T10:00:00Z",
    "updated_at": "2026-04-14T10:30:00Z"
  }
}
```

#### Grade Submission
```http
POST /centers/{center}/submissions/{submission}/grade
```

**Request Body:**
```json
{
  "score": 85.00,
  "feedback": "Excellent work! Your analysis was thorough and well-structured."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Submission graded successfully",
  "data": {
    "id": 1,
    "score": 85.00,
    "score_after_penalty": 85.00,
    "passed": true,
    "feedback": "Excellent work!...",
    "status": 2,
    "status_label": "Graded",
    "graded_at": "2026-04-16T14:00:00Z"
  }
}
```

#### Return for Revision
```http
POST /centers/{center}/submissions/{submission}/return
```

**Request Body:**
```json
{
  "feedback": "Please revise section 3 with more detailed analysis."
}
```

#### Download Submission File
```http
GET /centers/{center}/submissions/{submission}/files/{file}/download
```

**Response:** Binary file download with appropriate Content-Type header

#### Get Assignment Statistics
```http
GET /centers/{center}/assignments/{assignment}/statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_students": 50,
    "submitted": 35,
    "pending_grading": 10,
    "graded": 20,
    "returned": 5,
    "not_submitted": 15,
    "late_submissions": 3,
    "average_score": 78.5,
    "pass_rate": 85.0
  }
}
```

---

## Mobile API - Quizzes

### Base URL
```
/api/v1
```

### List Course Quizzes
```http
GET /centers/{center}/courses/{course}/quizzes
```

**Requirements:** Student must be enrolled in the course.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Chapter 1 Quiz",
      "description": "Test your knowledge...",
      "attachable_type": "video",
      "attachable_id": 123,
      "passing_score": 70.00,
      "max_attempts": 3,
      "time_limit_minutes": 30,
      "is_required": true,
      "is_available": true,
      "remaining_attempts": 2,
      "best_score": 65.00,
      "can_attempt": true,
      "questions_count": 10
    }
  ]
}
```

### Get Quiz Info (Before Starting)
```http
GET /centers/{center}/quizzes/{quiz}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Chapter 1 Quiz",
    "description": "Test your knowledge...",
    "passing_score": 70.00,
    "max_attempts": 3,
    "time_limit_minutes": 30,
    "shuffle_questions": true,
    "shuffle_answers": true,
    "show_correct_answers": true,
    "show_score_immediately": true,
    "is_required": true,
    "is_available": true,
    "available_from": "2026-03-01T00:00:00Z",
    "available_until": "2026-06-30T23:59:59Z",
    "remaining_attempts": 2,
    "best_score": 65.00,
    "can_attempt": true,
    "total_questions": 10,
    "total_points": 10.00
  }
}
```

### Start Quiz Attempt
```http
POST /centers/{center}/quizzes/{quiz}/start
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Quiz attempt started",
  "data": {
    "id": 45,
    "quiz_id": 1,
    "quiz_title": "Chapter 1 Quiz",
    "attempt_number": 2,
    "status": 0,
    "status_label": "In Progress",
    "started_at": "2026-03-10T12:00:00Z",
    "time_limit_minutes": 30,
    "remaining_time_seconds": 1800,
    "total_questions": 10,
    "answered_questions": 0,
    "questions": [
      {
        "id": 1,
        "question": "What is the capital of France?",
        "question_type": 0,
        "question_type_label": "Single Choice",
        "points": 1.00,
        "is_answered": false,
        "selected_answer_ids": [],
        "answers": [
          {"id": 1, "answer": "Paris"},
          {"id": 2, "answer": "London"},
          {"id": 3, "answer": "Berlin"},
          {"id": 4, "answer": "Madrid"}
        ]
      }
    ]
  }
}
```

*Note: If student has an existing in-progress attempt, it will be resumed.*

### Get Current Attempt
```http
GET /centers/{center}/quiz-attempts/{attempt}
```

**Response:** Same format as start attempt response

### Save Answer
```http
POST /centers/{center}/quiz-attempts/{attempt}/answer
```

**Request Body:**
```json
{
  "question_id": 1,
  "answer_ids": [1]
}
```

*For multiple choice questions, `answer_ids` can contain multiple values.*

**Response:**
```json
{
  "success": true,
  "message": "Answer saved",
  "data": {
    "question_id": 1,
    "answer_ids": [1],
    "remaining_time_seconds": 1500
  }
}
```

### Submit Quiz
```http
POST /centers/{center}/quiz-attempts/{attempt}/submit
```

**Response:**
```json
{
  "success": true,
  "message": "Quiz submitted successfully",
  "data": {
    "id": 45,
    "quiz_id": 1,
    "quiz_title": "Chapter 1 Quiz",
    "attempt_number": 2,
    "status": 3,
    "status_label": "Graded",
    "started_at": "2026-03-10T12:00:00Z",
    "submitted_at": "2026-03-10T12:20:00Z",
    "time_spent_seconds": 1200,
    "score": 80.00,
    "points_earned": 8.00,
    "points_possible": 10.00,
    "passed": true,
    "passing_score": 70.00,
    "questions": [
      {
        "id": 1,
        "question": "What is the capital of France?",
        "points": 1.00,
        "points_earned": 1.00,
        "is_correct": true,
        "selected_answer_ids": [1],
        "correct_answer_ids": [1],
        "explanation": "Paris is the capital city of France.",
        "answers": [
          {"id": 1, "answer": "Paris", "is_correct": true},
          {"id": 2, "answer": "London", "is_correct": false},
          {"id": 3, "answer": "Berlin", "is_correct": false},
          {"id": 4, "answer": "Madrid", "is_correct": false}
        ]
      }
    ]
  }
}
```

*Note: `questions` array only included if `show_correct_answers` is enabled.*

### Get Attempt Results
```http
GET /centers/{center}/quiz-attempts/{attempt}/results
```

**Response:** Same format as submit response

### My Attempts History
```http
GET /centers/{center}/quizzes/{quiz}/my-attempts
```

**Response:**
```json
{
  "success": true,
  "data": {
    "attempts": [
      {
        "id": 45,
        "attempt_number": 2,
        "status": 3,
        "status_label": "Graded",
        "score": 80.00,
        "passed": true,
        "started_at": "2026-03-10T12:00:00Z",
        "submitted_at": "2026-03-10T12:20:00Z",
        "time_spent_seconds": 1200
      },
      {
        "id": 40,
        "attempt_number": 1,
        "status": 3,
        "status_label": "Graded",
        "score": 65.00,
        "passed": false,
        "started_at": "2026-03-08T10:00:00Z",
        "submitted_at": "2026-03-08T10:25:00Z",
        "time_spent_seconds": 1500
      }
    ],
    "best_score": 80.00,
    "remaining_attempts": 1
  }
}
```

---

## Mobile API - Assignments

### List Course Assignments
```http
GET /centers/{center}/courses/{course}/assignments
```

**Requirements:** Student must be enrolled in the course.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Research Paper",
      "description": "Write a research paper...",
      "attachable_type": "section",
      "attachable_id": 5,
      "submission_types": ["file", "text"],
      "is_group_assignment": false,
      "max_points": 100.00,
      "passing_score": 60.00,
      "is_required": true,
      "due_date": "2026-04-15T23:59:59Z",
      "is_past_due": false,
      "late_submission_allowed": true,
      "is_available": true,
      "can_submit": true,
      "submission_status": 0,
      "submission_status_label": "Draft",
      "score": null,
      "passed": null
    }
  ]
}
```

### Get Assignment Details
```http
GET /centers/{center}/assignments/{assignment}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Research Paper",
    "description": "Write a research paper on the topic...",
    "attachable_type": "section",
    "attachable_id": 5,
    "submission_types": ["file", "text"],
    "allowed_file_types": ["pdf", "doc", "docx"],
    "max_file_size_mb": 10,
    "max_files": 3,
    "is_group_assignment": false,
    "max_group_size": null,
    "max_points": 100.00,
    "passing_score": 60.00,
    "is_required": true,
    "due_date": "2026-04-15T23:59:59Z",
    "is_past_due": false,
    "is_late": false,
    "late_submission_allowed": true,
    "late_penalty_percent": 10.00,
    "available_from": "2026-03-15T00:00:00Z",
    "available_until": "2026-04-30T23:59:59Z",
    "is_available": true,
    "can_submit": true,
    "my_submission": null
  }
}
```

### Create/Update Submission Draft
```http
POST /centers/{center}/assignments/{assignment}/submissions
```

**Request Body:**
```json
{
  "submission_type": 1,
  "text_content": "This is my research paper content...",
  "link_url": null
}
```

*All fields are optional. If submission exists and is draft/returned, it will be updated.*

**Response:** `201 Created` or `200 OK`
```json
{
  "success": true,
  "message": "Draft created",
  "data": {
    "id": 1,
    "assignment_id": 5,
    "submission_type": 1,
    "submission_type_label": "Text",
    "text_content": "This is my research paper content...",
    "link_url": null,
    "status": 0,
    "status_label": "Draft",
    "submitted_at": null,
    "is_late": false,
    "days_late": 0,
    "score": null,
    "feedback": null,
    "files": [],
    "created_at": "2026-04-10T10:00:00Z",
    "updated_at": "2026-04-10T10:00:00Z"
  }
}
```

### Upload File
```http
POST /centers/{center}/submissions/{submission}/files
Content-Type: multipart/form-data
```

**Request Body:**
- `file`: The file to upload (binary)

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "file_name": "research_paper.pdf",
    "file_size_kb": 2048,
    "file_type": "application/pdf"
  }
}
```

### Remove File
```http
DELETE /centers/{center}/submissions/{submission}/files/{file}
```

**Response:** `200 OK`

### Submit Assignment
```http
POST /centers/{center}/submissions/{submission}/submit
```

**Response:**
```json
{
  "success": true,
  "message": "Assignment submitted successfully",
  "data": {
    "id": 1,
    "status": 1,
    "status_label": "Submitted",
    "submitted_at": "2026-04-14T10:30:00Z",
    "is_late": false,
    "days_late": 0
  }
}
```

### Get My Submission
```http
GET /centers/{center}/assignments/{assignment}/my-submission
```

---

### Group Assignments

#### List Available Groups
```http
GET /centers/{center}/assignments/{assignment}/groups
```

**Response:**
```json
{
  "success": true,
  "data": {
    "groups": [
      {
        "id": 1,
        "name": "Team Alpha",
        "creator": {"id": 10, "name": "John Doe"},
        "members_count": 3,
        "members": [
          {"user_id": 10, "name": "John Doe", "role": "Leader", "joined_at": "..."},
          {"user_id": 11, "name": "Jane Smith", "role": "Member", "joined_at": "..."}
        ],
        "has_submission": false,
        "created_at": "2026-04-01T10:00:00Z"
      }
    ],
    "my_group_id": 1,
    "max_group_size": 5
  }
}
```

#### Create Group
```http
POST /centers/{center}/assignments/{assignment}/groups
```

**Request Body:**
```json
{
  "name": "Team Alpha"
}
```

**Response:** `201 Created` with group details

#### Get Group Details
```http
GET /centers/{center}/assignment-groups/{group}
```

#### Join Group
```http
POST /centers/{center}/assignment-groups/{group}/join
```

**Response:**
```json
{
  "success": true,
  "message": "Joined group successfully",
  "data": {...}
}
```

#### Leave Group
```http
POST /centers/{center}/assignment-groups/{group}/leave
```

---

## Enums & Constants

### Question Type
| Value | Label | Description |
|-------|-------|-------------|
| 0 | Single Choice | One correct answer |
| 1 | Multiple Choice | Multiple correct answers |

### Attempt Score Policy
| Value | Label | Description |
|-------|-------|-------------|
| 0 | Best | Keep highest score |
| 1 | Latest | Keep most recent score |
| 2 | Average | Average of all attempts |

### Quiz Attempt Status
| Value | Label | Description |
|-------|-------|-------------|
| 0 | In Progress | Currently taking quiz |
| 1 | Submitted | Submitted, pending grade |
| 2 | Timed Out | Auto-submitted due to time limit |
| 3 | Graded | Graded and scored |

### Submission Type
| Value | Label | Description |
|-------|-------|-------------|
| 0 | File | File upload |
| 1 | Text | Text response |
| 2 | Link | URL submission |

### Submission Status
| Value | Label | Description |
|-------|-------|-------------|
| 0 | Draft | Not yet submitted |
| 1 | Submitted | Submitted, pending review |
| 2 | Graded | Graded with score |
| 3 | Returned | Returned for revision |

### AI Generation Status
| Value | Label | Description |
|-------|-------|-------------|
| 0 | Pending | Queued for processing |
| 1 | Processing | Currently generating |
| 2 | Completed | Successfully generated |
| 3 | Failed | Generation failed |

---

## Error Codes

### Common Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `UNAUTHORIZED` | 403 | User not authorized |
| `NOT_FOUND` | 404 | Resource not found |
| `NOT_ENROLLED` | 403 | Student not enrolled in course |
| `VALIDATION_ERROR` | 422 | Invalid request data |

### Quiz Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `NOT_AVAILABLE` | 400 | Quiz not available (outside date range) |
| `NO_ATTEMPTS_LEFT` | 400 | Maximum attempts reached |
| `ATTEMPT_CLOSED` | 400 | Attempt already submitted |
| `TIME_EXPIRED` | 400 | Time limit exceeded |
| `INVALID_QUESTION` | 400 | Question not part of quiz |
| `ALREADY_SUBMITTED` | 400 | Attempt already submitted |
| `NOT_SUBMITTED` | 400 | Attempt not yet submitted |

### Assignment Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `NOT_AVAILABLE` | 400 | Assignment not available |
| `CANNOT_SUBMIT` | 400 | Cannot submit (past deadline, no late allowed) |
| `ALREADY_SUBMITTED` | 400 | Already submitted |
| `CANNOT_MODIFY` | 400 | Cannot modify submitted assignment |
| `FILE_LIMIT_REACHED` | 400 | Maximum files reached |
| `INVALID_FILE_TYPE` | 400 | File type not allowed |
| `FILE_TOO_LARGE` | 400 | File exceeds size limit |
| `EMPTY_SUBMISSION` | 400 | No content in submission |
| `NO_SUBMISSION` | 404 | No submission found |

### Group Assignment Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `NOT_GROUP_ASSIGNMENT` | 400 | Not a group assignment |
| `ALREADY_IN_GROUP` | 400 | Already member of a group |
| `GROUP_FULL` | 400 | Group at capacity |
| `NOT_IN_GROUP` | 400 | Not a member of this group |

---

## Scheduled Jobs

### Auto-Submit Timed Out Attempts
The system runs a scheduled job every minute to auto-submit quiz attempts that have exceeded their time limit.

**Job:** `AutoSubmitTimedOutAttempts`
**Frequency:** Every minute
**Action:** Submits and grades all in-progress attempts past their time limit

---

## Notes for Implementation

### Frontend (Admin)

1. **Quiz Builder UI:**
   - Support drag-and-drop question reordering
   - Preview mode for quiz before publishing
   - Batch question import/export

2. **AI Generation Flow:**
   - Show progress indicator while job is processing
   - Allow selecting which generated questions to approve
   - Edit questions before approval

3. **Grading Interface:**
   - Inline submission preview
   - File download links
   - Quick grade with rubric templates

### Mobile

1. **Quiz Taking:**
   - Handle timer countdown in-app
   - Save answers immediately (don't wait for explicit save)
   - Handle network interruption gracefully
   - Show progress indicator

2. **File Upload:**
   - Support multiple file selection
   - Show upload progress
   - Preview files before submission
   - Handle large file uploads with chunking if needed

3. **Group Assignments:**
   - Real-time group member updates (if using websockets)
   - Clear visual indication of group status
