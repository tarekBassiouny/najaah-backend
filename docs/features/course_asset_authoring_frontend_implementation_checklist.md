# Course Asset Authoring Frontend Implementation Checklist

## Purpose

This checklist converts the implemented backend into a frontend delivery plan.

Use it with:
- `/docs/features/course_asset_authoring_admin_ux_handoff.md`
- `/docs/features/assignments_quizzes_frontend_quick_reference.md`

---

## 1. Route Integration

Implement API clients for:
- `GET /api/v1/admin/centers/{center}/courses/{course}/asset-catalog`
- `POST /api/v1/admin/centers/{center}/ai-content/batches`
- `GET /api/v1/admin/centers/{center}/ai-content/jobs`
- `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/approve`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/publish`
- `DELETE /api/v1/admin/centers/{center}/ai-content/jobs/{job}`
- `GET /api/v1/admin/centers/{center}/courses/{course}/learning-assets`
- `GET /api/v1/admin/centers/{center}/learning-assets/{asset}`
- `PUT /api/v1/admin/centers/{center}/learning-assets/{asset}`
- `PATCH /api/v1/admin/centers/{center}/learning-assets/{asset}/status`

Keep using existing manual flows for:
- quiz create/update/questions
- assignment create/update

Do not add frontend calls to a non-existent batch detail endpoint.

---

## 2. Types

Add frontend types for:
- `AssetCatalogResponse`
- `CourseAssetSource`
- `CourseAssetSlot`
- `AssetSlotState`
- `CreateAssetBatchRequest`
- `CreateAssetBatchResponse`
- `LearningAssetAdminResource`
- `AIContentJobResource`

Keep slot types limited to:
- `summary`
- `quiz`
- `flashcards`
- `assignment`

Do not expose `interactive_activity` in the MVP UI.

---

## 3. Feature Modules

### `/features/course-builder`

Create or update:
- `CourseContentTree`
- `SectionGroup`
- `SourceRow`
- `AssetSlotList`
- `AssetSlotCard`
- `GenerateAssetsButton`
- `CreateManualQuizButton`
- `CreateManualAssignmentButton`

Hooks:
- `useAssetCatalog(centerId, courseId, filters)`

Selectors:
- `groupSourcesBySection(sources)`
- `getSlotByType(source.assets, assetType)`

### `/features/course-assets`

Create:
- `GenerateAssetsModal`
- `GenerationOptionsForm`
- `BatchProgressDrawer`
- `AssetReviewDrawer`
- `SummaryReviewForm`
- `FlashcardsReviewForm`
- `QuizReviewPanel`
- `AssignmentReviewPanel`
- `RegenerateAssetButton`
- `ApproveAssetButton`
- `PublishAssetButton`

Hooks:
- `useCreateAiBatch()`
- `useBatchJobs(batchKey)`
- `useReviewAiJob(jobId)`
- `useApproveAiJob(jobId)`
- `usePublishAiJob(jobId)`
- `useLearningAsset(assetId)`
- `useUpdateLearningAsset(assetId)`
- `useUpdateLearningAssetStatus(assetId)`

Services:
- `assetCatalog.api.ts`
- `aiContent.api.ts`
- `learningAssets.api.ts`

---

## 4. Course Builder Page

Tasks:
- load `asset-catalog` on page entry
- group flat `sources[]` by `source.section`
- render source rows for `video` and `pdf`
- show four slots per source
- render slot state badge
- render actions based on slot state and permissions

Required slot actions:
- `missing`
  - `Generate`
  - `Create Quiz` for quiz slot if allowed
  - `Create Assignment` for assignment slot if allowed
- `generating`
  - `View Progress`
- `review_required`
  - `Review`
  - `Regenerate`
- `approved`
  - `Publish`
  - `Review`
  - `Regenerate`
- `published`
  - `View`
  - `Edit`
  - `Regenerate`
- `draft`
  - `Edit`
  - `Publish` where relevant
- `failed`
  - `Retry`

Do not derive slot state client-side from unrelated resources.
Use `slot_state` from `asset-catalog`.

---

## 5. Generate Assets Modal

Tasks:
- open from a source row only
- prefill `source_type` and `source_id`
- lock source selection to that source
- allow multi-select across:
  - summary
  - quiz
  - flashcards
  - assignment
- show mode selector only for:
  - quiz
  - assignment

Validation:
- at least one asset selected
- only one entry per asset type
- if mode is manual, do not include that asset in the AI batch payload

Mapping:
- AI selections go into `POST /ai-content/batches`
- manual quiz routes to existing quiz create flow with prefilled attachable source
- manual assignment routes to existing assignment create flow with prefilled attachable source

---

## 6. Generation Config UI

### Summary

Expose:
- `length`
- `include_key_points`

### Quiz

Expose:
- `question_count`
- `difficulty`
- `question_styles`

Allowed `question_styles`:
- `single_choice`
- `multiple_choice`
- `true_false`

Do not expose short-answer.

### Flashcards

Expose:
- `card_count`
- `focus`

Allowed `focus`:
- `definitions`
- `concepts`
- `formulas`

### Assignment

Expose:
- `assignment_style`
- `submission_types`
- `max_points`

Allowed `submission_types`:
- `0` file
- `1` text
- `2` link

---

## 7. Batch Submission and Polling

Tasks:
- submit one batch request for all AI assets from the same source
- store returned `batch_key`
- open `BatchProgressDrawer`
- poll `GET /ai-content/jobs?batch_key=...`
- stop polling when all jobs are terminal or reviewable

Recommended polling states:
- `polling`
- `ready_for_review`
- `partially_failed`
- `all_terminal`

Stop conditions:
- all jobs are `published`
- all jobs are `failed` or `discarded`
- or user closes progress and returns later

Resume rule:
- if user reopens a source row with active batch jobs, frontend may poll by latest known `batch_key`

---

## 8. Review Flow

Review is per asset, not all-at-once.

### Summary

Tasks:
- load job payload
- edit title/content
- save through AI job review endpoint
- approve
- publish

### Flashcards

Tasks:
- edit title/cards
- save through AI job review endpoint
- approve
- publish

### Quiz

Tasks:
- edit title/description
- edit question list
- edit options and correct answers
- save through AI job review endpoint
- approve
- publish

### Assignment

Tasks:
- edit title/description
- edit submission types
- edit max points
- save through AI job review endpoint
- approve
- publish

After publish:
- refetch `asset-catalog`
- for summary/flashcards, later edits should use learning-asset admin APIs

---

## 9. Manual Flow Integration

### Quiz

When user clicks `Create Quiz`:
- navigate to existing quiz create screen
- prefill:
  - `course_id`
  - `attachable_type`
  - `attachable_id`

### Assignment

When user clicks `Create Assignment`:
- navigate to existing assignment create screen
- prefill:
  - `course_id`
  - `attachable_type`
  - `attachable_id`

After save:
- return to Course Builder
- refetch `asset-catalog`

---

## 10. Learning Asset Admin Integration

Use only for:
- generated summary
- generated flashcards

Tasks:
- show detail
- update title/content/payload
- publish/archive through status endpoint

Do not add manual create UI for learning assets in v1.

---

## 11. Permissions

Check and gate by permission:
- `course.manage`
  - required to enter Course Builder asset workflow
- `ai_content.generate`
  - required for `Generate Assets`
- `ai_content.review_publish`
  - required for `Review`, `Approve`, `Publish`
- `quiz.manage`
  - required for manual quiz create/edit
- `assignment.manage`
  - required for manual assignment create/edit
- `learning_asset.manage`
  - required for summary/flashcards admin edit

Do not assume `center_admin` can approve/publish.

---

## 12. Error Handling

Handle:
- `VALIDATION_ERROR`
- `INVALID_STATE`
- `NOT_FOUND`
- AI provider/model availability issues
- missing transcript or PDF text
- partial batch failure

UI requirements:
- show per-job error state
- preserve successful jobs when one asset fails
- allow regenerate or retry on failed assets only

---

## 13. Refresh Rules

Refetch `asset-catalog` after:
- batch publish
- single asset publish
- learning asset status change
- manual quiz create
- manual assignment create
- manual quiz update if user returns to Course Builder
- manual assignment update if user returns to Course Builder

Refetch job list after:
- review
- approve
- publish
- discard

---

## 14. Acceptance Checklist

Frontend is done when:
- course builder shows source-bound slots under videos and PDFs
- admin can submit one batch request for multiple AI assets from a source
- frontend polls by `batch_key`
- summary and flashcards can be reviewed and edited
- quiz and assignment can follow AI or manual path
- regenerate on a published asset does not confuse users about which version is live
- asset-catalog is the single source of truth for slot rendering
- no UI path assumes short-answer quizzes or manual summary/flashcard creation
