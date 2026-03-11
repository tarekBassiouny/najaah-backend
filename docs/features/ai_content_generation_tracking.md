# AI Content Generation Tracking

## Status
- Feature: AI content generation (admin-only, draft -> review -> publish)
- Branch: `feature/ai-content-generation` (to be created)
- Overall progress: `100%`
- Last updated: `2026-03-11`

## Locked Decisions
- No backward compatibility for old admin/backend AI quiz flow.
- Replace old `ai_generation_jobs` flow with unified `ai_content_jobs`.
- Mobile backward compatibility is required:
  - Existing mobile quiz/assignment endpoints must stay stable.
  - New mobile endpoints are additive only.
- Students do not chat with AI.
- AI output is always draft first; admin review/edit is required before publish.

## Product Scope
- Admin/content managers can generate:
  - Quizzes
  - Assignments
  - Summaries
  - Flashcards
  - Interactive activities
- Generated assets must belong to LMS structure (`course`, `section`, `video`, `pdf` source/attachment context).
- Students consume only published assets.

## Target Architecture
- New job pipeline model: `ai_content_jobs`
- Optional new content model for non-quiz/assignment assets: `learning_assets`
- Unified workflow state machine:
  - `pending -> processing -> completed -> approved -> published`
  - Failure path: `failed`
  - Cancel path: `discarded`

## Phases and Checklist

### Phase 1 - Foundation and Schema
- [x] Create `ai_content_jobs` migration and model.
- [x] Add enum(s): job status, target type, source type.
- [x] Add indexes for scale (`center_id+status`, `source`, `target`).
- [x] Remove old `AIGenerationJob` usage references.
- [x] Add/adjust factories.

**Deliverable**
- New DB schema and base model compile cleanly.

### Phase 2 - Service and Queue Pipeline
- [x] Implement unified service interface (`AIContentServiceInterface`).
- [x] Implement queue job processor for all target types.
- [x] Keep provider abstraction (OpenAI/Anthropic config based).
- [x] Store raw generated draft in `generated_payload`.
- [x] Capture provider/model/prompt/error metadata.

**Deliverable**
- End-to-end draft generation for at least one target type through queue.

### Phase 3 - Admin APIs (Center Scope)
- [x] Add routes:
  - [x] `POST /api/v1/admin/centers/{center}/ai-content/jobs`
  - [x] `GET /api/v1/admin/centers/{center}/ai-content/jobs`
  - [x] `GET /api/v1/admin/centers/{center}/ai-content/jobs/{job}`
  - [x] `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`
  - [x] `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/approve`
  - [x] `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/publish`
  - [x] `DELETE /api/v1/admin/centers/{center}/ai-content/jobs/{job}`
- [x] Remove old quiz-only AI routes/controller/service wiring.
- [x] Add strict ownership validation (source/target must match center context).
- [x] Return `422` for invalid source/target payload/context.

**Deliverable**
- Unified admin AI flow works for quiz and assignment targets.

### Phase 4 - Asset Publication Integration
- [x] Quiz target publish path:
  - [x] Write approved questions/options to quiz.
- [x] Assignment target publish path:
  - [x] Write approved assignment draft into assignment domain.
- [x] Add `learning_assets` for:
  - [x] summaries
  - [x] flashcards
  - [x] interactive activities
- [x] Publish operation writes canonical final content only from `reviewed_payload`.

**Deliverable**
- All target types can reach publish state with persisted output.

### Phase 5 - Mobile Consumption
- [x] Preserve existing quiz/assignment mobile behavior (no breaking changes).
- [x] Add new read endpoints for published-only learning assets:
  - [x] summaries list/detail
  - [x] flashcards list/detail
  - [x] interactive activities list/detail
- [x] Enforce enrollment + center checks + published/active filters.

**Deliverable**
- Mobile consumes published assets without AI workflow exposure.

### Phase 6 - Security, Docs, Quality
- [x] Add permissions:
  - [x] `ai_content.generate`
  - [x] `ai_content.review_publish`
  - [x] `learning_asset.manage`
- [x] Role mapping update (super_admin, center_owner, content_admin as baseline).
- [x] Add Scribe docs (`bodyParameters`, `queryParameters`) for new requests.
- [ ] Update Postman structure/modules.
- [x] Add feature tests and negative tests.
- [x] Run `./vendor/bin/sail composer quality`.

**Deliverable**
- Quality checks pass and docs are complete.

## API Contract Plan (Draft)

### Create Job
- Endpoint: `POST /api/v1/admin/centers/{center}/ai-content/jobs`
- Request body (draft):
  - `course_id` (required)
  - `source_type` (`video|pdf|section|course`)
  - `source_id`
  - `target_type` (`quiz|assignment|summary|flashcards|interactive_activity`)
  - `target_id` (nullable, required for update-existing flows)
  - `generation_config` (json; per target type)

### Review Job Output
- Endpoint: `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`
- Request body (draft):
  - `reviewed_payload` (required json)

### Approve and Publish
- Approve endpoint marks review accepted.
- Publish endpoint persists final content into canonical tables/resources.

## Validation Rules Plan
- Source ownership validation:
  - Source must belong to same center.
  - If course-bound source, must align with `course_id`.
- Target ownership validation:
  - If `target_id` provided, target must belong to same center/course context.
- Workflow guards:
  - Cannot review before `completed`.
  - Cannot publish before `approved`.
  - Terminal states cannot be modified except explicit retry/discard flow if supported.

## Test Matrix
- Unit:
  - state transitions
  - payload normalization
  - provider response parsing
- Feature (admin):
  - create job
  - poll/show
  - review
  - approve
  - publish
  - discard
  - invalid ownership -> `422`
  - forbidden role/scope -> `403`
- Feature (mobile):
  - published assets visible
  - draft/unpublished hidden
  - enrollment guard

## Risk Register
- Risk: cross-center source leakage.
  - Mitigation: strict ownership checks in request/service layer.
- Risk: large generated payloads.
  - Mitigation: json schema validation + limits in config.
- Risk: publish writes inconsistent structures.
  - Mitigation: target-specific mappers + transaction boundaries.
- Risk: frontend mismatch during refactor.
  - Mitigation: freeze response contracts and publish frontend handoff doc with examples.

## Progress Log
- 2026-03-11
  - Created tracking document.
  - Locked compatibility strategy (admin no-compat; mobile compatibility preserved).
  - Implementation not started yet.
- 2026-03-11
  - Implemented phases 1-6 end-to-end.
  - Added unified admin AI content generation lifecycle APIs.
  - Added new `learning_assets` mobile read endpoints (published-only).
  - Removed legacy quiz-only AI generation backend classes and routes.
  - Added permissions and role mappings for AI content operations.
  - Added feature tests for admin AI content flow and mobile learning assets.
  - `./vendor/bin/sail composer quality` passed.

## Done Criteria
- Unified AI admin pipeline replaces old quiz-only implementation.
- Mobile existing assessment APIs remain compatible.
- New published asset endpoints available for summaries/flashcards/interactive.
- Permissions/docs/tests completed.
- `./vendor/bin/sail composer quality` passes.
