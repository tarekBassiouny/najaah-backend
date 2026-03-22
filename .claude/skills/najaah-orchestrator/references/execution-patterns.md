# Orchestrator Execution Patterns

Use these patterns as templates. Prefer adapting an existing nearby implementation over following any template mechanically.

All patterns assume:
- a shared working-memory record exists
- each lane has explicit ownership
- handoff memos are used when one lane depends on another

## New Feature

```text
Discovery
- read master skill
- inspect similar feature
- list affected schema, services, routes, resources, tests
- initialize working memory with scope, invariants, owners, and risks

Plan
- Architecture: schema/model/index updates
- Features: service and authorization changes
- API: request/controller/resource/route updates
- Quality: unit and feature tests
- mark which phases can run in parallel and which must serialize

Approval
- show phases, risks, and verification

Phase Review Gate
- re-read the specific phase before coding
- inspect nearby code and affected files for that phase
- record contract impact and phase-local adjustments
- define exact verification for the phase
- mark the phase as reviewed before changing code

Execution
- complete one phase at a time
- or run disjoint lanes in parallel after ownership is explicit
- load only the skill needed for that phase
- write decisions and blockers back into memory
- keep docs and tests aligned

Verification
- focused tests first
- broader quality checks if the change is wide enough
```

## Bug Fix

```text
Discovery
- reproduce from code and tests
- identify root cause before editing
- write the failing behavior and suspected blast radius into memory

Plan
- fix path
- regression test
- scope/authorization checks

Execution
- implement the minimal fix
- add the regression test in the same pass

Verification
- run the regression test
- run adjacent tests if the fix touches shared logic
```

## Schema Change

```text
Discovery
- inspect current migration, model, factories, and queries
- determine backfill or compatibility needs
- reserve schema ownership before editing migrations or shared models

Plan
- migration
- model casts/relations
- service/API updates if the field is used externally
- factory/test updates

Verification
- migration syntax
- tests covering read/write paths
```

## Frontend Handoff

```text
Discovery
- read master skill then API skill
- confirm the exact scope: system or center
- inspect existing route/controller/resource definitions
- record the contract surface in memory so tests and docs use the same shape

Answer
- module and scope
- endpoint list
- required body/query/path params
- response fields needed by the UI
- permission or middleware constraints
- backend gaps or TODOs
```
