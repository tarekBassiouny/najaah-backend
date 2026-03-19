# Service Patterns

## Service Shape
- Constructor injection only
- No request, controller, or response dependencies
- Small public methods around explicit business actions
- Typed returns and stable domain exception payloads

## Authorization Pattern
- Keep workflow-specific authorization in a dedicated service or helper inside the same domain
- Validate center ownership, entity linkage, lifecycle state, and actor role before mutating data
- Reject invalid access early

## Domain Failure Pattern
- Throw domain exceptions with stable codes and readable messages
- Let the API layer translate those failures to HTTP responses

## Common Checks
- center ownership and scope
- entity belongs to the requested parent
- actor has the expected role or enrollment
- status allows the requested action
- effective settings permit the action

## Refactor Trigger
Split or refactor when:
- one service owns unrelated workflows
- one method carries many branches and side effects
- repeated checks belong in an authorization service
- multiple consumers need the same domain operation
