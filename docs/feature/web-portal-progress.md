# Web Portal — Implementation Progress

## Current Phase: Not Started
## Last Updated: 2026-03-22

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
| Backend | not started | — | Start Phase 0A |
| Admin Frontend | not started | Backend Phase 0A | Wait for 0A contract |
| Web Portal Frontend | not started | Backend Phase 2 | Wait for auth contract |

---

## Phase Tracker

| Phase | Lane | Status | PR | Contract Shared | Notes |
|-------|------|--------|-----|----------------|-------|
| 0A — Catalog-driven services | Backend | pending | — | — | |
| 0B — Settings UI cards | Admin FE | pending | — | — | blocked by 0A |
| 0C — Retroactive cleanup | Backend + Admin FE | pending | — | — | blocked by 0A+0B |
| 1 — Schema & models | Backend | pending | — | — | blocked by 0 |
| 2 — Auth & middleware | Backend | pending | — | — | blocked by 1 |
| 3 — Student web portal | Backend | pending | — | — | blocked by 2 |
| 4 — Parent web portal | Backend | pending | — | — | blocked by 2, parallel with 3 |
| 5A — Admin parent API | Backend | pending | — | — | blocked by 3+4 |
| 5B — Admin parent UI | Admin FE | pending | — | — | blocked by 5A |
| 6 — Quality | Backend | pending | — | — | blocked by 3+4+5 |
| 7 — Docs & Postman | Backend | pending | — | — | blocked by 6 |

---

## Frontend Contract Tracker

Backend publishes a contract doc after each phase that adds/changes endpoints.
Frontend can start building from the contract before the backend PR is merged.

| Contract | Generated After | Location | Status | Frontend Can Start |
|----------|----------------|----------|--------|--------------------|
| Settings API (feature_groups) | Phase 0A | `docs/contracts/web-portal/settings-feature-groups.md` | not started | Phase 0B |
| Auth API (student + parent) | Phase 2 | `docs/contracts/web-portal/web-auth-api.md` | not started | Portal scaffold + auth pages |
| Student Web API | Phase 3 | `docs/contracts/web-portal/web-student-api.md` | not started | Student dashboard + pages |
| Parent Web API | Phase 4 | `docs/contracts/web-portal/web-parent-api.md` | not started | Parent dashboard + pages |
| Admin Parent API | Phase 5A | `docs/contracts/web-portal/admin-parent-api.md` | not started | Phase 5B admin UI |
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
