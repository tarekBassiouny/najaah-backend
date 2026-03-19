# Mobile Course Assets Frontend Handoff

## Purpose

This document defines the mobile consume flow for all course assets after the unified mobile asset API rollout.

Use it as the frontend reference for:
- course asset discovery
- asset detail rendering
- quiz and assignment submission flows
- AI-generated asset consumption for summary, flashcards, and interactive activity

## Canonical Mobile Endpoints

### Discovery

- `GET /api/v1/centers/{center}/courses/{course}/assets`
- `GET /api/v1/centers/{center}/assets/{type}/{id}`
- `POST /api/v1/centers/{center}/assets/{type}/{id}/progress` for `summary`, `flashcards`, and `interactive_activity`

### Quiz Actions

- `POST /api/v1/centers/{center}/assets/quiz/{quiz}/attempts`
- `GET /api/v1/centers/{center}/assets/quiz/attempts/{attempt}`
- `POST /api/v1/centers/{center}/assets/quiz/attempts/{attempt}/answers`
- `POST /api/v1/centers/{center}/assets/quiz/attempts/{attempt}/submit`
- `GET /api/v1/centers/{center}/assets/quiz/attempts/{attempt}/results`

### Assignment Actions

- `POST /api/v1/centers/{center}/assets/assignment/{assignment}/submission`
- `POST /api/v1/centers/{center}/assets/assignment/submissions/{submission}/files`
- `DELETE /api/v1/centers/{center}/assets/assignment/submissions/{submission}/files/{file}`
- `POST /api/v1/centers/{center}/assets/assignment/submissions/{submission}/submit`

### Assignment Group Actions

- `GET /api/v1/centers/{center}/assets/assignment/{assignment}/groups`
- `POST /api/v1/centers/{center}/assets/assignment/{assignment}/groups`
- `GET /api/v1/centers/{center}/assets/assignment/groups/{group}`
- `POST /api/v1/centers/{center}/assets/assignment/groups/{group}/join`
- `POST /api/v1/centers/{center}/assets/assignment/groups/{group}/leave`

## Product Rule

Mobile should treat every course asset as one of two behaviors:

1. Read-only content
2. Actionable assessment

Read-only content:
- `summary`
- `flashcards`
- `interactive_activity`

Actionable assessment:
- `quiz`
- `assignment`

Important:
- AI-generated assets do not automatically mean submit-able assets.
- `summary`, `flashcards`, and `interactive_activity` are currently publish-and-consume assets.
- Only `quiz` and `assignment` have backend submission/action flows today.

## Recommended Mobile UX

### Course Assets Screen

Use one course screen fed by `GET /courses/{course}/assets`.

Each card should share the same base shell:
- title
- short description
- asset type badge
- availability state
- primary CTA
- optional secondary status line from `meta`

Recommended CTA by type:
- `summary` -> `Read`
- `flashcards` -> `Study`
- `interactive_activity` -> `Open`
- `quiz` -> `Start Quiz` or `Continue Quiz`
- `assignment` -> `Start Assignment` or `Continue Submission`

Recommended status line by type:
- `quiz` -> remaining attempts, best score
- `assignment` -> due date, submission status
- `flashcards` -> cards count
- `summary` -> short preview
- `interactive_activity` -> activity kind

### Asset Detail Shell

Use one shared detail screen driven by `GET /assets/{type}/{id}`.

Shared layout:
- header with title and type
- optional description
- availability/warning banner
- type-specific body renderer
- sticky primary action footer when the asset is actionable

## Consume Flow by Asset Type

### Summary

Backend contract:
- read from `payload.content`
- use `payload.payload` only for structured extras if present

UX:
- open into a clean reading screen
- show estimated reading length if frontend can infer it
- no submit button
- allow local-only save/share/bookmark UI if product wants it

Current backend note:
- mobile can persist summary progress through `POST /assets/summary/{id}/progress`
- use `completed=true` when the student finishes reading

### Flashcards

Backend contract:
- render from `payload.payload.cards` when available
- use `meta.cards_count` in the list

UX:
- open into full-screen deck mode
- show progress in deck locally: `current / total`
- support flip card, next, previous, shuffle locally
- primary CTA on list is `Study`
- no backend submit button

Current backend note:
- flashcards are consumable AI-generated content, not a submitted asset
- mobile can persist deck progress and resume state through `POST /assets/flashcards/{id}/progress`

### Interactive Activity

Backend contract:
- render from `payload.payload`
- use `meta.activity_kind` for previews

UX:
- open into one activity player shell
- render different UI blocks by `activity_kind`
- keep the action label generic: `Open`

Current backend note:
- this is currently a published learning asset
- mobile can persist lightweight progress through `POST /assets/interactive_activity/{id}/progress`
- if an activity later becomes gradable, add explicit action endpoints instead of overloading the read contract

### Quiz

Entry flow:
1. fetch `GET /assets/quiz/{id}`
2. inspect `payload.can_attempt`, `payload.remaining_attempts`, `payload.attempts`, `payload.stats`
3. choose CTA:
   - in-progress attempt exists -> resume flow
   - attempts available -> start flow
   - no attempts -> show locked/exhausted state

Submission flow:
1. `POST /assets/quiz/{quiz}/attempts`
2. render returned attempt
3. save answers through `POST /assets/quiz/attempts/{attempt}/answers`
4. submit through `POST /assets/quiz/attempts/{attempt}/submit`
5. show results through `GET /assets/quiz/attempts/{attempt}/results`

UX recommendations:
- if `payload.attempts` contains an in-progress attempt, surface `Continue Quiz`
- keep timers only inside attempt UI, not in list cards
- show results only after submit or when backend allows it

### Assignment

Entry flow:
1. fetch `GET /assets/assignment/{id}`
2. inspect `payload.can_submit`, `payload.my_submission`, due-date fields, and group flags
3. choose CTA:
   - no submission -> `Start Assignment`
   - draft exists -> `Continue Submission`
   - submitted -> `View Submission`

Submission flow:
1. create or fetch draft through `payload.my_submission` or `POST /assets/assignment/{assignment}/submission`
2. upload files through `POST /assets/assignment/submissions/{submission}/files`
3. remove files through `DELETE /assets/assignment/submissions/{submission}/files/{file}`
4. finalize through `POST /assets/assignment/submissions/{submission}/submit`

Group flow:
1. if `payload.is_group_assignment` is true, show `Group Options`
2. list groups through `GET /assets/assignment/{assignment}/groups`
3. create group through `POST /assets/assignment/{assignment}/groups`
4. open one group through `GET /assets/assignment/groups/{group}`
5. join or leave through `POST /assets/assignment/groups/{group}/join` and `POST /assets/assignment/groups/{group}/leave`

UX recommendations:
- group selection should happen before file upload/submit
- if `payload.my_submission` exists, treat it as the source of truth for draft state
- keep submission editor simple: text area, files list, submit CTA

## Unified Rendering Strategy

Frontend should not branch at the route level anymore. It should branch only after reading `type`.

Recommended renderer map:

- `summary` -> `SummaryAssetScreen`
- `flashcards` -> `FlashcardsAssetScreen`
- `interactive_activity` -> `InteractiveAssetScreen`
- `quiz` -> `QuizAssetScreen`
- `assignment` -> `AssignmentAssetScreen`

The list screen should push one detail route shape:

- `/course-assets/:type/:id`

Then the screen loads the canonical backend detail endpoint.

## What Mobile Should Not Assume

- Do not assume AI-generated assets are always interactive.
- Do not assume every asset has a submit lifecycle.
- Do not assume `payload.payload` has the same shape across summary, flashcards, and interactive activity.
- Do not assume list order is grouped by type; render in backend order.

## Progress Payload Contract

For `summary`, `flashcards`, and `interactive_activity`, the detail payload now includes:

```json
{
  "progress": {
    "status": "not_started|in_progress|completed",
    "status_label": "Not Started",
    "progress_percent": 0,
    "is_completed": false,
    "state": null,
    "started_at": null,
    "last_interacted_at": null,
    "completed_at": null
  }
}
```

Write contract:

```json
POST /api/v1/centers/{center}/assets/{type}/{id}/progress
{
  "progress_percent": 40,
  "completed": false,
  "state": {
    "current_card_index": 4
  }
}
```

Usage rules:
- summaries usually send `completed=true` when finished
- flashcards send `progress_percent` plus resume state
- interactive activity sends `progress_percent` plus lightweight local state
- quizzes and assignments do not use this endpoint
