# Standards

## PHP and Service Rules
- Use strict types.
- Prefer readonly services with constructor injection.
- Keep business logic in services, not controllers or requests.
- Use typed arrays with PHPDoc shapes when returning structured arrays.

## Model and Schema Rules
- Use integer status constants instead of enum database columns.
- Use soft deletes on entity tables unless there is a clear exception.
- Add casts for JSON, boolean, datetime, and numeric fields that are read in code.

## API and Resource Rules
- Keep controllers thin.
- Use FormRequests for input validation.
- Return resources for complex responses.
- For admin resources, prefer readable labels and related display fields over raw foreign keys or enum internals.

## Localization Rules
- Use locale-aware translated accessors for user-facing text.
- Do not hardcode locale arrays or resource-level fallback chains.
- Preserve surface-specific behavior:
  - mobile: localized display fields only
  - admin lists: localized display fields only unless the UI needs raw maps
  - admin detail/edit: localized fields plus raw translation maps when needed

## Compatibility Rules
- Preserve existing contracts by default.
- If a contract must change, call it out in the plan and in the completion report.
- Prefer additive changes over destructive response changes.
