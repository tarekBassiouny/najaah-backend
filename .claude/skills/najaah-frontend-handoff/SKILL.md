---
name: najaah-frontend-handoff
description: Generate API contract docs for frontend teams after backend phases. Use after implementing endpoints to unblock parallel frontend work.
---

# Najaah Frontend Handoff Skill

## Use This Skill When
- A backend phase that adds or changes endpoints is complete (or design is stable)
- Frontend team needs to start building against the API before the backend PR merges
- Generating the final handoff doc in Phase 7

## Mandatory Load Order
1. Read `.claude/skills/najaah/SKILL.md`
2. Read `docs/feature/web-portal-progress.md` for current state
3. Read `docs/feature/student-parent-web-portal.md` for the relevant phase tasks

## Contract Doc Format

Generate a contract doc at `docs/contracts/{name}.md` with this structure:

```markdown
# {Phase Name} — API Contract
## Status: Draft | Final
## Generated: {date}
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
{which center settings control availability}

## Error Codes
{new error codes introduced in this phase}
```

## How To Generate

1. Read the route file for the phase (e.g., `routes/api/v1/web/student.php`)
2. Read each controller's methods — extract params, service calls, return types
3. Read each FormRequest — extract validation rules
4. Read each Resource — extract response shape
5. Read ErrorCodes for new codes added in this phase
6. Combine into the contract doc format above

If the phase is not yet implemented, generate from the plan:
1. Read the phase tasks in `student-parent-web-portal.md`
2. Read similar existing endpoints (mobile equivalents) for response shapes
3. Mark contract as `Status: Draft (from plan, not implemented)`

## After Generating

1. Save to `docs/contracts/{name}.md`
2. Update `docs/feature/web-portal-progress.md`:
   - Set contract status to `draft` or `final`
   - Update the frontend lane status if unblocked
3. Notify which frontend work is now unblocked

## Contract Lifecycle

```
Draft (from plan)  →  Draft (from implementation)  →  Final (after tests pass)
     │                        │                              │
     └─ FE can scaffold       └─ FE builds for real          └─ FE integration tests
```

- **Draft from plan**: Generated before backend implementation, based on plan + similar endpoints. Enough for frontend to scaffold components and mock data.
- **Draft from implementation**: Generated after backend code is written. Real request/response shapes from actual FormRequests and Resources.
- **Final**: Generated after Phase 6 tests pass. Guaranteed stable contract.

## References
- Plan: `docs/feature/student-parent-web-portal.md`
- Settings governance: `docs/feature/web-portal-settings-governance.md`
- Progress tracker: `docs/feature/web-portal-progress.md`
- Existing mobile routes (for response shape reference): `routes/api/v1/mobile.php`
- Existing admin API skill: `.claude/skills/najaah-api/references/admin-frontend-handoff.md`
