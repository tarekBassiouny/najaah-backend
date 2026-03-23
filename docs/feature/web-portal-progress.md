# Web Portal — Implementation Progress

## Current Phase: Phase 4 complete — Phase 5A next (Admin Parent API)
## Last Updated: 2026-03-23

---

## Parallel Lanes

Three lanes run concurrently. Backend leads, frontend follows via API contracts.

```
LANE 1: Backend (najaah-backend)
LANE 2: Admin Frontend (najaah-frontend — admin panel changes)
LANE 3: Web Portal Frontend (najaah-frontend — new portal SPA)
```

### Lane Status

| Lane | Current Phase | Blocked By | Next Action |
|------|--------------|------------|-------------|
| Backend | Phase 4 complete | — | Plan Phase 5A (Admin Parent API) |
| Admin Frontend | Phase 0B complete | — | PR #78 submitted, awaiting 0C verification |
| Web Portal Frontend | not started | Backend Phase 2 | Wait for auth contract |

---

## Phase Tracker

| Phase | Lane | Status | PR | Contract Shared | Notes |
|-------|------|--------|-----|----------------|-------|
| 0A — Catalog-driven services | Backend | complete | #280 | done | All 8 tasks done, merged to dev, release PR #284 |
| 0B — Settings UI cards | Admin FE | complete | FE #78 | — | Feature group cards committed, PR to dev |
| 0C.1 — Add feature_group to existing entries | Backend | complete | — | — | Already done in Phase 0A |
| 0C.2-4 — Verify existing features in cards | Admin FE | pending | — | — | blocked by 0B merge |
| 1 — Schema & models | Backend | complete | #286 | — | All tasks done, 1205 tests pass |
| 2 — Auth & middleware | Backend | complete | #287 | — | All tasks done, 1205 tests pass |
| 3 — Student web portal | Backend | complete | #288 | — | All tasks done, reused 18 mobile controllers |
| 4 — Parent web portal | Backend | complete | — | — | All tasks done, 7 controllers, 2 services, 4 events |
| 5A — Admin parent API | Backend | pending | — | — | blocked by 3+4 |
| 5B — Admin parent UI | Admin FE | pending | — | — | blocked by 5A |
| 6 — Quality | Backend | pending | — | — | blocked by 3+4+5 |
| 7 — Docs & Postman | Backend | pending | — | — | blocked by 6 |

---

## Phase Review Gate

No phase moves to implementation until this gate is completed for that phase.

### Standard Gate

| Check | Required |
|-------|----------|
| Phase plan re-read | yes |
| Nearby code inspected | yes |
| Affected files/modules listed | yes |
| Contract impact checked | yes |
| Tests/verification approach identified | yes |
| Phase adjustments recorded before coding | yes |
| Explicit approval to implement | yes |

### Gate Record Template

```text
PHASE
- <phase name>

PLAN REVIEWED
- yes | no

CODE INSPECTED
- files/modules:

CONTRACT IMPACT
- none | settings | auth | student API | parent API | admin API

RISKS / ADJUSTMENTS
- concrete change from plan after code review

VERIFICATION PLAN
- exact tests/checks to run for this phase

APPROVED TO IMPLEMENT
- yes | no
- approved by:
- date:
```

### Gate Record: Phase 0A

```text
PHASE
- 0A — Backend: Catalog-Driven Services

PLAN REVIEWED
- yes

CODE INSPECTED
- config/settings_catalog.php
- app/Services/Settings/PolicySettingsService.php
- app/Services/Settings/CenterSettingsService.php
- app/Services/Settings/SettingsResolverService.php
- app/Services/Settings/CenterSettingsPageService.php

CONTRACT IMPACT
- settings — API adds sections.feature_groups; catalog entries gain value_key + feature_group

RISKS / ADJUSTMENTS
- value_key is metadata only; normalizeSystemValue() keeps per-key match
- CenterSettingsService features guard kept separate from catalog-driven limits
- SettingsResolverService handles view_limit <-> default_view_limit via key swap
- depends_on metadata added for future web_playback -> web_access cascade

VERIFICATION PLAN
- PHPStan level 7: passed (0 errors)
- Pint: passed (all files)
- New tests: system_limit, system_override, feature_flag, feature_groups API

APPROVED TO IMPLEMENT
- yes
- approved by: user
- date: 2026-03-22
```

### Gate Record: Phase 1

```text
PHASE
- 1 — Architecture (Schema & Models)

PLAN REVIEWED
- yes

CODE INSPECTED
- app/Models/User.php — is_student pattern, fillable, casts, relations
- app/Models/UserDevice.php — device_type column (nullable string), status enum cast, scopes
- app/Models/JwtToken.php — fillable, casts, device_id nullable FK
- app/Services/Devices/DeviceService.php — register() revokes all other devices
- config/settings_catalog.php — catalog entry structure, feature_group/value_key from Phase 0A
- app/Enums/ — int-backed and string-backed enum patterns
- app/Support/ErrorCodes.php — const pattern
- database/seeders/ — SystemSettingSeeder (updateOrCreate), CenterSettingSeeder (factory)
- database/factories/ — UserFactory, UserDeviceFactory, JwtTokenFactory
- docs/feature/web-portal-settings-governance.md — all 11 settings with catalog entries

CONTRACT IMPACT
- settings — 3 feature flags, 4 center settings, 4 system settings added to catalog
- No new API endpoints (schema/model only)

RISKS / ADJUSTMENTS
- DeviceType enum cast: cannot auto-cast at model level because existing device_type column
  has free-text values. Added deviceTypeParsed() method using tryFrom() instead. Scopes
  compare against enum values directly (works with raw DB strings).
- Unique index name on parent_student_links was too long for MySQL — used explicit short name.
- DeviceService pool-aware logic deferred to Phase 2 (Phase 1 only adds model scopes).
- Updated UserDeviceFactory default device_type from 'device-type' to DeviceType::Mobile->value.

VERIFICATION PLAN
- PHPStan level 7: 0 errors
- Pint: all 1389 files pass
- All 1205 tests pass (4931 assertions, 0 failures)
- Migrations: all 4 new migrations run successfully (fresh + seed)

APPROVED TO IMPLEMENT
- yes
- approved by: user
- date: 2026-03-23
```

### Gate Record: Phase 2

```text
PHASE
- 2 — Auth & Middleware

PLAN REVIEWED
- yes

CODE INSPECTED
- config/auth.php — guard config, existing mobile/admin guards
- config/cors.php — existing allowed_origins_patterns
- app/Services/Auth/JwtService.php — create() signature, token creation
- app/Services/Auth/Contracts/JwtServiceInterface.php — create() contract
- app/Services/Devices/DeviceService.php — register(), device pool logic
- app/Services/Devices/Contracts/DeviceServiceInterface.php — interface contract
- app/Http/Middleware/JwtStudentMiddleware.php — existing mobile JWT middleware pattern
- app/Http/Middleware/EnsureGuestBrowsingAllowed.php — PolicySettingsService usage pattern
- app/Services/Settings/PolicySettingsService.php — resolveCenterPolicy signature
- app/Providers/AppServiceProvider.php — service bindings
- bootstrap/app.php — middleware registration, route groups

CONTRACT IMPACT
- auth — 2 new JWT guards (web-student, web-parent), 3 new middleware aliases
- settings — PolicySettingsService used for allow_web_access/allow_parent_portal checks
- New API routes: POST /api/v1/web/student/auth/* and /api/v1/web/parent/auth/*

RISKS / ADJUSTMENTS
- SettingsResolverService cannot be used for web access checks (different signature).
  Used PolicySettingsService::resolveCenterPolicy(Center) instead — same pattern as
  EnsureGuestBrowsingAllowed middleware.
- JwtService::create() signature extended with optional TokenPlatform param (backward compatible).
- DeviceService::registerWeb() added as separate method — web device pool is independent
  from mobile devices (no reinstall detection, no pre-approved requests).
- Parent web device limit set to 99 (effectively unlimited) — parents are not device-restricted.

VERIFICATION PLAN
- PHPStan level 7: 0 errors
- Pint + Rector: all files pass (composer fix)
- All 1205 tests pass (4931 assertions, 0 failures)

APPROVED TO IMPLEMENT
- yes
- approved by: user
- date: 2026-03-23
```

### Gate Record: Phase 3

```text
PHASE
- 3 — Student Web Portal (Feature Parity)

PLAN REVIEWED
- yes

CODE INSPECTED
- routes/api/v1/mobile.php — mobile route structure and controller reuse pattern
- routes/api/v1/mobile/education.php — education lookups wrapped in jwt.mobile
- app/Http/Controllers/Mobile/MeController.php — device-specific logic (activeDevice relation)
- app/Http/Resources/Mobile/StudentUserResource.php — device field conditional on relation
- app/Services/Playback/PlaybackAuthorizationService.php — device resolution, assertCanStartPlayback
- app/Services/Playback/PlaybackService.php — requestPlayback flow
- app/Http/Middleware/JwtWebStudentMiddleware.php — request attribute bindings
- All 18 mobile controllers confirmed platform-agnostic (no mobile-specific logic)

CONTRACT IMPACT
- student API — full student web API at /api/v1/web/student/*
- playback — allow_web_playback check added to PlaybackAuthorizationService

RISKS / ADJUSTMENTS
- Middleware did not set token_platform on request attributes — added it so
  PlaybackAuthorizationService can detect web requests for allow_web_playback check.
- Web MeController omits device loading (no activeDevice relation set) — StudentUserResource
  already handles this gracefully via conditional `relationLoaded('activeDevice')` check.
- Education routes re-declared in web/student.php (cannot include mobile/education.php
  directly because it wraps routes in jwt.mobile middleware).
- DeviceChangeRequestController excluded from web routes (mobile-only feature).

VERIFICATION PLAN
- PHPStan level 7: 0 errors
- Pint + Rector: all files pass (composer fix)
- All tests pass (0 failures)

APPROVED TO IMPLEMENT
- yes
- approved by: user
- date: 2026-03-23
```

### Gate Record: Phase 4

```text
PHASE
- 4 — Parent Web Portal (Read-Only Dashboard)

PLAN REVIEWED
- yes

CODE INSPECTED
- app/Models/ParentStudentLink.php — relations, scopes (forParent/forStudent take int, not User)
- app/Models/Enrollment.php — relations, scopes (active, notDeleted, forUser)
- app/Models/QuizAttempt.php — quiz relation, answers relation
- app/Models/AssignmentSubmission.php — assignment relation, status enum
- app/Services/Assessments/AssessmentProgressService.php — getProgressSummary signature
- app/Http/Controllers/Mobile/WeeklyActivityController.php — activity aggregation pattern
- app/Support/AuditActions.php — constant naming pattern
- app/Exceptions/DomainException.php — errorCode()/statusCode() method names
- app/Providers/AppServiceProvider.php — service binding pattern

CONTRACT IMPACT
- parent API — full parent dashboard at /api/v1/web/parent/*
- audit — 5 new audit action constants for parent link lifecycle
- events — 4 domain events dispatched (no listeners in MVP)

RISKS / ADJUSTMENTS
- ParentStudentLink scopes (forParent, forStudent, forCenter) accept int, not User — must
  pass $user->id, not $user directly.
- DomainException uses errorCode()/statusCode() (not getErrorCode()/getStatusCode()) — fixed
  in LinkController.
- Rector renamed catch variable from $e to $domainException (CatchExceptionNameMatchingTypeRector).
- Weekly activity query logic duplicated from WeeklyActivityController into ParentProgressService
  because the existing controller is student-scoped (checks is_student) and the parent variant
  queries a different student's data.

VERIFICATION PLAN
- PHPStan level 7: 0 errors
- Pint + Rector: all files pass (composer fix)
- All tests pass (0 failures)

APPROVED TO IMPLEMENT
- yes
- approved by: user
- date: 2026-03-23
```

### Gate Rule

- `pending` means not reviewed yet.
- `reviewed` means code was inspected and phase adjustments are recorded, but coding has not started.
- `in_progress` is allowed only after the gate record is filled and approval is explicit.

---

## Frontend Contract Tracker

Backend publishes a contract doc after each phase that adds/changes endpoints.
Frontend can start building from the contract before the backend PR is merged.

| Contract | Generated After | Location | Status | Frontend Can Start |
|----------|----------------|----------|--------|--------------------|
| Settings API (feature_groups) | Phase 0A | `docs/contracts/student-parent-web-portal/settings-feature-groups.md` | draft (from implementation) | Phase 0B |
| Auth API (student + parent) | Phase 2 | `docs/contracts/student-parent-web-portal/web-auth-api.md` | not started | Portal scaffold + auth pages |
| Student Web API | Phase 3 | `docs/contracts/student-parent-web-portal/web-student-api.md` | not started | Student dashboard + pages |
| Parent Web API | Phase 4 | `docs/contracts/student-parent-web-portal/web-parent-api.md` | not started | Parent dashboard + pages |
| Admin Parent API | Phase 5A | `docs/contracts/student-parent-web-portal/admin-parent-api.md` | not started | Phase 5B admin UI |
| Full Handoff Doc | Phase 7 | `docs/feature/student-parent-web-portal-api.md` | not started | Final integration |

---

## Parallel Execution Timeline

```
Week N:   Backend Phase 0A
Week N+1: Backend Phase 0C.1  ──────────  Admin FE Phase 0B (parallel)
          Backend Phase 0C.2-4 + Admin FE verification
Week N+2: Backend Phase 1
Week N+3: Backend Phase 2  ─────────────  Portal FE: scaffold + auth (from contract)
Week N+4: Backend Phase 3  ──  Phase 4    Portal FE: student pages (from contract)
          (parallel)                      Portal FE: parent pages (from contract)
Week N+5: Backend Phase 5A ─────────────  Admin FE Phase 5B (from contract)
Week N+6: Backend Phase 6
          Backend Phase 7
          Portal FE: integration testing against real API
```

**Rules:**
- Backend publishes contract doc BEFORE starting implementation (from plan + route design)
- Backend completes the Phase Review Gate BEFORE starting implementation for that phase
- Frontend builds against contract, flags mismatches during integration
- Contract doc is the source of truth until the real API is live
- Once API is live, contract doc becomes the handoff doc (Phase 7)

---

## Decisions Changed

Track any deviations from the approved plan here:

| Date | Phase | Decision | Reason |
|------|-------|----------|--------|
| — | — | — | — |

---

## Open Blockers

| Blocker | Affects | Owner | Status |
|---------|---------|-------|--------|
| — | — | — | — |
