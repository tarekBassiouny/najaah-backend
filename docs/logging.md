# Logging Policy

This document defines what XYZ LMS logs to the database (audit) and to application logs, and what must never be logged. Audit logs already exist and must not change.

## Audit / Business Events (Database)
Audit logs capture user and business actions for accountability and compliance. These are written to the existing audit log tables only.

Log to audit DB when:
- Admin or system actions change business state (create/update/delete/publish).
- Role and permission changes (assign/revoke roles, permissions).
- Enrollment and access changes (enroll/unenroll, approve/reject requests).
- Center or course configuration changes.

Do not add operational details (stack traces, request payloads) to audit logs. Keep audit entries focused on who/what/when.

## Operational / System Logs (Laravel Logs)
Application logs are for operational health, debugging, and security observability only. They must not duplicate audit logs.

Log to application logs when:
- Background jobs succeed/fail or are retried.
- External integrations fail or respond unexpectedly (Bunny, email, storage).
- Authn/authz anomalies or unexpected access attempts.
- System faults (exceptions, timeouts, infrastructure issues).

Do not log normal, expected business actions already covered by audit logs.

## Log Severity
- `info`: Successful operations, key lifecycle milestones, expected state changes (e.g., job completed).
- `warning`: Recoverable issues, validation anomalies, retries, degraded behavior.
- `error`: Failed operations, exceptions, data corruption risk, security issues.

## Required Log Context Fields
All application logs must include:
- `center_id`: Center scope if applicable, otherwise `null`.
- `user_id`: Acting user if applicable, otherwise `null`.
- `request_id`: Request correlation id or trace id if available.
- `source`: Component or subsystem (e.g., `auth`, `jobs`, `bunny`, `centers`).

## Must Never Be Logged
- Passwords, tokens, refresh tokens, OTPs, or reset tokens.
- Full payment data or secrets/keys.
- Raw uploaded files or binary payloads.
- Full PII where not required (log ids, not full records).

## Examples
1) Job success (info)
```json
{
  "level": "info",
  "message": "Center onboarding email sent",
  "center_id": 12,
  "user_id": 45,
  "request_id": "req-abc123",
  "source": "jobs"
}
```

2) External integration failure (warning)
```json
{
  "level": "warning",
  "message": "Bunny library creation failed, retry scheduled",
  "center_id": 12,
  "user_id": null,
  "request_id": "job-789",
  "source": "bunny"
}
```

3) Security anomaly (error)
```json
{
  "level": "error",
  "message": "Admin token validation failed",
  "center_id": null,
  "user_id": 77,
  "request_id": "req-def456",
  "source": "auth"
}
```

4) System fault (error)
```json
{
  "level": "error",
  "message": "Database connection timeout",
  "center_id": null,
  "user_id": null,
  "request_id": "req-ghi789",
  "source": "system"
}
```
