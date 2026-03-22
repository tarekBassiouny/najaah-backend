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
3. If settings, feature flags, or admin-managed defaults are involved, load `.claude/skills/najaah-features/references/settings-classification.md`
4. Load only the specialist skill needed for the next phase

Do not load all specialist skills unless the task genuinely spans all of them.

## Operating Model
Follow this sequence every time:
1. Discovery
2. Build working memory
3. Plan
4. Approval
5. Phase review gate
6. Execute by phase or parallel lane
7. Verify
8. Report

Do not implement before approval unless the user explicitly asked for immediate execution without a plan.
Do not start a new phase until its phase review gate is completed.

## Discovery Checklist
- Check changed files and current repo state
- Locate the nearest similar implementation
- Identify affected schema, services, controllers, requests, resources, routes, docs, and tests
- Confirm whether the change is system-scoped, center-scoped, or both
- Classify each new or touched knob as system setting, center setting, center feature flag, operational default, or hard-coded invariant
- Confirm whether admin resources or localized fields are involved
- Note open risks, assumptions, and missing requirements

## Working Memory Protocol
Create and keep a compact task memory. For the template and section definitions, see `references/shared-memory.md`.

Do not let specialists drift from this memory. Update it whenever schema, workflow, API, or test scope changes.

## Plan Template
Use a short phase-based plan. For the template, see `references/execution-patterns.md`.

## Phase Review Gate
Before executing any approved phase, complete a short review gate and write it into the task tracker or working notes.

Required checks:
- re-read the phase plan
- inspect the nearby implementation and affected files
- list affected schema, services, API, docs, and tests for that phase
- record contract impact
- record any phase-local adjustments discovered during code review
- define the verification plan
- receive explicit approval to implement that phase

Status rule:
- `pending`: phase not reviewed yet
- `reviewed`: review complete, not yet coding
- `in_progress`: allowed only after review complete and approval explicit

Git workflow rule:
- once a phase is approved for implementation, prefer executing it from a dedicated git worktree on that phase branch
- keep the main checkout available for planning, tracker updates, and reviews

## Parallel Execution Rules
Parallel work is allowed only when it reduces latency without creating contract drift. For detailed rules on when to parallelize, lane setup, and ownership, see `references/parallel-playbook.md`.

## Delegation Matrix
- Use `najaah-architecture` for migrations, indexes, relationships, query-shape changes, and caching decisions.
- Use `najaah-features` for service logic, authorization services, workflows, domain rules, and state changes.
- Use `najaah-api` for routes, FormRequests, controllers, resources, Scribe, and frontend contract answers.
- Use `najaah-quality` for tests, factories, Pint, PHPStan, coverage, review passes, and PR workflow.

## Communication Contract
Default communication path is through the orchestrator and shared memory. For the handoff memo template, see `references/handoff-contract.md`.

Direct specialist-to-specialist sync is acceptable only for a shared contract boundary, and the result must be written back to memory immediately.

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

For phase-based feature work, also check:
- the phase review gate was completed before code changes started
- any phase-local plan adjustments were recorded back into the tracker or working memory

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
