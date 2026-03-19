# AI Content Generation Enhancement Frontend Handoff

## Purpose

This document is the frontend-facing handoff for the phased AI content generation enhancement work.

Update this file after each backend phase so the frontend can adopt contract changes incrementally instead of rediscovering them from code.

## Scope

- Admin AI content generation flow
- Center-scoped routes only
- Request contract changes
- Response shape changes
- Frontend rollout notes per phase

## Current Backend Status

- Phases 0 through 4 are now implemented on the backend
- v1 backend scope is complete
- Phase 5 auto-transcription remains deferred by design
- the current frontend task is adoption, not waiting for more v1 backend changes

## Routing and Permission Baseline

- Scope: `center`
- Create/list/show/discard permissions: `ai_content.generate`
- Review/approve/publish permissions: `ai_content.review_publish`

Primary endpoints:
- `POST /api/v1/admin/centers/{center}/ai-content/jobs`
- `POST /api/v1/admin/centers/{center}/ai-content/batches`
- `GET /api/v1/admin/centers/{center}/ai-content/jobs`
- `GET /api/v1/admin/centers/{center}/ai-content/jobs/{job}`
- `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/approve`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/publish`
- `DELETE /api/v1/admin/centers/{center}/ai-content/jobs/{job}`

---

## Phase 0

### Summary

Phase 0 is a contract cleanup phase. No route paths changed, but request validation and job payload shape changed.

### Frontend-Relevant Changes

1. `language` is now part of the AI content job contract.
- Accepted values: `en`, `ar`, `both`
- Default: `ar`
- Returned on job resources and batch job resources

2. Batch source support expanded.
- Previous practical frontend assumption: `video` and `pdf`
- New supported values: `video`, `pdf`, `section`, `course`

3. Batch target support expanded.
- `interactive_activity` is now allowed in batch generation

4. Single-job and batch `generation_config` validation are now aligned.
- Frontend should expect the same target-specific validation rules on both endpoints

### Request Contract

#### Create Single Job

`POST /api/v1/admin/centers/{center}/ai-content/jobs`

Body:
```json
{
  "course_id": 12,
  "source_type": "video",
  "source_id": 34,
  "target_type": "summary",
  "language": "ar",
  "ai_provider": "openai",
  "ai_model": "gpt-4o-mini",
  "generation_config": {}
}
```

Notes:
- `language` is optional from the client because backend defaults it to `ar`
- frontend should still send it explicitly to avoid ambiguity

#### Create Batch

`POST /api/v1/admin/centers/{center}/ai-content/batches`

Body:
```json
{
  "course_id": 12,
  "source_type": "section",
  "source_id": 55,
  "language": "both",
  "assets": [
    {
      "target_type": "interactive_activity"
    },
    {
      "target_type": "summary",
      "generation_config": {
        "length": "medium"
      }
    }
  ]
}
```

### Response Contract Additions

Job resource now includes:
```json
{
  "id": 1001,
  "source_type": "section",
  "source_id": 55,
  "target_type": "interactive_activity",
  "target_id": null,
  "language": "both",
  "status": 0,
  "status_label": "Pending"
}
```

### Frontend Action Items

- Add `language` control to AI generation forms
- Default frontend selection to `ar`
- Support `both` only where the UI can handle bilingual generated payloads
- Expand source pickers and source chips to allow `section` and `course` batch generation
- Include `interactive_activity` in batch target selection UI
- Reuse the same generation-config builders for single-job and batch flows

### Validation Expectations

Frontend should expect `422` validation errors when:
- `language` is outside `en|ar|both`
- `generation_config` violates target-specific rules
- duplicate `target_type` values are submitted inside one batch

### Backend Gaps Remaining After Phase 0

- `language` influences persistence and contract shape, but not prompt behavior yet
- transcripts and extracted PDF text are not added yet
- prompt quality and output validation improvements are not added yet

---

## V1 Status and Backend Roadmap After V1

Phases 0 through 4 are now implemented on the backend.

The frontend can build the full v1 admin workflow against the current contract without waiting for another backend phase.

Phase 5 remains future work only:
- automatic transcript generation providers
- async auto-transcription jobs
- budget and provider controls for transcription

Nothing else is required from the backend to start the v1 frontend rollout.

---

## Phase 1

### Summary

Phase 1 adds real source-preparation workflows for AI generation:
- admins can upload or paste transcripts for videos
- uploaded PDFs enter async text extraction
- course and section AI extraction now includes transcript text and extracted PDF text when available

### Frontend-Relevant Changes

1. Video transcript management endpoints are now available.
- `POST /api/v1/admin/centers/{center}/videos/{video}/transcript`
- `GET /api/v1/admin/centers/{center}/videos/{video}/transcript`
- `DELETE /api/v1/admin/centers/{center}/videos/{video}/transcript`

2. Admin video resources now expose transcript metadata.
- `has_transcript`
- `transcript_format`
- `transcript_source`

3. Admin PDF resources now expose extraction metadata.
- `has_extracted_text`
- `text_extraction_status`
- `text_extraction_status_label`

4. AI generation readiness is now stricter for direct media sources.
- video-source AI generation requires a stored transcript
- pdf-source AI generation requires completed text extraction

### Transcript API Contract

#### Save Transcript

`POST /api/v1/admin/centers/{center}/videos/{video}/transcript`

Accepted request shapes:

Multipart upload:
```http
file=<txt|vtt|srt file>
```

JSON body:
```json
{
  "transcript_text": "Lesson introduction..."
}
```

Response:
```json
{
  "success": true,
  "message": "Transcript saved successfully.",
  "data": {
    "video_id": 44,
    "has_transcript": true,
    "transcript": "Lesson introduction...",
    "transcript_format": "txt",
    "transcript_source": "manual"
  }
}
```

Notes:
- uploaded `vtt` and `srt` files are normalized to plain text on save
- v1 only supports manual transcript ingestion
- raw audio upload is still out of scope

#### Read Transcript

`GET /api/v1/admin/centers/{center}/videos/{video}/transcript`

Response shape matches the save response `data` object.

#### Delete Transcript

`DELETE /api/v1/admin/centers/{center}/videos/{video}/transcript`

Response:
```json
{
  "success": true,
  "message": "Transcript deleted successfully.",
  "data": {
    "video_id": 44,
    "has_transcript": false,
    "transcript": null,
    "transcript_format": null,
    "transcript_source": null
  }
}
```

### Updated Resource Fields

#### Admin Video Resource

New fields:
```json
{
  "has_transcript": true,
  "transcript_format": "txt",
  "transcript_source": "manual"
}
```

#### Admin PDF Resource

New fields:
```json
{
  "has_extracted_text": false,
  "text_extraction_status": 0,
  "text_extraction_status_label": "Pending"
}
```

Status mapping:
- `0` = `Pending`
- `1` = `Processing`
- `2` = `Completed`
- `3` = `Failed`
- `4` = `Skipped`

### Frontend Action Items

- Add transcript management UI to the admin video details/edit flow
- Support both transcript paste and transcript file upload
- Show transcript presence in video tables/cards using `has_transcript`
- Show PDF extraction state in PDF tables/details using `text_extraction_status_label`
- Prevent or warn against AI generation from a direct video source when `has_transcript` is `false`
- Prevent or warn against AI generation from a direct PDF source until `text_extraction_status` is `2`
- When a PDF has just been created, poll or refresh its resource until extraction leaves `Pending` or `Processing`

### Validation and Error Expectations

Frontend should expect `422` responses when:
- transcript upload is missing both `file` and `transcript_text`
- transcript upload uses unsupported file types outside `txt|vtt|srt`
- AI job creation uses a `video` source without a transcript
- AI job creation uses a `pdf` source before extraction completes

Relevant backend error codes:
- `TRANSCRIPT_NOT_FOUND`
- `PDF_NOT_READY`
- `PDF_TEXT_EXTRACTION_FAILED`

### Remaining Backend Gaps After Phase 1

- language-aware prompt construction is still not implemented
- `generation_config` is still not used to shape prompts
- output validation/retry logic is still not implemented
- auto-transcription is still deferred

---

## Phase 2

### Summary

Phase 2 activates prompt intelligence and language behavior:
- prompts are now target-aware and language-aware
- AI providers now receive separate system and user prompts
- `generation_config` now affects prompt instructions
- publish-time translation normalization now follows the job language

### Frontend-Relevant Changes

1. No route paths changed.
- create/review/approve/publish endpoints stay the same

2. `language` now materially affects `generated_payload` and `reviewed_payload`.
- `ar` and `en` jobs still use plain strings for human-readable fields
- `both` jobs now expect locale objects with `ar` and `en` for human-readable fields

3. `generation_config` is now behaviorally active.
- the backend uses it to shape the generated content, not just validate it

4. Publication now respects job language.
- plain string outputs for `ar` jobs publish to Arabic translations
- plain string outputs for `en` jobs publish to English translations
- bilingual maps for `both` jobs are preserved on publish

### Payload Shape Expectations

#### Single-Language Jobs

For `language=ar` or `language=en`, human-readable fields in generated payloads remain plain strings.

Example summary payload:
```json
{
  "title": "ملخص الدرس",
  "content": "هذا ملخص موجز."
}
```

#### Bilingual Jobs

For `language=both`, human-readable fields should be locale objects.

Example summary payload:
```json
{
  "title": {
    "ar": "ملخص الدرس",
    "en": "Lesson Summary"
  },
  "content": {
    "ar": "هذا ملخص موجز.",
    "en": "This is a short summary."
  }
}
```

Example quiz fragment:
```json
{
  "quiz": {
    "title": {
      "ar": "اختبار سريع",
      "en": "Quick Quiz"
    },
    "description": {
      "ar": "راجع المفاهيم الأساسية",
      "en": "Review the key concepts"
    }
  },
  "questions": [
    {
      "question": {
        "ar": "ما هو المتغير؟",
        "en": "What is a variable?"
      },
      "options": [
        {
          "text": {
            "ar": "رمز يمثل قيمة",
            "en": "A symbol that represents a value"
          },
          "is_correct": true
        }
      ],
      "explanation": {
        "ar": "المتغير يرمز إلى قيمة يمكن أن تتغير.",
        "en": "A variable represents a value that can change."
      },
      "points": 1
    }
  ]
}
```

### Generation Config Behavior

The frontend can now expect real behavioral impact from these settings:

- `summary.length`
- `summary.include_key_points`
- `quiz.question_count`
- `quiz.difficulty`
- `quiz.question_styles`
- `assignment.assignment_style`
- `assignment.submission_types`
- `assignment.max_points`
- `flashcards.card_count`
- `flashcards.focus`

### Frontend Action Items

- Render generated/reviewed payloads according to `job.language`, not as always-string content
- For `language=both`, use locale-aware editors, preview cards, and diff/review components
- Preserve bilingual object shapes when sending `reviewed_payload`; do not flatten them to strings
- Keep `generation_config` controls visible and meaningful because they now change output behavior
- Update review UIs for summary, quiz, flashcards, assignment, and interactive activity payloads to handle both single-language and bilingual text nodes

### Validation and Review Expectations

Backend review endpoints still accept a generic `reviewed_payload` object, so the frontend must preserve the original language shape:

- if the job language is `ar` or `en`, keep text fields as strings
- if the job language is `both`, keep text fields as `{ ar, en }` objects wherever the generated payload uses them

### Remaining Backend Gaps After Phase 2

- provider timeout and 429 hardening are still pending
- structured JSON-mode provider requests are still pending
- output validation/retry logic is still not implemented

---

## Phase 3

### Summary

Phase 3 hardens provider execution:
- provider calls now use request timeouts
- `429` rate limits are detected explicitly
- OpenAI and Gemini now request structured JSON mode
- retryable provider failures now use queue retries/backoff instead of being swallowed as terminal failures
- queue `retry_after` is now aligned above the AI job timeout

### Frontend-Relevant Changes

1. No route paths changed.
- the AI job API surface is the same

2. Job lifecycle behavior is more resilient for transient provider failures.
- rate limits and connection timeouts now move jobs back to `Pending` while the queue retries them
- only exhausted retries end in terminal `Failed` status

3. Payload shape did not change in Phase 3.
- this phase improves provider reliability, not the published JSON contract

### Dashboard Behavior Expectations

- treat `Pending` jobs with a recent `error_message` as retrying, not necessarily terminal
- keep polling until the job becomes `Completed`, `Approved`, `Published`, or final `Failed`
- avoid showing malformed provider output to reviewers; JSON-mode requests should reduce that class of issue

### Frontend Action Items

- Update job status UI to distinguish retrying/transient failures from final failures
- If a job returns to `Pending` with an `error_message`, show a retrying state instead of a hard failure state
- Keep existing generated/review payload renderers; no new payload fields are required for Phase 3
- Do not assume provider fallback exists; the selected provider remains deterministic per job

### Error Expectations

Backend retries automatically for transient provider issues such as:
- provider `429` rate limiting
- provider connection timeouts

Frontend-visible behavior:
- transient failures may briefly populate `error_message` before a later retry succeeds
- final failures still surface as `status=Failed`

### Remaining Backend Gaps After Phase 3

- target-type output validation is still not implemented
- validation warnings are not stored yet
- automatic provider fallback is still out of scope

---

## Phase 4

### Summary

Phase 4 adds output validation and review safety:
- generated AI payloads are validated per target type before the job is marked complete
- invalid first-pass AI payloads trigger one retry with validation feedback
- jobs now store `validation_warnings`
- reviewed payloads are validated before they can be saved

### Frontend-Relevant Changes

1. Job resources now expose `validation_warnings`.
- available on job list and job detail resources
- value is either `null` or an array of human-readable warning strings

2. AI generation is stricter before a job reaches `Completed`.
- malformed payloads now fail earlier instead of reaching reviewers as broken JSON content
- the backend retries once automatically before final failure

3. Review editing now has stronger shape enforcement.
- invalid `reviewed_payload` structures return `422`
- this is especially important for bilingual jobs where text nodes must stay as `{ ar, en }`

### Response Contract Addition

Job resources now include:
```json
{
  "id": 1001,
  "status": 2,
  "status_label": "Completed",
  "validation_warnings": [
    "content must be a non-empty text field."
  ]
}
```

Interpretation:
- `null`: no warnings were recorded
- non-empty array: the first AI response had validation issues, but the retry still succeeded or the job failed after retry

### Review Validation Expectations

Frontend should expect `422` responses when `reviewed_payload` breaks target structure requirements.

Examples:
- summary without `title` or `content`
- quiz questions without options
- bilingual jobs where text fields are flattened into strings instead of `{ ar, en }`

Practical rule:
- always preserve the exact shape of the generated payload while editing
- edit values in place instead of rebuilding payload objects from scratch

### Frontend Action Items

- Surface `validation_warnings` in the job detail view, review drawer, and failure state
- Treat warnings as quality signals, not always as hard blockers
- Preserve generated payload shape exactly when posting `reviewed_payload`
- Add schema-aware editors for each target type instead of a generic JSON textarea
- Keep retrying/transient provider states distinct from validation failures
- Show a clearer empty or disabled state when the selected source is not AI-ready

### Recommended Dashboard UX

1. Use a three-step job detail layout:
- Source readiness
- Generated content
- Review and publish

2. Show source readiness badges before generation starts:
- video: `Transcript Ready` or `Transcript Missing`
- pdf: `Extraction Pending`, `Extraction Processing`, `Extraction Ready`, `Extraction Failed`
- section/course: `Ready`

3. For bilingual jobs, use locale tabs instead of mixed inline JSON rendering.
- tab 1: Arabic
- tab 2: English
- keep one shared structural editor and swap the active locale values

4. Put `validation_warnings` in a visible but non-blocking panel.
- use a warning callout above the generated content
- include copy like `The first AI response failed validation and was retried automatically. Review carefully.`

5. Separate status chips clearly:
- `Pending`
- `Retrying`
- `Completed`
- `Approved`
- `Published`
- `Failed`

6. Avoid exposing raw JSON by default.
- render summaries, flashcards, quizzes, assignments, and interactive activities with domain-specific cards/forms
- keep raw JSON in an advanced collapsible panel for debugging only

7. Add a sticky review action bar.
- `Save Review`
- `Approve`
- `Publish`
- disable the actions until required fields remain structurally valid

### Final Frontend Rollout Checklist

- AI job forms send `language` explicitly
- batch generation supports `video`, `pdf`, `section`, and `course`
- batch generation includes `interactive_activity`
- video details include transcript upload, replace, view, and delete
- PDF details show extraction state and prevent premature direct AI generation
- review screens preserve single-language vs bilingual payload shapes
- job detail screens show `validation_warnings`
- status UI distinguishes retrying provider failures from terminal failures
- publish UI keeps source/target context visible so reviewers know what will be updated
