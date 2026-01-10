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
- `video.upload_session_id` is present.
- Upload session exists.
- `upload_session.upload_status == READY`.
- `upload_session.expires_at` is null or in the future.
- `video.encoding_status == READY`.
- `video.lifecycle_status >= READY`.
- `video.center_id == course.center_id`.

### C4 — PDF Readiness (ALL attached PDFs)
- `pdf.upload_session_id` is present.
- Upload session exists.
- `upload_session.upload_status == READY`.
- `upload_session.expires_at` is null or in the future.
- `pdf.center_id == course.center_id`.

### C5 — No Bypasses
- Publishing fails if any upload session is missing, failed, or expired.
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
- `upload_session_id` is required.
- Session must be READY and not expired.

## PDF Readiness

- `upload_session_id` is required.
- Session must be READY and not expired.

## Webhook Constraints (Bunny)

- Bunny webhooks are treated as untrusted notifications.
- No signature or secret validation is supported.
- Events are ignored if the session is missing, expired, or the transition is invalid/duplicate.
