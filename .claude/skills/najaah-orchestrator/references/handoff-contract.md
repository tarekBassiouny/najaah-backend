# Handoff Memo Contract

Use a handoff memo whenever work moves between specialists or parallel lanes.

## Template

```text
FROM
- specialist or lane

TO
- specialist or lane

OWNED AREA
- files or modules changed
- files or modules reserved

CHANGED CONTRACTS
- schema changes
- service rule changes
- API field changes
- docs or test assumptions that changed

DECISIONS
- choices that are now fixed

BLOCKERS
- missing input
- unresolved risk

VERIFICATION
- tests/checks run
- tests/checks still needed

NEXT ACTION
- exact next step for the receiver
```

## Rules

- Keep it factual and short.
- Do not restate unchanged project context.
- If tenant scope, auth boundary, or response fields changed, say so explicitly.
- Write the same decision into shared memory so later lanes do not rely on stale assumptions.
