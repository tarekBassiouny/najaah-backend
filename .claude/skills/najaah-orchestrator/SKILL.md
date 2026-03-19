---
name: najaah-orchestrator
description: Coordinate complex Najaah LMS backend work across architecture, feature, API, quality, and PR workflow skills. Use for new features, bug fixes, and multi-layer changes.
---

# Najaah Orchestrator

## Use This Skill When
- starting a new backend feature
- fixing a bug that touches multiple layers
- planning before implementation
- coordinating schema, service, API, and test work
- coordinating multiple specialists with shared memory or parallel lanes
- answering "what's next?" for a non-trivial task

## Mandatory Load Order
1. Read `.claude/skills/najaah/SKILL.md`
2. Inspect repository state and nearby implementations
3. Load only the specialist skill needed for the next phase

Do not load all specialist skills unless the task genuinely spans all of them.

## Operating Model
Follow this sequence every time:
1. Discovery
2. Build working memory
3. Plan
4. Approval
5. Execute by phase or parallel lane
6. Verify
7. Report

Do not implement before approval unless the user explicitly asked for immediate execution without a plan.

## Discovery Checklist
- Check changed files and current repo state
- Locate the nearest similar implementation
- Identify affected schema, services, controllers, requests, resources, routes, docs, and tests
- Confirm whether the change is system-scoped, center-scoped, or both
- Confirm whether admin resources or localized fields are involved
- Note open risks, assumptions, and missing requirements

## Working Memory Protocol
Create and keep a compact task memory with these sections:

```text
OBJECTIVE
- requested outcome

SCOPE
- system | center | mixed

INVARIANTS
- tenancy
- authorization split
- localization/admin output
- contract compatibility

AFFECTED AREAS
- schema
- services
- API
- jobs/events
- docs
- tests

OWNERSHIP
- lane or specialist -> owned files/modules

DEPENDENCIES
- what must finish before another lane can proceed

DECISIONS
- concrete choices that are now settled

VERIFICATION LEDGER
- tests/checks run
- checks still pending

OPEN RISKS
- unresolved questions
```

Do not let specialists drift from this memory. Update it whenever schema, workflow, API, or test scope changes.

## Plan Template
Use a short phase-based plan:

```text
OBJECTIVE
- one sentence on the requested outcome

WORKING MEMORY
- scope
- invariants
- ownership
- key dependencies

PHASES
- Architecture: schema, indexes, model updates
- Features: services, authorization, workflows
- API: routes, requests, controllers, resources, docs
- Quality: unit/feature/integration tests, factories, checks
- Documentation: feature docs, schema docs, quick references

RISKS
- multi-tenancy
- authorization
- contract compatibility
- localization/admin output

VERIFICATION
- tests or checks you will run
```

## Parallel Execution Rules
Parallel work is allowed only when it reduces latency without creating contract drift.

Use parallel lanes for:
- discovery across nearby schema, service, API, and tests
- architecture research plus feature design when the schema impact is already understood
- API implementation plus quality work after request and response shapes are stable
- documentation updates after behavior and contracts are frozen

Do not parallelize when:
- two lanes need to edit the same file set
- migrations, models, or shared resources are still changing
- error codes or authorization rules are unsettled
- tenant scope is unclear

Before opening a lane, record:
- lane owner
- exact write scope
- dependency edges
- expected handoff target

## Delegation Matrix
- Use `najaah-architecture` for migrations, indexes, relationships, query-shape changes, and caching decisions.
- Use `najaah-features` for service logic, authorization services, workflows, domain rules, and state changes.
- Use `najaah-api` for routes, FormRequests, controllers, resources, Scribe, and frontend contract answers.
- Use `najaah-quality` for tests, factories, Pint, PHPStan, coverage, and review passes.
- Use `najaah-pr-workflow` only after implementation when the task is about review or PR prep.

## Communication Contract
Default communication path is through the orchestrator and shared memory.

Use a handoff memo whenever one specialist hands work to another:

```text
FROM
- specialist or lane

TO
- specialist or lane

OWNED AREA
- files/modules that changed or are reserved

CHANGED CONTRACTS
- schema, service, API, docs, or test assumptions that changed

DECISIONS
- choices that are now fixed

BLOCKERS
- what is missing

VERIFICATION
- tests/checks run
- tests/checks still needed

NEXT ACTION
- exact next step for the receiver
```

Direct specialist-to-specialist sync is acceptable only for a shared contract boundary, and the result must be written back to memory immediately.

## Non-Negotiable Rules
- Preserve multi-tenancy boundaries.
- Keep controllers thin and business logic in services.
- Prefer human-readable admin resource fields over raw IDs or enum internals.
- Use locale-aware translated fields for user-facing output.
- Preserve existing contracts unless the task explicitly allows contract changes.
- Add or update tests for changed behavior.

## Frontend Questions
For frontend coordination:
1. Load `.claude/skills/najaah-api/SKILL.md`
2. Use `.claude/skills/najaah-api/references/admin-frontend-handoff.md`
3. Answer with exact endpoints, required params, response fields, scope rules, and gaps

## Verification Gates
Do not report complete until you have checked:
- implementation matches the approved plan
- working memory reflects final decisions and no stale blockers remain
- affected scopes and authorization paths are covered
- changed behavior has tests or a documented testing gap
- documentation is updated when a reusable pattern changed

## Reporting Format
Use a compact delivery report:

```text
Completed
- phases finished
- main files or modules changed

Verified
- tests/checks run
- checks not run

Risks / Follow-ups
- residual risk
- contract or rollout note
```

## References
- Patterns and example flows: `references/execution-patterns.md`
- Shared memory template: `references/shared-memory.md`
- Parallel lane playbook: `references/parallel-playbook.md`
- Handoff memo contract: `references/handoff-contract.md`
