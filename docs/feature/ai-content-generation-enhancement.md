# AI Content Generation Enhancement — V1 Plan

## Context

The AI content generation system already exists for quizzes, assignments, summaries, flashcards, and interactive activities, but it still generates from weak source material. Videos do not yet persist transcripts, PDFs do not yet persist extracted text, course-level extraction is shallow, prompts are generic, `generation_config` is mostly unused, and language intent is not yet part of the persisted job contract.

This plan upgrades the system in phases while keeping v1 focused and safe.

**Date**: 2026-03-18

## Decisions

- Manual transcript ingestion comes first in v1: admin file upload or pasted transcript text.
- Raw audio upload is not part of v1.
- Auto-transcription is future work, but the transcript data model should be ready for it.
- `smalot/pdfparser` will be used for PDF text extraction.
- Arabic-first generation is the default for v1.
- Batch generation in v1 supports `video`, `pdf`, `section`, and `course` sources.
- Batch generation in v1 includes `interactive_activity`.
- Automatic provider fallback is out of scope for v1.

## V1 Scope

### In Scope
- Persist `language` on AI content jobs
- Unify single-job and batch validation
- Manual transcript upload or pasted transcript text for videos
- PDF text extraction and queued processing
- Better source extraction for video, PDF, section, and course
- Prompt builder with language-aware output and `generation_config` wiring
- Provider hardening: timeouts, 429 handling, JSON-mode output, better backoff
- Output validation and one retry for invalid AI responses

### Out of Scope
- Automatic provider fallback
- Manual raw audio upload
- Auto-transcription providers, budgets, and jobs

---

## Phase 0: Job Contract & Request Cleanup

**Status**: Implemented

### 0.1 Persist Job Language

**Create** migration to add `language` to `ai_content_jobs`
- `language` varchar(10) default `'ar'`

**Modify** `app/Models/AIContentJob.php`
- add `language` to `$fillable`
- add cast or string handling as needed

**Modify** `app/Http/Resources/Admin/AIContent/AIContentJobResource.php`
- expose `language`

### 0.2 Unify Request Validation

**Create** shared validation helper or concern for AI generation config rules
- one source of truth for target-type-specific `generation_config` validation

**Modify** `app/Http/Requests/Admin/AIContent/CreateAIContentJobRequest.php`
- add `language` validation: `['en', 'ar', 'both']`, default `'ar'`
- reuse shared generation_config validation

**Modify** `app/Http/Requests/Admin/AIContent/CreateAIContentBatchRequest.php`
- add batch-level `language` validation: `['en', 'ar', 'both']`, default `'ar'`
- open `source_type` to `video`, `pdf`, `section`, `course`
- include `interactive_activity` in allowed batch target types
- reuse shared generation_config validation

### 0.3 AIContentService Job Creation

**Modify** `app/Services/Assessments/AIContentService.php`
- persist `language` when creating a job
- pass batch-level `language` into each created job

### 0.4 Tests

- request validation tests for `language`
- tests for batch support of `section` and `course`
- tests for batch support of `interactive_activity`
- tests that shared generation_config validation behaves consistently between single-job and batch endpoints

---

## Phase 1: Content Foundation

**Status**: Implemented

### 1.1 Migrations

**Create** migration to add transcript fields to videos
- `transcript` longText nullable
- `transcript_format` varchar(10) nullable
- `transcript_source` varchar(20) nullable

**Create** migration to add extracted text fields to PDFs
- `text_content` longText nullable
- `text_extraction_status` tinyInteger default 0, indexed

### 1.2 Enums

**Create**
- `app/Enums/TranscriptFormat.php`
- `app/Enums/TranscriptSource.php`
- `app/Enums/TextExtractionStatus.php`

### 1.3 Model Updates

**Modify** `app/Models/Video.php`
- add transcript fields to `$fillable`
- add enum casts for transcript format and source

**Modify** `app/Models/Pdf.php`
- add extracted text fields to `$fillable`
- add enum cast for extraction status

### 1.4 Transcript Service

**Create** `app/Services/Videos/Contracts/TranscriptServiceInterface.php`
```php
public function uploadTranscript(Video $video, User $admin, UploadedFile $file): Video;
public function saveTranscriptText(Video $video, User $admin, string $transcriptText): Video;
public function getTranscript(Video $video): ?string;
public function deleteTranscript(Video $video, User $admin): Video;
public function parseToPlainText(string $content, TranscriptFormat $format): string;
```

**Create** `app/Services/Videos/TranscriptService.php`
- support file upload for `txt`, `vtt`, and `srt`
- support pasted transcript text
- normalize everything into plain text stored on `videos.transcript`
- set `transcript_format` and `transcript_source=manual`

### 1.5 Transcript Admin Endpoints

**Create** `app/Http/Controllers/Admin/Videos/VideoTranscriptController.php`
- `store()` accepts file upload or pasted transcript text
- `show()` returns transcript and metadata
- `destroy()` removes transcript and metadata

**Create** `app/Http/Requests/Admin/Videos/UploadTranscriptRequest.php`
- require either `file` or `transcript_text`
- `file`: `mimes:txt,vtt,srt,text`, `max:5120`
- `transcript_text`: non-empty string with reasonable max length

**Modify** `routes/api/v1/admin/videos.php`
- `POST /centers/{center}/videos/{video}/transcript`
- `GET /centers/{center}/videos/{video}/transcript`
- `DELETE /centers/{center}/videos/{video}/transcript`

### 1.6 PDF Text Extraction

**Add** `getContents(string $path): string` to `app/Services/Storage/Contracts/StorageServiceInterface.php`

**Implement** in `app/Services/Storage/SpacesStorageService.php`

**Create**
- `app/Services/Pdfs/Contracts/PdfTextExtractionServiceInterface.php`
- `app/Services/Pdfs/PdfTextExtractionService.php`

Behavior:
- fetch PDF bytes from storage
- extract text with `smalot/pdfparser`
- return normalized plain text

**Composer**
- `composer require smalot/pdfparser`

### 1.7 PDF Extraction Job

**Create** `app/Jobs/ExtractPdfTextJob.php`
- queue: `pdf-extraction`
- tries: 3
- backoff: `[15, 60, 180]`
- manage status transitions: pending -> processing -> completed or failed

**Modify** `app/Services/Pdfs/PdfService.php`
- dispatch extraction job after successful PDF creation when the uploaded file is a PDF

### 1.8 Source Extraction Fixes

**Modify** `app/Services/Assessments/AIContentService.php`
- keep video extraction transcript-aware
- keep PDF extraction text-aware
- fix `extractCourseContent()` so it includes video transcripts and PDF text, not only titles

### 1.9 Service Registration

**Modify** `app/Providers/AppServiceProvider.php`
- register transcript service bindings
- register PDF text extraction service bindings

### 1.10 Resource Updates

**Modify** `app/Http/Resources/Admin/Videos/VideoResource.php`
- add `has_transcript`
- add `transcript_format`
- add `transcript_source`

**Modify** `app/Http/Resources/Admin/PdfResource.php`
- add `text_extraction_status`

### 1.11 Error Codes

**Modify** `app/Support/ErrorCodes.php`
- add `TRANSCRIPT_NOT_FOUND`
- add `PDF_TEXT_EXTRACTION_FAILED`

### 1.12 Tests

- `tests/Feature/Admin/Videos/TranscriptUploadTest.php`
- `tests/Unit/Services/Videos/TranscriptServiceTest.php`
- `tests/Unit/Services/Pdfs/PdfTextExtractionServiceTest.php`
- `tests/Feature/Jobs/ExtractPdfTextJobTest.php`
- coverage for course extraction including transcripts and extracted PDF text

---

## Phase 2: Prompt Intelligence & Language

**Status**: Implemented
**Depends on**: Phase 0 and Phase 1

### 2.1 Prompt Builder

**Create** `app/Services/Assessments/PromptBuilder.php`
```php
public function build(AIContentJob $job, string $content): array;
public function buildRetryPrompt(AIContentJob $job, string $content, array $errors): array;
```

Responsibilities:
- separate system and user prompts
- make prompts target-type aware
- make prompts language aware
- inject generation config into prompt instructions
- support bilingual output shape when `language=both`

### 2.2 AIContentService Integration

**Modify** `app/Services/Assessments/AIContentService.php`
- inject `PromptBuilder`
- change processing flow to use `{system, user}`
- update provider methods to accept separate system and user messages

### 2.3 generation_config Wiring

Use `generation_config` in prompts for:
- quiz
- assignment
- summary
- flashcards
- interactive activity

### 2.4 Language-Aware Publication

**Modify** translation normalization in `AIContentService`
- use job language
- default plain strings to Arabic when `language=ar`
- preserve bilingual maps when `language=both`

### 2.5 Tests

- `tests/Unit/Services/Assessments/PromptBuilderTest.php`
- `tests/Feature/Admin/AIContent/AIContentLanguageTest.php`

---

## Phase 3: Provider Hardening

**Status**: Implemented
**Depends on**: Phase 2

### 3.1 HTTP Hardening

**Modify** provider calls in `app/Services/Assessments/AIContentService.php`
- add `->timeout(120)`
- detect 429 responses explicitly

**Create** `app/Exceptions/RateLimitException.php`

### 3.2 Queue Backoff

**Modify** `app/Jobs/ProcessAIContentJob.php`
- change `$backoff = 60` to `[30, 90, 270]`

### 3.3 Structured JSON Output

**Modify** provider calls
- OpenAI: request JSON object output
- Gemini: request JSON MIME type
- Anthropic: strengthen JSON-only prompt contract while keeping markdown stripping fallback

### 3.4 Explicit v1 Non-Goal

- do not add automatic provider fallback in v1
- keep provider selection deterministic per job

### 3.5 Tests

- timeout handling
- 429 handling
- JSON-mode request assertions
- queue backoff behavior where practical

---

## Phase 4: Output Validation

**Status**: Implemented
**Depends on**: Phase 2 and Phase 3

### 4.1 Validators

**Create**
- `app/Services/Assessments/Validators/AIOutputValidatorInterface.php`
- target-type validators for quiz, assignment, summary, flashcards, and interactive activity

### 4.2 Validation Integration

**Modify** `AIContentService::processJob()`
- validate provider output before storing success
- retry once with validation feedback if the first payload is invalid

### 4.3 Job Warnings

**Create** migration to add `validation_warnings` JSON nullable to `ai_content_jobs`

**Modify** `app/Models/AIContentJob.php`
- add `validation_warnings` to fillable and casts

### 4.4 Tests

- unit tests for each validator
- integration-style test for invalid AI output -> retry -> success or failure

---

## Phase 5: Auto-Transcription Pipeline

**Status**: Future
**Depends on**: Phase 1
**V1 Status**: Deferred

Phase 5 is intentionally split into a separate deferred design document:
- [ai-auto-transcription-pipeline.md](/Users/tarekbassiouny/projects/najaah-backend/docs/feature/ai-auto-transcription-pipeline.md)

Phase 1 already left the system ready for that future work by:
- storing canonical plain-text transcripts on videos
- tracking `transcript_source`
- tracking `transcript_format`
- keeping transcript ingestion behind a service boundary

---

## Implementation Sequence

```text
Phase 0 -> Phase 1 -> Phase 2 -> Phase 3 -> Phase 4

Phase 5 happens after v1 adoption.
```

## Verification Strategy

After each phase:
1. Run the relevant test slice first
2. Run `composer quality`
3. Manually test the changed API surface in dev
4. Commit the phase as an isolated change when possible

## V1 Delivery Status

- Phase 0: Implemented
- Phase 1: Implemented
- Phase 2: Implemented
- Phase 3: Implemented
- Phase 4: Implemented
- Phase 5: Deferred by design

## Key Files

| File | Role |
|------|------|
| `app/Services/Assessments/AIContentService.php` | central AI generation flow |
| `app/Models/AIContentJob.php` | persisted AI job contract |
| `app/Models/Video.php` | transcript storage |
| `app/Models/Pdf.php` | extracted text storage |
| `app/Jobs/ProcessAIContentJob.php` | AI generation queue job |
| `app/Jobs/ExtractPdfTextJob.php` | PDF extraction queue job |
| `app/Http/Requests/Admin/AIContent/CreateAIContentJobRequest.php` | single-job validation |
| `app/Http/Requests/Admin/AIContent/CreateAIContentBatchRequest.php` | batch validation |
| `app/Http/Resources/Admin/AIContent/AIContentJobResource.php` | admin job output |
| `app/Services/Pdfs/PdfService.php` | PDF creation and extraction dispatch |
| `app/Services/Storage/Contracts/StorageServiceInterface.php` | storage read support |
| `app/Providers/AppServiceProvider.php` | service bindings |
| `config/ai.php` | provider configuration and limits |

## Recommended Rollout

1. Implement Phase 0 first so job contracts, requests, and tests agree.
2. Implement Phase 1 next so the AI pipeline gets better source data before prompt work.
3. Implement Phase 2 once source text is materially improved.
4. Implement Phase 3 before broad rollout to reduce flaky provider behavior.
5. Implement Phase 4 last in v1 to improve quality without expanding infrastructure scope.
