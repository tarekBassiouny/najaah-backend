# Student & Parent Web Portal — Parent Web API Contract

## Status: Draft
## Source Phase: 4 — Parent Web Portal
## Audience: Portal frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the read-only parent portal API for linked-student visibility, progress, quiz review, assignments, and activity.

---

## Auth

- Guard: `jwt.web.parent`
- Access is center-scoped
- Parent authorization always depends on an active `parent_student_links` row

---

## Expected Endpoints

| Method | Path | Purpose | Status |
|--------|------|---------|--------|
| `GET` | `/api/v1/web/parent/students` | List linked students | planned |
| `GET` | `/api/v1/web/parent/students/{student}` | View linked student detail | planned |
| `POST` | `/api/v1/web/parent/links` | Request a new link | planned |
| `GET` | `/api/v1/web/parent/links` | View current or pending links | planned |
| `GET` | `/api/v1/web/parent/students/{student}/enrollments` | Student enrollments | planned |
| `GET` | `/api/v1/web/parent/students/{student}/progress` | Course or asset progress | planned |
| `GET` | `/api/v1/web/parent/students/{student}/quiz-attempts` | Quiz attempts list | planned |
| `GET` | `/api/v1/web/parent/quiz-attempts/{attempt}` | Full quiz attempt review | planned |
| `GET` | `/api/v1/web/parent/students/{student}/assignments` | Assignment status and grades | planned |
| `GET` | `/api/v1/web/parent/students/{student}/activity/weekly` | Weekly activity summary | planned |

---

## Read Rules

- Parents can view linked student profile and academic progress.
- Parents cannot start playback, access PDFs directly, submit quizzes, submit assignments, or mutate student settings.
- Quiz review must respect the quiz `show_correct_answers` policy.

---

## Linking Rules

- Auto-link is a convenience only; authorization comes from the pivot table.
- Parent link requests should be rate-limited.
- Unbranded-center auto-linking may create multiple center-scoped rows for one parent.

---

## Open Items

- Confirm final path naming for parent resources to keep frontend route mapping stable.
- Confirm whether student detail and progress are split endpoints or a bootstrap dashboard endpoint plus detail endpoints.
- Confirm exact audit/event side effects that should be exposed to frontend as status messaging.

