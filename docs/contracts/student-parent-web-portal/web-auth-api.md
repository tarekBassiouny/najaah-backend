# Student & Parent Web Portal — Web Auth API Contract

## Status: Draft
## Source Phase: 2 — Auth & Middleware
## Audience: Portal frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the auth surfaces for student web and parent web login, refresh, and logout.

---

## Auth Modes

| Surface | Guard | Login Identity | Device Binding |
|---------|-------|----------------|----------------|
| Student web | `jwt.web.student` | phone + OTP | yes, web pool |
| Parent web | `jwt.web.parent` | phone + OTP | no |

---

## Expected Endpoints

| Method | Path | Purpose | Status |
|--------|------|---------|--------|
| `POST` | `/api/v1/web/auth/student/send-otp` | Send student OTP | planned |
| `POST` | `/api/v1/web/auth/student/verify` | Verify OTP and issue student web tokens | planned |
| `POST` | `/api/v1/web/auth/student/refresh` | Refresh student web access token | planned |
| `POST` | `/api/v1/web/auth/student/logout` | Revoke current student web token | planned |
| `POST` | `/api/v1/web/auth/parent/register` | Resolve/create parent user before login | planned |
| `POST` | `/api/v1/web/auth/parent/send-otp` | Send parent OTP | planned |
| `POST` | `/api/v1/web/auth/parent/verify` | Verify OTP and issue parent web tokens | planned |
| `POST` | `/api/v1/web/auth/parent/refresh` | Refresh parent web access token | planned |
| `POST` | `/api/v1/web/auth/parent/logout` | Revoke current parent web token | planned |

---

## Key Business Rules

- Student web login follows the same OTP identity resolution as mobile.
- Parent registration reuses an existing same-center user by phone when present.
- A same-center user may become dual-role (`is_student = true`, `is_parent = true`).
- Student web device binding uses the web pool only.
- Mobile and web device pools are independent.

---

## Draft Response Requirements

### Student Verify/Login

Expected response should include:
- access token
- refresh token
- web device UUID
- authenticated student profile summary

### Parent Verify/Login

Expected response should include:
- access token
- refresh token
- parent profile summary
- linked-students summary or link-state metadata when useful

---

## Error Areas To Lock Later

- web access disabled
- parent portal disabled
- web device limit reached
- center mismatch
- inactive or banned student
- invalid OTP

---

## Open Items

- Confirm final request payload keys for student verify endpoint and whether it mirrors mobile naming.
- Confirm whether parent registration and parent OTP send remain separate endpoints or become one combined flow.
- Confirm whether login responses should include enough bootstrap data for dashboard routing.

