# Web Portal — Implementation Progress

## Current Phase: Phase 1 complete — Phase 2 next (Auth & Middleware)
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
| Backend | Phase 1 complete | — | Plan Phase 2 (Auth & Middleware) |
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
| 1 — Schema & models | Backend | complete | — | — | All tasks done, 1205 tests pass |
| 2 — Auth & middleware | Backend | pending | — | — | blocked by 1 |
| 3 — Student web portal | Backend | pending | — | — | blocked by 2 |
| 4 — Parent web portal | Backend | pending | — | — | blocked by 2, parallel with 3 |
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
