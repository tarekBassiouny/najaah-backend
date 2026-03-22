---
name: najaah-frontend-handoff
description: Generate API contract docs for frontend teams after backend phases. Use after implementing endpoints to unblock parallel frontend work on any feature.
---

# Najaah Frontend Handoff Skill

## Use This Skill When
- A backend phase that adds or changes endpoints is complete (or design is stable)
- Frontend team needs to start building against the API before the backend PR merges
- Generating a final handoff doc after all phases complete
- Any cross-repo feature work where backend leads and frontend follows

## Frontend Repo Location
`/Users/tarekbassiouny/projects/najaah-frontend`

## Mandatory Load Order
1. Read `.claude/skills/najaah/SKILL.md`
2. Find the feature's progress tracker at `docs/feature/{feature}-progress.md`
3. Read the feature plan doc for the relevant phase tasks

## Cross-Repo Contract Protocol

Backend and frontend develop in parallel using **contract-driven development**:

```
Backend implements phase
    → generates contract doc at docs/contracts/{feature}/{name}.md
    → updates docs/feature/{feature}-progress.md
    → frontend is unblocked

Frontend reads contract from backend repo
    → builds against contract shapes (with MSW mocks)
    → updates progress tracker lane status
    → integration tests when backend API is live
```

## Directory Structure

```
docs/contracts/
  {feature-slug}/
    {contract-name}.md       # one per phase that adds endpoints
docs/feature/
  {feature-slug}.md          # the plan
  {feature-slug}-progress.md # progress tracker (shared with frontend)
```

Example for web portal:
```
docs/contracts/
  student-parent-web-portal/
    settings-feature-groups.md
    web-auth-api.md
    web-student-api.md
    web-parent-api.md
    admin-parent-api.md
docs/feature/
  student-parent-web-portal.md
  web-portal-progress.md
```

## Contract Doc Format

Generate contract docs with this structure:

```markdown
# {Feature} — {Phase Name} API Contract
## Status: Draft (from plan) | Draft (from implementation) | Final
## Generated: {date}
## Feature: {feature name}
## Backend PR: {PR link or "not yet"}

---

## Endpoints

### {GROUP NAME}

#### {METHOD} {PATH}
- **Auth:** {guard/middleware}
- **Request:**
  - Headers: {required headers}
  - Body: {field: type, validation rules}
- **Response 200:**
  ```json
  {example response shape from Resource}
  ```
- **Error Responses:**
  - {code}: {when}
- **Notes:** {any behavior the frontend needs to know}

---

## Auth Flow
{step-by-step for this phase's auth, if applicable}

## Settings / Feature Flags
{which center settings control availability, if applicable}

## Error Codes
{new error codes introduced in this phase}

## Breaking Changes
{any changes from previous contract drafts — empty if first draft}
```

## How To Generate

### From Implementation (preferred)
1. Read the route file for the phase
2. Read each controller's methods — extract params, service calls, return types
3. Read each FormRequest — extract validation rules
4. Read each Resource — extract response shape
5. Read ErrorCodes for new codes added in this phase
6. Combine into the contract doc format above

### From Plan (before implementation)
1. Read the phase tasks in the feature plan doc
2. Read similar existing endpoints for response shapes
3. Mark contract as `Status: Draft (from plan, not implemented)`
4. Note which shapes are estimated vs confirmed

## After Generating

1. Save to `docs/contracts/{feature-slug}/{name}.md`
2. Update the feature's progress tracker:
   - Set contract status to `draft` or `final`
   - Update the frontend lane status if unblocked
3. State which frontend work is now unblocked

## Contract Lifecycle

```
Draft (from plan)  →  Draft (from implementation)  →  Final (after tests pass)
     │                        │                              │
     └─ FE can scaffold       └─ FE builds for real          └─ FE integration tests
```

- **Draft from plan**: Enough for frontend to scaffold components, set up types, and mock data.
- **Draft from implementation**: Real shapes from FormRequests and Resources. Frontend can build real API integration.
- **Final**: Tests pass. Guaranteed stable contract. Frontend can run integration tests.

## Progress Tracker Template

When starting a new cross-repo feature, create `docs/feature/{feature}-progress.md` with:

```markdown
# {Feature Name} — Implementation Progress

## Current Phase: {phase}
## Last Updated: {date}

## Lane Status
| Lane | Current Phase | Blocked By | Next Action |
|------|--------------|------------|-------------|
| Backend | {phase} | — | {action} |
| Admin Frontend | not started | Backend {phase} | Wait for contract |
| {Other FE lane} | not started | Backend {phase} | Wait for contract |

## Phase Tracker
| Phase | Lane | Status | PR | Contract | Notes |
|-------|------|--------|-----|----------|-------|
| ... | ... | pending/in-progress/done | — | — | |

## Frontend Contract Tracker
| Contract | Generated After | Location | Status | Frontend Can Start |
|----------|----------------|----------|--------|--------------------|
| ... | Phase X | docs/contracts/{feature}/{name}.md | not started | {what} |

## Decisions Changed
| Date | Phase | Decision | Reason |
|------|-------|----------|--------|

## Open Blockers
| Blocker | Affects | Owner | Status |
|---------|---------|-------|--------|
```

## References
- Existing mobile routes (for response shapes): `routes/api/v1/mobile.php`
- Existing admin routes: `routes/api/v1/admin/`
- Admin API handoff reference: `.claude/skills/najaah-api/references/admin-frontend-handoff.md`
