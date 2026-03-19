# Parallel Lane Playbook

Use parallel lanes to reduce latency, not to create duplicate reasoning or conflicting edits.

## Safe Lanes

### Discovery
- architecture can inspect schema, migrations, and models
- features can inspect services and authorization rules
- API can inspect routes, requests, and resources
- quality can inspect nearby tests and factories

### Implementation
- architecture and features can overlap when the schema path is additive or already fixed
- API and quality can overlap after request and response shapes are stable
- documentation can follow once behavior and contract decisions are frozen

## Serialization Triggers

Do not run lanes in parallel when they touch:
- the same files
- migrations or shared models still being designed
- shared error codes or resource payloads
- authorization and tenant-boundary rules still being decided

## Lane Setup Checklist

Before starting a lane, record:
- owner
- exact write scope
- dependency edge
- expected handoff target
- success condition

## Example Lane Split

```text
Lane A: Architecture
- migration
- model casts/relations
- factory default updates

Lane B: Features
- service logic
- authorization
- domain exceptions

Lane C: API
- FormRequest
- controller
- resource
- route wiring

Lane D: Quality
- regression tests
- contract tests
- focused validation commands
```

Use this split only if each lane has a disjoint write scope and the contract boundary between lanes is already defined in shared memory.
