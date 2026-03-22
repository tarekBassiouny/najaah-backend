# Najaah LMS — Agentic Workflow Guide

## Agent Inventory

### Backend (`najaah-backend`)
| Agent | Command | When to Use |
|-------|---------|-------------|
| `orchestrator` | `claude --agent orchestrator` | Complex multi-layer work, new features, coordinated bug fixes |
| `feature-builder` | `claude --agent feature-builder` | Implement a planned phase end-to-end |
| `reviewer` | `claude --agent reviewer` | Validate changes before commit or PR |
| `contract-generator` | `claude --agent contract-generator` | Generate frontend handoff docs after a backend phase |
| `cross-repo` | `claude --agent cross-repo` | Check lane status and contract drift across repos |

### Frontend (`najaah-frontend`)
| Agent | Command | When to Use |
|-------|---------|-------------|
| `orchestrator` | `claude --agent orchestrator` | Complex multi-layer work, new features, coordinated bug fixes |
| `feature-builder` | `claude --agent feature-builder` | Implement frontend from a backend contract |
| `reviewer` | `claude --agent reviewer` | Validate changes before commit or PR |

### Skills (loaded on-demand by agents or directly)
| Skill | Trigger |
|-------|---------|
| `najaah` / `lms` | Always loaded first — master context |
| `najaah-orchestrator` / `lms-orchestrator` | Multi-step coordination |
| `najaah-architecture` | Schema, migrations, indexes |
| `najaah-features` | Services, authorization, domain rules |
| `najaah-api` | Routes, controllers, resources |
| `najaah-quality` / `lms-qa` | Tests, lint, review |
| `lms-frontend` | Components, hooks, services, routes |
| `lms-pm` | Scope framing, capability mapping |
| `lms-review` | Code review checklist |

---

## Workflow 1: New Feature (Multi-Phase, Cross-Repo)

**Example:** Student & Parent Web Portal

### Step 1 — Plan (backend orchestrator)
```bash
cd najaah-backend
claude --agent orchestrator
```
> "Plan the student-parent web portal feature. We need web login for students, parent portal with read-only access, and admin parent management."

The orchestrator will:
1. Load master skill → discover domain rules
2. Inspect existing auth, device, and user infrastructure
3. Produce a phased plan with parallel lanes
4. Ask for approval before any implementation

**Output:** Plan doc at `docs/feature/student-parent-web-portal.md` + progress tracker at `docs/feature/web-portal-progress.md`

### Step 2 — Implement Phase 0A (backend feature-builder)
```bash
claude --agent feature-builder
```
> "Implement Phase 0A from docs/feature/student-parent-web-portal.md — catalog-driven settings services"

The feature-builder will:
1. Read the phase plan
2. Complete the phase review gate (inspect code, list affected files)
3. Execute: migrations → services → API updates → tests
4. Run `composer quality`
5. Report what was done

### Step 3 — Generate contract (backend contract-generator)
```bash
claude --agent contract-generator
```
> "Generate the settings feature groups contract for Phase 0A of student-parent-web-portal"

Creates `docs/contracts/student-parent-web-portal/settings-feature-groups.md` with endpoint table, request/response shapes, permissions.

### Step 4 — Review before PR (backend reviewer)
```bash
git add -A
claude --agent reviewer
```
> "Review staged changes for Phase 0A"

Runs Pint + PHPStan + tests + PR checklist. Reports blockers/warnings/suggestions.

### Step 5 — Frontend builds from contract (frontend feature-builder)
```bash
cd najaah-frontend
claude --agent feature-builder
```
> "Implement Phase 0B from docs/contracts/student-parent-web-portal/settings-feature-groups.md"

The frontend feature-builder will:
1. Read the backend contract
2. Create types, services, hooks, components, route, tests
3. Run lint + type-check + tests
4. Report

### Step 6 — Check cross-repo status
```bash
cd najaah-backend
claude --agent cross-repo
```
> "Check status of student-parent-web-portal across both repos"

Reports: backend Phase 0A done, contract published, frontend Phase 0B in progress, no drift detected.

---

## Workflow 2: Bug Fix (Single Layer)

**Example:** Course enrollment count returns wrong number for center admin

### Option A — Direct skill (simple fix)
```bash
cd najaah-backend
claude
```
> "@najaah-features The enrollment count for center admin is wrong — it's showing all enrollments instead of center-scoped ones. Fix the EnrollmentService."

Claude loads the features skill, inspects the service, finds the missing `center_id` scope, fixes it, adds a test.

### Option B — Orchestrator (if unsure where the bug lives)
```bash
claude --agent orchestrator
```
> "Center admin sees wrong enrollment count. Investigate and fix."

The orchestrator will:
1. Discover: check EnrollmentService, EnrollmentController, EnrollmentResource
2. Identify: missing center scope in the query
3. Plan: single-phase fix (features layer only)
4. Ask for approval
5. Fix and test

---

## Workflow 3: Bug Fix (Multi-Layer)

**Example:** Device binding allows duplicate web devices

### Step 1 — Investigate with orchestrator
```bash
claude --agent orchestrator
```
> "Students can register multiple web devices beyond web_device_limit. Investigate and fix."

The orchestrator will:
1. Load master skill
2. Inspect DeviceService, UserDevice model, device registration flow
3. Find: web device check uses wrong scope (checking all devices, not just web)
4. Plan: schema scope fix (architecture) → service logic fix (features) → test coverage (quality)
5. Ask for approval

### Step 2 — Review
```bash
claude --agent reviewer
```
> "Review the device binding fix"

---

## Workflow 4: Code Review / PR Prep

**Example:** Review a teammate's PR or your own changes

### Backend
```bash
cd najaah-backend
claude --agent reviewer
```
> "Review all changes on this branch against dev"

The reviewer agent:
1. Diffs the branch against dev
2. Reads all changed files
3. Runs Pint, PHPStan, tests
4. Applies the full PR checklist (multi-tenancy, auth, contracts, tests)
5. Reports: ready / needs-fixes / needs-discussion

### Frontend
```bash
cd najaah-frontend
claude --agent reviewer
```
> "Review staged changes"

Same flow: lint, type-check, tests, review checklist (TypeScript, React Query, services, forms, route protection, tenancy, security).

---

## Workflow 5: Add a New Admin Setting

**Example:** Add `max_quiz_attempts` center setting

```bash
cd najaah-backend
claude
```
> "@najaah-features Add a new center setting max_quiz_attempts with default 3. Center admins should be able to override it."

Claude loads the features skill, which tells it to check `references/settings-classification.md`. It will:
1. Classify: center setting (owned by center admin)
2. Add catalog entry with `value_key`, `feature_group`
3. Add migration for default value
4. Update the settings service
5. Add test coverage

---

## Workflow 6: Frontend Feature from Existing Contract

**Example:** Build the parent management page from the admin-parent API contract

```bash
cd najaah-frontend
claude --agent feature-builder
```
> "Build the admin parent management feature from docs/contracts/student-parent-web-portal/admin-parent-api.md"

The feature-builder will:
1. Read the contract → extract endpoints, permissions, response shapes
2. Create `src/features/parents/types/parent.ts`
3. Create `src/features/parents/services/parents.service.ts`
4. Create `src/features/parents/hooks/use-parents.ts`
5. Create `src/features/parents/components/ParentsTable.tsx`
6. Add page at `src/app/(dashboard)/centers/[centerId]/parents/page.tsx`
7. Update sidebar and capabilities
8. Add tests with MSW handlers
9. Run checks and report

---

## Workflow 7: Quick Investigation

**Example:** Understand how video playback security works

```bash
cd najaah-backend
claude
```
> "@najaah Explain how video playback security works — signed URLs, view limits, and device binding."

Claude loads the master skill, reads the domain rules reference, then inspects the actual PlaybackService, ViewLimitService, and BunnyStream integration to give an accurate answer.

---

## Workflow 8: Cross-Repo Status Check

**Example:** "Where are we on the web portal?"

```bash
cd najaah-backend
claude --agent cross-repo
```
> "What's the current status of student-parent-web-portal across both repos?"

The cross-repo agent:
1. Reads `docs/feature/web-portal-progress.md`
2. Checks contract docs in `docs/contracts/student-parent-web-portal/`
3. Reads frontend git log for related changes
4. Reports:
   - Backend: Phase 0A done, Phase 1 pending
   - Admin Frontend: Phase 0B blocked by Phase 0A contract (now available)
   - Portal Frontend: blocked by Phase 2 auth contract
   - Contracts: settings-feature-groups (draft), others (draft/skeleton)
   - Next action: start backend Phase 1, unblock frontend Phase 0B

---

## Auto-Formatting Hooks

Both repos have PostToolUse hooks that auto-format after every Write/Edit:
- **Backend:** Pint formats PHP files
- **Frontend:** Prettier formats TS/TSX/JS files

These run automatically — no manual step needed.

---

## Quick Reference: Which Agent for What

| Scenario | Agent | Repo |
|----------|-------|------|
| Plan a new feature | `orchestrator` | backend |
| Implement a planned phase | `feature-builder` | backend or frontend |
| Fix a bug (simple) | direct skill (`@najaah-features`) | backend |
| Fix a bug (complex) | `orchestrator` | backend |
| Generate API contract | `contract-generator` | backend |
| Build frontend from contract | `feature-builder` | frontend |
| Review before PR | `reviewer` | backend or frontend |
| Check cross-repo progress | `cross-repo` | backend |
| Investigate code | direct skill (`@najaah`) | backend |
| Add a setting | direct skill (`@najaah-features`) | backend |
