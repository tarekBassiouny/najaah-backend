# Publish Readiness Checklist (Admin)

This document is the final authority for publish readiness checks in the admin backend.
It reflects current implementation and enforced guards.

## Course Readiness

### C1 — Structural Requirements
- Course exists and is not soft-deleted.
- Course belongs to the current admin center.
- Course has at least one section.

### C2 — Section State
- At least one section is visible and not soft-deleted.
- Note: there is no `published` flag on sections in the current model.

### C3 — Video Readiness (ALL attached videos)
- URL videos do not require an upload session.
- Uploaded videos require `video.upload_session_id`.
- Upload session exists.
- `upload_session.upload_status == READY`.
- `video.encoding_status == READY`.
- `video.lifecycle_status >= READY`.
- `video.center_id == course.center_id`.
- Once an uploaded video is already fully ready, later upload-session expiry does not block course publish.

### C4 — PDF Readiness (ALL attached PDFs)
- `pdf.upload_session_id` is present.
- Upload session exists.
- `upload_session.upload_status == READY`.
- `upload_session.expires_at` is null or in the future.
- `pdf.center_id == course.center_id`.

### C5 — No Bypasses
- Publishing fails if any required upload session is missing or failed.
- Publishing fails if any video is still encoding or any PDF was created without a session.

## Section Readiness

### S1 — Ownership & State
- Section exists and is not soft-deleted.
- Section belongs to the course.
- Course belongs to the current admin center.

### S2 — Attachments
- All attached videos satisfy C3.
- All attached PDFs satisfy C4.

## Video Readiness

- `encoding_status == READY`.
- URL videos do not require an upload session.
- Uploaded videos require `upload_session_id`.
- Session must be READY.
- Session expiry does not block publish once the uploaded video is already READY.

## PDF Readiness

- `upload_session_id` is required.
- Session must be READY and not expired.

## Webhook Constraints (Bunny)

- Bunny webhooks are treated as untrusted notifications.
- No signature or secret validation is supported.
- Events are ignored if the session is missing, expired, or the transition is invalid/duplicate.
