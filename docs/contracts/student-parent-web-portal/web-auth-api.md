# Student & Parent Web Portal — Web Auth API Contract

## Status: Complete (from implementation)
## Source Phase: 2 — Auth & Middleware
## Audience: Portal frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Base URL

All endpoints are prefixed with `/api/v1/web`.

---

## Required Headers (all endpoints)

| Header | Required | Description |
|--------|----------|-------------|
| `X-Api-Key` | **yes** | Center API key (resolves `center_id`) or system API key (unbranded / `center_id = null`). Missing or invalid key returns `401` with code `INVALID_API_KEY`. |
| `Authorization` | protected only | `Bearer <access_token>` — required on endpoints marked **protected**. |
| `Content-Type` | yes (POST) | `application/json` |

---

## Auth Modes

| Surface | Guard middleware | Login identity | Device binding |
|---------|-----------------|----------------|----------------|
| Student web | `jwt.web.student` | phone + OTP | yes, web device pool (limit from center policy) |
| Parent web | `jwt.web.parent` | phone + OTP | no practical limit (99) |

---

## Endpoints

### 1. Student Send OTP

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/student/send-otp` |
| **Auth** | public (X-Api-Key only) |
| **Throttle** | `throttle:otp-send` |
| **Status** | **implemented** |

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `phone` | string | yes | digits only, regex `^[1-9][0-9]{9}$` (auto-stripped of non-digits) |
| `country_code` | string | yes | max 8 chars, regex `^(\+\d{1,6}\|00\d{1,6})$` (auto-stripped of non-digit/non-plus chars) |

#### Success Response — `200`

```json
{
  "success": true,
  "token": "<otp-verification-token>"
}
```

`token` is an opaque string passed back to the verify endpoint.

#### Error Responses

| Code | HTTP | Condition |
|------|------|-----------|
| `INVALID_API_KEY` | 401 | Missing or invalid `X-Api-Key` header |
| `CENTER_INACTIVE` | 403 | Resolved center is not active |
| (validation) | 422 | Invalid phone or country_code |

---

### 2. Student Verify (Login)

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/student/verify` |
| **Auth** | public (X-Api-Key only) |
| **Throttle** | `throttle:otp-verify` |
| **Status** | **implemented** |

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `otp` | string | yes | The OTP code sent to the phone |
| `token` | string | yes | The opaque token returned by send-otp |
| `device_uuid` | string | no | Existing web device UUID; auto-generated if omitted |
| `device_name` | string | no | Browser/device name for display |
| `device_os` | string | no | OS identifier (e.g. "Windows", "macOS") |

#### Success Response — `200`

```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Ahmed",
    "phone": "5551234567",
    "is_student": true,
    "is_parent": false
  },
  "token": {
    "access_token": "<jwt>",
    "refresh_token": "<jwt>",
    "expires_in": 1800
  },
  "device_uuid": "c3a1f8e2-..."
}
```

#### Error Responses

| Code | HTTP | Message | Condition |
|------|------|---------|-----------|
| `INVALID_API_KEY` | 401 | Invalid API key. | Bad X-Api-Key |
| `CENTER_INACTIVE` | 403 | Center is not active. | Resolved center inactive |
| `OTP_INVALID` | 422 | Invalid OTP. | Wrong or expired OTP |
| `USER_NOT_FOUND_FOR_OTP` | 422 | No student account found for this phone number. | Phone not registered |
| `NOT_STUDENT` | 422 | Only students can log in here. | User exists but `is_student = false` |
| `CENTER_MISMATCH` | 422 | Center mismatch. | User belongs to a different center |
| `STUDENT_INACTIVE` | 403 | Student account is inactive. | User status is not Active |
| `STUDENT_BANNED` | 403 | Student account is banned. | User status is Banned |
| `WEB_ACCESS_DISABLED` | 403 | Web access is not enabled for this center. | Center policy `allow_web_access = false` |
| `WEB_DEVICE_LIMIT_REACHED` | 403 | Web device limit reached. | Device registration exceeds `web_device_limit` |

---

### 3. Student Refresh Token

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/student/refresh` |
| **Auth** | public (X-Api-Key only) |
| **Status** | **implemented** |

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `refresh_token` | string | yes | The refresh token issued at login |

#### Success Response — `200`

```json
{
  "success": true,
  "token": {
    "access_token": "<new-jwt>",
    "refresh_token": "<new-refresh-jwt>",
    "expires_in": 1800
  }
}
```

If the refresh token is invalid, expired, revoked, or belongs to a user whose center scope does not match the resolved `X-Api-Key`, the endpoint returns empty tokens instead of an error:

```json
{
  "success": true,
  "token": {
    "access_token": "",
    "refresh_token": "",
    "expires_in": 1800
  }
}
```

---

### 4. Student Me (Profile)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/v1/web/auth/student/me` |
| **Auth** | **protected** — `jwt.web.student` |
| **Status** | **implemented** |

#### Request Body

None.

#### Success Response — `200`

```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Ahmed",
    "phone": "5551234567",
    "is_student": true,
    "is_parent": false,
    "center_id": 1
  }
}
```

`center_id` is `null` for unbranded (Najaah org) students.

---

### 5. Student Logout

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/student/logout` |
| **Auth** | **protected** — `jwt.web.student` |
| **Status** | **implemented** |

#### Request Body

None.

#### Success Response — `200`

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

### 6. Parent Register

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/parent/register` |
| **Auth** | public (X-Api-Key only) |
| **Throttle** | `throttle:otp-send` |
| **Status** | **implemented** |

Resolves or creates a parent user, auto-links students by `parent_phone` match, then sends an OTP for verification.

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `phone` | string | yes | digits only, regex `^[1-9][0-9]{9}$` (auto-stripped) |
| `country_code` | string | yes | max 8 chars, regex `^(\+\d{1,6}\|00\d{1,6})$` (auto-stripped) |

#### Success Response — `200`

```json
{
  "success": true,
  "token": "<otp-verification-token>",
  "auto_linked": true
}
```

| Field | Type | Description |
|-------|------|-------------|
| `token` | string | Opaque OTP verification token — pass to parent verify |
| `auto_linked` | boolean | `true` if students in this center were auto-linked by matching `parent_phone` |

#### Business Rules

- If a user with the same phone + country_code + center already exists, that user is upgraded to `is_parent = true` (dual-role).
- If no user exists, a new parent-only user is created (`is_student = false`, `is_parent = true`).
- Auto-linking: finds students in the same center whose `parent_phone` matches and creates `ParentStudentLink` records with status `Active` and method `AutoMatched`.
- Race condition on user creation is handled via `UniqueConstraintViolationException` catch-and-resolve.

#### Error Responses

| Code | HTTP | Condition |
|------|------|-----------|
| `INVALID_API_KEY` | 401 | Bad X-Api-Key |
| `CENTER_INACTIVE` | 403 | Resolved center inactive |
| (validation) | 422 | Invalid phone or country_code |

---

### 7. Parent Send OTP

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/parent/send-otp` |
| **Auth** | public (X-Api-Key only) |
| **Throttle** | `throttle:otp-send` |
| **Status** | **implemented** |

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `phone` | string | yes | digits only, regex `^[1-9][0-9]{9}$` (auto-stripped) |
| `country_code` | string | yes | max 8 chars, regex `^(\+\d{1,6}\|00\d{1,6})$` (auto-stripped) |

#### Success Response — `200`

```json
{
  "success": true,
  "token": "<otp-verification-token>"
}
```

#### Error Responses

| Code | HTTP | Condition |
|------|------|-----------|
| `INVALID_API_KEY` | 401 | Bad X-Api-Key |
| `CENTER_INACTIVE` | 403 | Resolved center inactive |
| (validation) | 422 | Invalid phone or country_code |

---

### 8. Parent Verify (Login)

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/parent/verify` |
| **Auth** | public (X-Api-Key only) |
| **Throttle** | `throttle:otp-verify` |
| **Status** | **implemented** |

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `otp` | string | yes | The OTP code sent to the phone |
| `token` | string | yes | The opaque token from send-otp or register |
| `device_uuid` | string | no | Existing web device UUID; auto-generated if omitted |
| `device_name` | string | no | Browser/device name |
| `device_os` | string | no | OS identifier |

#### Success Response — `200`

```json
{
  "success": true,
  "data": {
    "id": 99,
    "name": "Parent",
    "phone": "5559876543",
    "is_parent": true
  },
  "token": {
    "access_token": "<jwt>",
    "refresh_token": "<jwt>",
    "expires_in": 1800
  },
  "device_uuid": "a7b2c3d4-..."
}
```

Note: parent verify response does **not** include `is_student` (unlike student verify).

#### Error Responses

| Code | HTTP | Message | Condition |
|------|------|---------|-----------|
| `INVALID_API_KEY` | 401 | Invalid API key. | Bad X-Api-Key |
| `CENTER_INACTIVE` | 403 | Center is not active. | Resolved center inactive |
| `OTP_INVALID` | 422 | Invalid OTP. | Wrong or expired OTP |
| `USER_NOT_FOUND_FOR_OTP` | 422 | No account found for this phone number. Please register first. | Phone not registered as parent |
| `UNAUTHORIZED` | 422 | This account is not registered as a parent. | User exists but `is_parent = false` |
| `CENTER_MISMATCH` | 422 | Center mismatch. | User belongs to a different center |
| `STUDENT_INACTIVE` | 403 | Account is inactive. | User status is not Active |
| `PARENT_PORTAL_DISABLED` | 403 | Parent portal is not enabled for this center. | Center policy `allow_parent_portal = false` |

---

### 9. Parent Refresh Token

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/parent/refresh` |
| **Auth** | public (X-Api-Key only) |
| **Status** | **implemented** |

#### Request Body

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `refresh_token` | string | yes | The refresh token issued at login |

#### Success Response — `200`

```json
{
  "success": true,
  "token": {
    "access_token": "<new-jwt>",
    "refresh_token": "<new-refresh-jwt>",
    "expires_in": 1800
  }
}
```

Scope mismatch returns empty tokens (same behavior as student refresh):

```json
{
  "success": true,
  "token": {
    "access_token": "",
    "refresh_token": "",
    "expires_in": 1800
  }
}
```

---

### 10. Parent Me (Profile)

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/v1/web/auth/parent/me` |
| **Auth** | **protected** — `jwt.web.parent` |
| **Status** | **implemented** |

#### Request Body

None.

#### Success Response — `200`

```json
{
  "success": true,
  "data": {
    "id": 99,
    "name": "Parent",
    "phone": "5559876543",
    "is_parent": true,
    "center_id": 1,
    "linked_students": [
      {
        "id": 42,
        "name": "Ahmed",
        "link_status": "Active"
      }
    ]
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `linked_students` | array | Active `ParentStudentLink` records with student id, name, and link status enum name |
| `center_id` | int\|null | `null` for unbranded parents |

---

### 11. Parent Logout

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/v1/web/auth/parent/logout` |
| **Auth** | **protected** — `jwt.web.parent` |
| **Status** | **implemented** |

#### Request Body

None.

#### Success Response — `200`

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

## Endpoint Summary

| # | Method | Path | Purpose | Auth | Status |
|---|--------|------|---------|------|--------|
| 1 | `POST` | `/api/v1/web/auth/student/send-otp` | Send student OTP | public | **implemented** |
| 2 | `POST` | `/api/v1/web/auth/student/verify` | Verify OTP, issue student web tokens | public | **implemented** |
| 3 | `POST` | `/api/v1/web/auth/student/refresh` | Refresh student access token | public | **implemented** |
| 4 | `GET` | `/api/v1/web/auth/student/me` | Get authenticated student profile | protected | **implemented** |
| 5 | `POST` | `/api/v1/web/auth/student/logout` | Revoke current student token | protected | **implemented** |
| 6 | `POST` | `/api/v1/web/auth/parent/register` | Register/resolve parent, auto-link, send OTP | public | **implemented** |
| 7 | `POST` | `/api/v1/web/auth/parent/send-otp` | Send parent OTP | public | **implemented** |
| 8 | `POST` | `/api/v1/web/auth/parent/verify` | Verify OTP, issue parent web tokens | public | **implemented** |
| 9 | `POST` | `/api/v1/web/auth/parent/refresh` | Refresh parent access token | public | **implemented** |
| 10 | `GET` | `/api/v1/web/auth/parent/me` | Get parent profile with linked students | protected | **implemented** |
| 11 | `POST` | `/api/v1/web/auth/parent/logout` | Revoke current parent token | protected | **implemented** |

---

## Error Codes Reference

All error codes used across Web Auth endpoints:

| Code | HTTP | Used In |
|------|------|---------|
| `INVALID_API_KEY` | 401 | All endpoints (middleware) |
| `CENTER_INACTIVE` | 403 | All public endpoints |
| `OTP_INVALID` | 422 | Student verify, Parent verify |
| `USER_NOT_FOUND_FOR_OTP` | 422 | Student verify, Parent verify |
| `NOT_STUDENT` | 422 | Student verify |
| `UNAUTHORIZED` | 422 | Parent verify |
| `CENTER_MISMATCH` | 422 | Student verify, Parent verify |
| `STUDENT_INACTIVE` | 403 | Student verify, Parent verify |
| `STUDENT_BANNED` | 403 | Student verify |
| `WEB_ACCESS_DISABLED` | 403 | Student verify |
| `PARENT_PORTAL_DISABLED` | 403 | Parent verify |
| `WEB_DEVICE_LIMIT_REACHED` | 403 | Student verify |

---

## Token Shape (TokenResource)

All token objects share this shape:

```json
{
  "access_token": "string",
  "refresh_token": "string",
  "expires_in": 1800
}
```

`expires_in` defaults to `1800` seconds (30 minutes) when not explicitly set by the JWT service.

---

## Implementation Sources

| Layer | File |
|-------|------|
| Routes | `routes/api/v1/web/auth.php` |
| Student controller | `app/Http/Controllers/Web/Auth/StudentAuthController.php` |
| Parent controller | `app/Http/Controllers/Web/Auth/ParentAuthController.php` |
| Student login action | `app/Actions/Web/WebStudentLoginAction.php` |
| Parent login action | `app/Actions/Web/WebParentLoginAction.php` |
| Web auth service | `app/Services/Auth/WebAuthService.php` |
| Web auth interface | `app/Services/Auth/Contracts/WebAuthServiceInterface.php` |
| OTP request | `app/Http/Requests/Web/WebOtpRequest.php` |
| Login request | `app/Http/Requests/Web/WebLoginRequest.php` |
| Parent register request | `app/Http/Requests/Web/ParentRegisterRequest.php` |
| Refresh token request | `app/Http/Requests/Mobile/RefreshTokenRequest.php` |
| Token resource | `app/Http/Resources/Mobile/TokenResource.php` |
| Error codes | `app/Support/ErrorCodes.php` |
| API key middleware | `app/Http/Middleware/ResolveCenterApiKey.php` |
