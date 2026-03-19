# Course Asset AI Generation Dashboard Handoff

## Purpose

This document is the frontend-facing handoff for the admin dashboard flow that generates course assets from course sources.

It focuses on the current backend reality for:
- asset catalog rendering
- AI generation actions
- polling and review
- publish workflow
- dashboard UX expectations

Use this doc as the primary reference for the course asset AI dashboard.

Related docs:
- `/docs/features/ai_content_generation_enhancement_frontend_handoff.md`
- `/docs/features/course_asset_authoring_admin_ux_handoff.md`
- `/docs/features/course_asset_authoring_frontend_implementation_checklist.md`

## Current Backend Status

- AI content generation Phases 0 through 4 are implemented
- course asset builder backend exists
- the dashboard can be implemented now
- auto-transcription is deferred
- mobile route unification is a separate follow-up

## What Changed For The Dashboard

### 1. Generation is now a real job workflow

The dashboard must treat asset generation as a multi-step job lifecycle:
- create job or batch
- poll jobs
- review generated payload
- approve
- publish

This is not a fire-and-forget action anymore.

Relevant endpoints:
- `GET /api/v1/admin/centers/{center}/courses/{course}/asset-catalog`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs`
- `POST /api/v1/admin/centers/{center}/ai-content/batches`
- `GET /api/v1/admin/centers/{center}/ai-content/jobs`
- `GET /api/v1/admin/centers/{center}/ai-content/jobs/{job}`
- `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/approve`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/publish`
- `DELETE /api/v1/admin/centers/{center}/ai-content/jobs/{job}`

### 2. Source readiness is now part of the UX

Direct generation from raw media is stricter:
- `video` requires a stored transcript
- `pdf` requires completed text extraction

The dashboard should not let users start generation blindly. It must show readiness clearly.

### 3. Language is now part of generation

Every AI job now has `language`:
- `ar`
- `en`
- `both`

This affects both generation and review payload shape.

### 4. Validation warnings now exist

Jobs may return `validation_warnings`.

This means:
- first AI output may have failed validation
- backend retried automatically once
- frontend should show a warning, not assume everything is perfect because status is `Completed`

### 5. Batch generation supports more targets than older docs assumed

Current backend target support includes:
- `summary`
- `quiz`
- `flashcards`
- `assignment`
- `interactive_activity`

Important current dashboard caveat:
- the source asset catalog service does **not** include `interactive_activity` slots yet
- so `interactive_activity` is supported in AI jobs, but not fully represented in the current course asset grid

Frontend should not invent a grid slot for it unless backend catalog support is added.

## Dashboard Entry Point

Primary page shape:
- one course page
- one source-bound asset catalog grid
- one generation modal
- one progress drawer
- one review drawer

The dashboard should treat `asset-catalog` as the source of truth for slot rendering.

## Asset Catalog Contract

Current endpoint:
- `GET /api/v1/admin/centers/{center}/courses/{course}/asset-catalog`

Current response shape:
- `course`
- `sources[]`

Each source includes:
- `type`
- `id`
- `title`
- `order_index`
- `ai_readiness`
- `section`
- `assets[]`

Each asset slot includes:
- `asset_type`
- `slot_state`
- `canonical`
- `latest_job`

Example slot states already exercised by tests:
- `missing`
- `draft`
- `generating`
- `published`

The frontend should not infer slot state from raw jobs. Use `slot_state` directly.

`ai_readiness` is the source-of-truth contract for disabling generation before submit.

`ai_readiness` includes:
- `is_ready`
- `code`
- `badge`
- `title`
- `message`

## Source Row UX

Each source row should display:
- source title
- source type badge: `video` or `pdf`
- parent section title when present
- source readiness badges
- one slot card per supported asset type in the grid

### Readiness Badges

For video:
- `Transcript Ready`
- `Transcript Missing`

For PDF:
- `Extraction Pending`
- `Extraction Processing`
- `Extraction Ready`
- `Extraction Failed`

These should be visible before opening generation modals.

For transcript-missing videos, the current backend message is:
- title: `Transcript required`
- message: `This video is not AI-ready yet. Add a transcript from the Videos page before generating assets.`

Frontend rule:
- if `ai_readiness.is_ready === false`, disable the `Generate` action
- show `ai_readiness.badge` on the source row
- show `ai_readiness.title` and `ai_readiness.message` in the empty/blocked state without inventing different copy

## Slot Card UX

Current dashboard slot types:
- `summary`
- `quiz`
- `flashcards`
- `assignment`

Each slot card should show:
- asset type label
- `slot_state`
- canonical title if one exists
- latest job badge if one exists
- primary action
- secondary actions where applicable

### Action Matrix

#### `missing`
- `Generate`
- `Create Manually` only for `quiz` and `assignment`

#### `generating`
- `View Progress`

#### `draft`
- `Review Draft`
- `Edit` if canonical draft exists
- `Regenerate`

#### `approved`
- `Publish`
- `Review`
- `Regenerate`

#### `published`
- `View`
- `Edit`
- `Regenerate`

#### `failed`
- `Retry`
- `View Error`

If backend returns `Pending` with an `error_message`, treat this as retrying/transient, not immediately failed.

## Generation Modal

The modal should be source-bound.

Do not let users change source inside the modal in the first implementation.

Show:
- source title
- source type
- section title
- readiness state

### Supported Generation Inputs

Single job:
- source type
- source id
- target type
- target id optional for regenerate/update
- language
- provider/model optional
- `generation_config`

Batch:
- one source
- multiple assets
- one language
- per-asset provider/model optional
- per-asset `generation_config`

### Recommended UX Defaults

- default `language` to `ar`
- expose `both` only where review UI can handle bilingual payloads correctly
- keep provider/model in an advanced section unless the product explicitly needs it

## Review Drawer

The review drawer must be type-aware and language-aware.

### Type-Aware Forms

Use dedicated forms for:
- summary
- flashcards
- quiz
- assignment

Avoid a raw JSON-first editor.

### Language-Aware Editing

For `ar` and `en` jobs:
- text fields remain plain strings

For `both` jobs:
- text fields become `{ ar, en }`

Recommended UI:
- locale tabs
- same structural form
- switch visible locale values without flattening the underlying payload

### Validation Warning Panel

If `validation_warnings` is non-null:
- show a warning panel above the review form
- do not block review automatically
- explain that backend retried the AI response after schema/content issues

Suggested copy:
- `The first AI response needed correction and was retried automatically. Review this asset carefully before publishing.`

## Job Polling

Batch polling uses:
- `GET /api/v1/admin/centers/{center}/ai-content/jobs?batch_key={uuid}`

There is no separate batch detail endpoint.

The progress drawer should show:
- one row per target asset
- status
- status label
- error state
- warning state

### Important Status Handling

Treat these states distinctly:
- `Pending`
- `Processing`
- `Completed`
- `Approved`
- `Published`
- `Failed`

Special case:
- `Pending` plus recent `error_message` means retrying/transient provider failure

## Publish Flow

Publish is explicit.

The dashboard must not assume `Completed` means live content.

Required sequence:
1. generation completes
2. admin reviews
3. admin approves
4. admin publishes

After publish:
- refetch job
- refetch `asset-catalog`
- update slot state

## Current Backend Gaps The Frontend Must Respect

### 1. `interactive_activity` catalog slot is not present yet

Current `CourseAssetCatalogService` only models:
- `summary`
- `quiz`
- `flashcards`
- `assignment`

Do not add an `interactive_activity` slot to the grid until backend catalog support is added.

### 2. Asset catalog is still video/pdf oriented

Core AI generation supports `section` and `course`, but the current course asset dashboard/catalog remains source-bound to video and PDF rows.

Frontend should build the dashboard against current catalog reality, not the broader AI job capabilities.

### 3. Manual create only exists for quiz and assignment

Do not add fake manual create buttons for:
- summary
- flashcards
- interactive_activity

## Recommended Dashboard Layout

### Left Side
- course structure tree
- grouped by section
- source rows under each section

### Main Grid
- one row per source
- one card per slot

### Right Panel / Drawer
- progress
- review
- publish

### Sticky Elements
- sticky section headers
- sticky action bar in review drawer

## Recommended Visual Hierarchy

Use color and badges intentionally:
- readiness badges on source row
- slot state badges on asset cards
- validation warnings in amber
- provider/transient failures in blue or neutral warning state
- terminal failures in red

Avoid making every incomplete state look like an error.

## Frontend Checklist

- load `asset-catalog` on page entry
- group `sources[]` by section
- render four current slot cards per source
- show transcript/extraction readiness before generation
- support single and batch AI generation
- send `language` explicitly
- support review payload editing by asset type
- support bilingual review shape for `both`
- show `validation_warnings`
- poll jobs by `batch_key`
- treat retrying `Pending` jobs differently from `Failed`
- require explicit approve and publish actions
- refetch `asset-catalog` after publish, manual create, edit, or regenerate

## Frontend Recommendation

If the frontend team needs one practical rule above all others, use this:

`asset-catalog` drives slot rendering, and `ai-content/jobs` drives generation workflow.

Do not let the UI infer one from the other.
