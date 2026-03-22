# Web Portal — Parallel Workflow

## Overview

Backend and frontend develop in parallel using **contract-driven development**.
Backend publishes API contract docs after each phase. Frontend builds against
contracts without waiting for backend PRs to merge.

---

## Three Lanes

### Lane 1: Backend (najaah-backend)

Owns: API endpoints, services, schema, auth, middleware, tests.
Runs: Phase 0A → 0C → 1 → 2 → 3 || 4 → 5A → 6 → 7.

**After each phase that adds endpoints:**
1. Generate contract doc using `najaah-frontend-handoff` skill
2. Save to `docs/contracts/{name}.md`
3. Update `docs/feature/web-portal-progress.md`
4. Tag frontend team: "contract ready, you're unblocked for X"

### Lane 2: Admin Frontend (najaah-frontend — admin panel)

Owns: Settings UI cards, parent management pages, sidebar updates.
Runs: Phase 0B (after 0A contract) → 5B (after 5A contract).

**Workflow:**
1. Read contract doc from backend repo
2. Build UI components against contract shapes
3. Once backend PR merges, switch from mock data to real API
4. Integration test

### Lane 3: Web Portal Frontend (najaah-frontend — portal SPA)

Owns: Student login/dashboard, parent register/dashboard, all portal pages.
Runs: Scaffold (after Phase 2 contract) → Student pages (after Phase 3) → Parent pages (after Phase 4).

**Workflow:**
1. Read contract doc from backend repo
2. Build pages + components against contract shapes
3. Use MSW or similar for API mocking during development
4. Once backend PR merges, switch to real API
5. Integration test against staging

---

## Phase-by-Phase Parallel Map

### Phase 0A — Backend: Catalog-Driven Services
```
Backend:     Implement 0A.1 → 0A.9
             Generate: docs/contracts/settings-feature-groups.md
Frontend:    Waiting (nothing to do yet)
```

### Phase 0B + 0C — Settings UI + Retroactive Cleanup
```
Backend:     Implement 0C.1 (add feature_group to existing entries)
Admin FE:    Implement 0B.1 → 0B.6 (from settings contract)
             ↓
Both:        Verify 0C.2-4 together (existing features in new layout)
```

### Phase 1 — Schema & Models
```
Backend:     Implement 1.1 → 1.15
             No contract needed (no new endpoints)
Frontend:    Continue 0B/0C work or wait
```

### Phase 2 — Auth & Middleware
```
Backend:     Implement 2.1 → 2.18
             Generate: docs/contracts/web-auth-api.md
                       ↓
Portal FE:   Start scaffold:
             - Project setup (router, auth store, API client)
             - Student login page (OTP flow)
             - Parent register + login pages
             - Auth token management (JWT + refresh)
             - Web device UUID handling (localStorage)
```

### Phase 3 + 4 — Student Web + Parent Web (parallel)
```
Backend:     Phase 3 (student) ──── Phase 4 (parent)  [parallel]
             Generate:               Generate:
             web-student-api.md      web-parent-api.md
                  ↓                       ↓
Portal FE:   Student pages:         Parent pages:
             - Course list           - Linked students
             - Course detail         - Student progress
             - Video playback        - Quiz results
             - Quiz attempt          - Assignment status
             - Assignments           - Weekly activity
             - Progress              - Link request
             - Explore/search
```

### Phase 5A + 5B — Admin Parent Management
```
Backend:     Phase 5A (admin API)
             Generate: docs/contracts/admin-parent-api.md
                  ↓
Admin FE:    Phase 5B:
             - Parent list page
             - Parent detail page
             - Link management on student detail
             - Pending requests queue
             - Sidebar menu item
```

### Phase 6 + 7 — Quality + Docs
```
Backend:     Phase 6 (tests), Phase 7 (docs)
             Finalize all contract docs → status: Final
Portal FE:   Integration testing against real API
Admin FE:    Integration testing against real API
```

---

## Contract Doc Lifecycle

```
1. DRAFT FROM PLAN
   - Generated before backend implementation
   - Based on plan tasks + similar existing endpoints
   - Frontend can: scaffold components, set up routes, build UI with mock data
   - Source: student-parent-web-portal.md + existing mobile resources

2. DRAFT FROM IMPLEMENTATION
   - Generated after backend code is written
   - Real request/response shapes from FormRequests + Resources
   - Frontend can: build real API integration, remove mocks
   - Source: actual route files, controllers, resources

3. FINAL
   - Generated after Phase 6 tests pass
   - Guaranteed stable — no breaking changes
   - Frontend can: run integration tests, prepare for release
   - Source: tested + verified implementation
```

Frontend should NOT wait for Final to start — Draft from Plan is enough to scaffold.

---

## Communication Protocol

### Backend → Frontend signals:

| Signal | When | Format |
|--------|------|--------|
| "Contract ready: {phase}" | After generating contract doc | Update progress.md + ping team |
| "Contract updated: {phase}" | After implementation changes contract | Update contract doc + diff note |
| "API live: {phase}" | After backend PR merges to dev | Update progress.md |
| "Contract final: {phase}" | After Phase 6 tests pass | Update contract status to Final |

### Frontend → Backend signals:

| Signal | When | Format |
|--------|------|--------|
| "Contract mismatch: {detail}" | During integration testing | Issue or comment on progress.md |
| "Need clarification: {endpoint}" | During development | Question in progress.md blockers |
| "Integration verified: {phase}" | After successful integration | Update progress.md |

---

## How To Start a Phase (Orchestrator Checklist)

```
1. Read docs/feature/web-portal-progress.md
2. Confirm no blockers for this phase
3. Read the phase tasks in student-parent-web-portal.md
4. Load the relevant specialist skill
5. Execute tasks
6. If endpoints added/changed:
   a. Load najaah-frontend-handoff skill
   b. Generate/update contract doc
   c. Update progress tracker
7. Run quality checks (Pint, PHPStan, relevant tests)
8. Prepare PR via najaah-pr-workflow
9. Update progress tracker: phase status, lane unblocks
```

---

## Quick Reference: Who Builds What

| Component | Repo | Lane | Depends On |
|-----------|------|------|-----------|
| Settings catalog refactor | najaah-backend | Backend | nothing |
| Feature-as-Section-Header UI | najaah-frontend | Admin FE | Phase 0A contract |
| Schema + models + enums | najaah-backend | Backend | Phase 0 |
| JWT web middleware + auth | najaah-backend | Backend | Phase 1 |
| Portal scaffold + auth pages | najaah-frontend | Portal FE | Phase 2 contract |
| Student web routes | najaah-backend | Backend | Phase 2 |
| Student dashboard + pages | najaah-frontend | Portal FE | Phase 3 contract |
| Parent web services + routes | najaah-backend | Backend | Phase 2 |
| Parent dashboard + pages | najaah-frontend | Portal FE | Phase 4 contract |
| Admin parent API | najaah-backend | Backend | Phase 3+4 |
| Admin parent UI | najaah-frontend | Admin FE | Phase 5A contract |
| Test suite | najaah-backend | Backend | Phase 3+4+5 |
| Docs + Postman | najaah-backend | Backend | Phase 6 |
