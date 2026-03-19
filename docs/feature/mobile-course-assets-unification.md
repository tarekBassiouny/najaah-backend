# Mobile Course Assets Unification

## Status

- Implemented
- Unified mobile asset surface is the canonical contract
- Legacy per-type read endpoints were removed
- Quiz and assignment student actions now live under the same `/assets/...` namespace
- Assignment group collaboration now lives under the same `/assets/assignment/...` namespace
- Learning-asset progress is now persisted through the same `/assets/{type}/{id}/progress` route family

## Purpose

The mobile API currently exposes course assets through multiple disconnected read routes:
- quizzes
- assignments
- summaries
- flashcards
- interactive activities

Each family uses a different controller path and a different response shape. This makes the mobile app harder to maintain and creates gaps for AI-generated assets after publication.

This document defines the unified mobile read API that becomes the single source of truth for course assets.

## Problem

Current mobile asset discovery is fragmented:
- `GET /centers/{center}/courses/{course}/quizzes`
- `GET /centers/{center}/quizzes/{quiz}`
- `GET /centers/{center}/courses/{course}/assignments`
- `GET /centers/{center}/assignments/{assignment}`
- `GET /centers/{center}/courses/{course}/summaries`
- `GET /centers/{center}/summaries/{asset}`
- `GET /centers/{center}/courses/{course}/flashcards`
- `GET /centers/{center}/flashcards/{asset}`
- `GET /centers/{center}/courses/{course}/interactive-activities`
- `GET /centers/{center}/interactive-activities/{asset}`

This causes four problems:
- mobile has to know backend-specific route families for each asset type
- response contracts are inconsistent
- AI-generated assets are harder to discover uniformly
- adding a new asset type becomes an API multiplication problem

## Goal

Replace fragmented mobile course-asset read routes with a minimal unified API:

1. one list endpoint for all course assets
2. one detail endpoint for any asset type

Quiz attempts, assignment submission actions, assignment group collaboration, and learning-asset progress remain separate by behavior, but they live under the same `/assets/...` route family.

## Non-Goals

- No change to admin CRUD routes in this phase
- No asset-type-specific mobile UI decisions in this spec
- No parallel long-term legacy read API

## Asset Types In Scope

The unified API must support:
- `quiz`
- `assignment`
- `summary`
- `flashcards`
- `interactive_activity`

These map to current persisted models as follows:
- `quiz` -> `Quiz`
- `assignment` -> `Assignment`
- `summary` -> `LearningAsset` with `asset_type=summary`
- `flashcards` -> `LearningAsset` with `asset_type=flashcards`
- `interactive_activity` -> `LearningAsset` with `asset_type=interactive_activity`

## Source of Truth

The unified mobile asset API becomes the only supported read surface for mobile course assets.

Read endpoints listed in the Problem section should be removed after the mobile client is migrated.

The unified endpoints are now the only canonical mobile read source.

## Target Endpoints

### 1. List Course Assets

`GET /api/v1/centers/{center}/courses/{course}/assets`

Purpose:
- return all visible assets for a course in one normalized list

Supported query parameters:
- `type` optional: filter to one asset type

### 2. Show Asset Detail

`GET /api/v1/centers/{center}/assets/{type}/{id}`

Purpose:
- return normalized detail for any asset type using one route pattern

Route parameter `type` allowed values:
- `quiz`
- `assignment`
- `summary`
- `flashcards`
- `interactive_activity`

## Access Rules

Both unified endpoints must enforce:
- authenticated student only
- center ownership check
- enrollment check
- asset visibility and active/publication rules

Expected behavior:
- not enrolled -> `403`
- wrong center or inaccessible asset -> `404`
- asset exists but is not currently available for use -> include availability flags in list results, but detail can still return `400` or `404` depending on existing product behavior

Implementation should standardize these rules instead of having each asset controller interpret them differently.

## Normalized List Contract

Each list item should return the same top-level shape regardless of asset type.

```json
{
  "id": 123,
  "type": "quiz",
  "title": "Chapter 1 Quiz",
  "description": "Practice the first lesson",
  "attachable_type": "video",
  "attachable_id": 55,
  "is_available": true,
  "is_required": false,
  "order_index": 1,
  "published_at": "2026-03-19T10:00:00Z",
  "meta": {}
}
```

### Required Top-Level Fields

- `id`
- `type`
- `title`
- `description`
- `attachable_type`
- `attachable_id`
- `is_available`
- `is_required`
- `order_index`
- `published_at`
- `meta`

### `meta` by Asset Type

#### Quiz

```json
{
  "questions_count": 10,
  "remaining_attempts": 2,
  "best_score": 80,
  "can_attempt": true,
  "time_limit_minutes": 20
}
```

#### Assignment

```json
{
  "submission_types": [0, 1],
  "can_submit": true,
  "due_date": "2026-03-25T10:00:00Z",
  "submission_status": 1,
  "submission_status_label": "Draft",
  "score": null,
  "passed": null
}
```

#### Summary

```json
{
  "content_preview": "Short preview text"
}
```

#### Flashcards

```json
{
  "cards_count": 12
}
```

#### Interactive Activity

```json
{
  "activity_kind": "matching"
}
```

The exact lightweight fields can evolve, but the `meta` container should remain the type-specific extension point.

## Normalized Detail Contract

The detail endpoint should keep one stable top-level shape and place type-specific detail inside a dedicated payload key.

```json
{
  "id": 123,
  "type": "quiz",
  "title": "Chapter 1 Quiz",
  "description": "Practice the first lesson",
  "attachable_type": "video",
  "attachable_id": 55,
  "is_available": true,
  "is_required": false,
  "order_index": 1,
  "published_at": "2026-03-19T10:00:00Z",
  "meta": {},
  "payload": {}
}
```

### Required Top-Level Fields

- everything from list shape
- `payload`

### `payload` by Asset Type

#### Quiz

Should include quiz info needed before starting:
- `passing_score`
- `max_attempts`
- `time_limit_minutes`
- `shuffle_questions`
- `shuffle_answers`
- `show_correct_answers`
- `show_score_immediately`
- `available_from`
- `available_until`
- `remaining_attempts`
- `best_score`
- `can_attempt`
- `total_questions`
- `total_points`

The detail endpoint should not replace attempt-start or attempt-show endpoints.

#### Assignment

Should include:
- `submission_types`
- `allowed_file_types`
- `max_file_size_mb`
- `max_files`
- `is_group_assignment`
- `max_group_size`
- `max_points`
- `passing_score`
- `due_date`
- `is_past_due`
- `is_late`
- `late_submission_allowed`
- `late_penalty_percent`
- `available_from`
- `available_until`
- `can_submit`
- `my_submission`

#### Learning Assets

For `summary`, `flashcards`, and `interactive_activity`, payload should include:
- `content`
- `payload`

Where:
- `content` is the translated primary content string when present
- `payload` is the structured stored payload for cards/activity/body data

## Ordering Rules

The list endpoint should return assets in a course-consumable order.

Recommended ordering:
1. by section order where applicable
2. by attachable order in course structure
3. by asset `order_index`
4. stable tie-breaker by id

This should not be left to each model independently.

## AI-Generated Assets Requirement

Any asset published through the AI content flow must appear in the unified mobile assets API automatically.

This is a hard requirement.

That means:
- AI-published quizzes must appear as `type=quiz`
- AI-published assignments must appear as `type=assignment`
- AI-published summaries, flashcards, and interactive activities must appear as their asset types

The unified service must not exclude `interactive_activity`.

## Backend Architecture

### New Service

Create a single orchestrating read service, for example:

`MobileCourseAssetService`

Responsibilities:
- fetch all course assets across models
- apply access and visibility rules
- normalize all asset types into one list/detail contract
- centralize type-specific metadata assembly

### Type Adapters

Do not let controllers build ad hoc arrays.

Use internal type normalizers/adapters, for example:
- `QuizMobileAssetAdapter`
- `AssignmentMobileAssetAdapter`
- `LearningAssetMobileAssetAdapter`

Each adapter should expose:
- list normalization
- detail normalization
- availability evaluation support if needed

### New Resources

Create:
- one mobile asset list resource
- one mobile asset detail resource

These resources should accept already-normalized arrays or dedicated DTOs rather than raw Eloquent models from unrelated tables.

## Route Strategy

### New Canonical Routes

Add:
- `GET /centers/{center}/courses/{course}/assets`
- `GET /centers/{center}/assets/{type}/{id}`

### Action Route Handling

Behavior-specific routes still exist for submit/join workflows, but they should stay inside the unified asset namespace.

Canonical action route families:
- `/centers/{center}/assets/quiz/...`
- `/centers/{center}/assets/assignment/...`
- `/centers/{center}/assets/{type}/{id}/progress` for `summary|flashcards|interactive_activity`

## Controller Strategy

Preferred controller design:
- one `MobileCourseAssetController@index`
- one `MobileCourseAssetController@show`

Avoid separate list/show controllers for each asset type once the unified API exists.

## Error Contract

Unified endpoints should return the standard mobile envelope:

Success:
```json
{
  "success": true,
  "message": "Operation completed",
  "data": []
}
```

Failure:
```json
{
  "success": false,
  "error": {
    "code": "NOT_ENROLLED",
    "message": "You are not enrolled in this course."
  }
}
```

Standardize these codes:
- `UNAUTHORIZED`
- `NOT_ENROLLED`
- `NOT_FOUND`
- `NOT_AVAILABLE`

## Migration Plan

### Phase 1

- implement unified service
- implement unified resources
- add canonical routes
- add tests for all asset types

### Phase 2

- switch mobile app to unified routes only
- verify AI-generated assets are discoverable through unified endpoints

### Phase 3

- remove fragmented mobile read routes
- keep only action routes for quiz attempts, assignment submissions, assignment groups, and learning-asset progress

## Tests

Must add coverage for:
- list all asset types in one course response
- filter by `type`
- detail response for each asset type
- enrollment and center access rules
- AI-published quiz visibility
- AI-published assignment visibility
- AI-published summary visibility
- AI-published flashcards visibility
- AI-published interactive activity visibility

## Acceptance Criteria

- mobile can fetch all course assets from one list endpoint
- mobile can fetch any single asset from one detail endpoint
- response contracts are normalized across all asset types
- AI-generated assets are visible without special-case routing
- `interactive_activity` is supported end to end
- fragmented mobile read routes are no longer the source of truth

## Immediate Implementation Notes

Before coding, the team should accept these decisions:
- one canonical list endpoint
- one canonical detail endpoint
- no separate read APIs per asset type going forward
- attempts and submissions remain separate action routes

This spec is the implementation target for the next backend cleanup phase.
