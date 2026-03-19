# AI Auto-Transcription Pipeline

## Status

- Deferred
- Not part of AI content generation v1
- Backend architecture and contracts only

## Purpose

This document defines the deferred backend design for automatic video transcription.

The goal is to extend the current manual transcript workflow without changing the canonical transcript contract already used by AI content generation:
- `videos.transcript` remains the canonical plain-text transcript
- manual and automatic transcript ingestion both flow through the transcript service boundary
- AI content generation continues to depend on transcript readiness, not on provider-specific transcription state

## Goals

- Automatically generate transcripts for uploaded videos
- Keep transcript storage provider-agnostic
- Support async processing for long-running transcription work
- Allow admin review before auto-generated transcripts are treated as trusted
- Preserve compatibility with the current AI content generation flow
- Leave room for retries, limits, cost controls, and later provider expansion

## Non-Goals

- Not part of v1 AI content generation rollout
- No provider-specific implementation choice in this document
- No subtitle publishing UX
- No translation workflow
- No speaker diarization requirements
- No provider fallback logic
- No frontend implementation detail beyond backend contract implications

## Existing Foundation

Phase 1 already established the core foundation:
- videos can store normalized plain-text transcripts
- transcript ingestion is handled through a transcript service
- transcript metadata already distinguishes source and format
- AI content generation already blocks direct video-source generation when transcripts are missing

That means the auto-transcription pipeline should be additive, not a refactor of the existing transcript model.

## Core Design Principle

Auto-transcription should produce a candidate transcript, not directly overwrite trusted transcript state without control.

Backend behavior should distinguish:
- transcript generation
- transcript review/approval
- transcript publication to the canonical video transcript field

This keeps the AI generation dependency simple while preserving auditability and future provider flexibility.

## Canonical Contract

The canonical transcript contract should remain:
- `videos.transcript`: normalized plain text used by downstream consumers
- `videos.transcript_format`: normalized stored format, typically `txt`
- `videos.transcript_source`: `manual` or `auto`

AI content generation should continue to read only from canonical transcript fields plus readiness state. It should not need to understand provider job status.

## Recommended Data Model

### Option A: Minimal Video-Level State

Add transcription execution fields directly on `videos`:
- `transcription_status`
- `transcription_provider`
- `transcription_model`
- `transcription_requested_by`
- `transcription_requested_at`
- `transcription_started_at`
- `transcription_completed_at`
- `transcription_failed_at`
- `transcription_error`
- `transcription_job_reference`
- `transcription_review_status`

Pros:
- simple to query
- easy dashboard integration

Cons:
- weak history
- weak audit trail when transcripts are regenerated

### Option B: Dedicated `video_transcriptions` Table

Create a transcription record table and keep `videos` as the canonical published snapshot.

Suggested fields:
- `id`
- `video_id`
- `status`
- `provider`
- `model`
- `source_audio_path`
- `requested_by`
- `requested_at`
- `started_at`
- `completed_at`
- `failed_at`
- `error_message`
- `raw_payload`
- `normalized_transcript`
- `detected_language`
- `confidence_summary`
- `review_status`
- `reviewed_by`
- `reviewed_at`
- `published_at`
- `published_to_video_version`
- timestamps

Recommended status concepts:
- `pending`
- `queued`
- `processing`
- `completed`
- `failed`
- `cancelled`

Recommended review concepts:
- `pending_review`
- `approved`
- `rejected`
- `superseded`

Pros:
- supports history and retries cleanly
- easier future provider changes
- better auditability
- cleaner manual vs auto coexistence

Cons:
- more moving parts

### Recommendation

Use Option B.

If auto-transcription is deferred long enough that simplicity matters more than history, Option A is acceptable as a temporary bridge. But the long-term design should favor a dedicated `video_transcriptions` table with `videos` holding only the currently published transcript snapshot.

## State Model

There should be two separate state machines.

### Execution State

Execution state answers: has the provider-side transcription work finished?

Suggested values:
- `pending`
- `queued`
- `processing`
- `completed`
- `failed`
- `cancelled`

### Review State

Review state answers: should this transcript be trusted and published to the video record?

Suggested values:
- `pending_review`
- `approved`
- `rejected`
- `superseded`

This separation matters because a transcription can complete successfully but still require human review before publication.

## Service Boundaries

### TranscriptProviderInterface

The provider contract should abstract request, polling, normalization, and provider job references.

Suggested interface shape:

```php
interface TranscriptProviderInterface
{
    public function request(Video $video, array $options = []): TranscriptProviderRequestResult;

    public function poll(VideoTranscription $transcription): TranscriptProviderPollResult;

    public function cancel(VideoTranscription $transcription): void;

    public function normalizeCompletedPayload(array $payload): TranscriptNormalizationResult;
}
```

Notes:
- `request()` should not write directly to `videos`
- `poll()` should update transcription records, not canonical transcript fields
- normalization should return plain text plus any optional metadata

### VideoTranscriptionServiceInterface

Application-level orchestration should sit behind a dedicated service.

Suggested responsibilities:
- create transcription requests
- validate whether a video is eligible
- manage status transitions
- publish approved transcripts to the canonical video fields
- retry or cancel jobs
- prevent unsafe concurrent requests

Suggested interface shape:

```php
interface VideoTranscriptionServiceInterface
{
    public function request(Video $video, User $admin): VideoTranscription;

    public function refresh(VideoTranscription $transcription): VideoTranscription;

    public function approve(VideoTranscription $transcription, User $admin): Video;

    public function reject(VideoTranscription $transcription, User $admin, ?string $reason = null): VideoTranscription;

    public function retry(VideoTranscription $transcription, User $admin): VideoTranscription;

    public function cancel(VideoTranscription $transcription, User $admin): VideoTranscription;
}
```

### TranscriptService Reuse

The current transcript service should remain the only path that writes canonical transcript fields on `videos`.

That means approval/publish flow should eventually call something like:
- `saveTranscriptText(...)`
- or a dedicated `publishAutoTranscript(...)` method inside the transcript service

Direct model writes from provider jobs should be avoided.

## Queue and Job Architecture

Recommended job split:

1. `RequestVideoTranscriptionJob`
- submits the transcription request to the provider
- stores the provider job reference
- moves execution state to `queued` or `processing`

2. `PollVideoTranscriptionJob`
- checks provider completion state
- reschedules itself while processing is incomplete
- stores normalized transcript candidate when complete

3. `FinalizeVideoTranscriptionJob`
- optional
- runs normalization, summarization of confidence metadata, and review-state transitions

4. `ExpireStaleVideoTranscriptionsJob`
- optional maintenance job
- marks abandoned requests as failed or cancelled

### Queue Rules

- use a dedicated queue such as `transcriptions`
- keep backoff explicit and conservative
- treat provider rate limits and timeouts as retryable
- separate retry policy for request failures vs polling failures

## Eligibility Rules

The service should reject auto-transcription when:
- the video does not belong to the center scope
- the source media is missing or inaccessible
- the video exceeds duration or file-size limits
- another active transcription request already exists for the same video
- the center has reached configured transcription limits

The service should allow retriggering when:
- the latest request failed
- the latest request was rejected
- the latest approved transcript is intentionally being regenerated

## Publication Rules

When an auto-generated transcript is approved:
- publish normalized plain text into `videos.transcript`
- set `videos.transcript_format` to canonical text format
- set `videos.transcript_source` to `auto`
- preserve the transcription record as the audit source of the published transcript

When a manual transcript is saved later:
- canonical video fields should switch to the manual transcript
- manual transcript becomes the trusted active transcript
- any previous auto-transcription record remains historical only

When an auto transcript is rejected:
- keep the historical transcription record
- do not mutate canonical transcript fields

## Manual vs Auto Coexistence

The backend should define clear precedence rules:

- manual transcript is the highest-trust source
- approved auto transcript is valid when no newer manual transcript exists
- new manual transcript writes should supersede approved auto transcripts
- AI content generation should consume only the active canonical transcript

This avoids ambiguous behavior in the dashboard and downstream services.

## Admin API Contract Direction

This document avoids final endpoint design, but the backend should expose operations for:
- request transcription for a video
- fetch the latest transcription status
- list transcription history for a video
- approve a completed transcript
- reject a completed transcript
- retry a failed transcript
- cancel an active transcript when supported

The response contract should expose:
- execution status
- review status
- provider-neutral metadata
- error state
- whether the transcript is already published to the video

## Error Model

Suggested backend error categories:
- source media unavailable
- transcription already in progress
- transcription limit exceeded
- provider temporarily unavailable
- transcription failed permanently
- transcript not reviewable in current state
- transcript not publishable in current state

The public contract should use domain-level error codes, not provider-native error strings.

## Limits and Governance

Auto-transcription should eventually respect center-level controls similar to AI content generation:
- daily transcription job limit
- monthly transcription job limit
- max audio/video duration
- max media size
- concurrent transcription limit

The provider layer should remain hidden behind provider-neutral policy enforcement.

## Observability

Minimum backend observability expectations:
- structured logs for request, polling, approval, rejection, and failure
- audit trail for who requested, approved, rejected, or retried
- metrics for success rate, duration, retry count, and failure categories

## AI Content Generation Compatibility

AI content generation should not depend on the existence of transcription records.

It should continue to use:
- `videos.transcript`
- `videos.transcript_source`
- transcript readiness rules already established in v1

This preserves a clean boundary:
- transcription pipeline is responsible for producing trusted transcript state
- AI generation is responsible for consuming trusted transcript state

## Migration Strategy

Recommended rollout order:

1. Add transcription persistence model and status fields
2. Add provider-agnostic service contracts
3. Add request and polling jobs
4. Add admin status/read APIs
5. Add approve/reject/publish flow
6. Integrate dashboard UX
7. Add limits, metrics, and operational controls

## Open Decisions

- Should approval be mandatory before auto transcripts become canonical, or can some centers opt into auto-publish?
- Should multiple historical transcription attempts be kept forever or pruned?
- Should a new transcription request automatically supersede earlier failed/completed ones?
- Should detected language be stored even if no translation workflow exists yet?
- Should transcript history be versioned against video media replacements?

## Relationship To Existing Spec

This document is the deferred follow-up to Phase 5 in [ai-content-generation-enhancement.md](/Users/tarekbassiouny/projects/najaah-backend/docs/feature/ai-content-generation-enhancement.md).

That v1 document should remain focused on shipped AI content generation capabilities. This document owns the future auto-transcription design.
