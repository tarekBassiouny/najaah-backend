# Testing Patterns

## Test Mix
- service-level tests for business rules
- feature tests for HTTP contracts and middleware behavior
- integration-style coverage when a workflow crosses models, services, and jobs

## What To Cover
- happy path
- authorization and scope failures
- validation failures when the API surface changed
- settings and edge-case behavior when limits or lifecycle state matter
- regression for the bug or contract change that triggered the work

## Factory Rules
- update factories when new required fields or defaults are introduced
- keep factories aligned with published and ready states used often in tests

## Commands
```bash
./vendor/bin/sail test
./vendor/bin/sail test --filter="Name"
./vendor/bin/sail test --coverage --min=90
./vendor/bin/sail pint --test
./vendor/bin/sail composer phpstan
./vendor/bin/sail composer quality
```

## Review Checklist
- tests would fail before the fix and pass after it
- scope and authorization paths are covered
- factories still produce valid data
- no obvious missing branch on critical paths
