# Shared Memory Template

Use one compact shared memory record per orchestrated task. Keep it short enough to scan, but concrete enough that specialists stop re-deriving the same decisions.

## Template

```text
OBJECTIVE
- requested outcome

SCOPE
- system | center | mixed

INVARIANTS
- tenancy rules that must hold
- auth boundary: admin sanctum vs student JWT
- localization/admin readability rules
- contract compatibility requirement

AFFECTED AREAS
- schema:
- services:
- API:
- jobs/events:
- docs:
- tests:

OWNERSHIP
- Architecture:
- Features:
- API:
- Quality:
- Documentation:

DEPENDENCIES
- Architecture -> Features:
- Features -> API:
- API -> Quality:

DECISIONS
- settled choice + reason

VERIFICATION LEDGER
- completed:
- pending:

OPEN RISKS
- unresolved question
- rollout or migration note
```

## Najaah-Specific Prompts

When filling the template, explicitly answer:
- Is the work center-scoped, system-scoped, or mixed?
- Does it change branded vs unbranded behavior?
- Does it affect JWT student flows, Sanctum admin flows, or both?
- Does it introduce or change localized admin/mobile output?
- Does it change Scribe or frontend-facing contracts?

## Update Triggers

Update the memory immediately when:
- a migration or shared model changes
- service behavior changes authorization or lifecycle rules
- a request or resource contract changes
- tests reveal a broader blast radius than planned
