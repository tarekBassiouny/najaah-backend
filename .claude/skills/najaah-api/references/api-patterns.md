# API Patterns

## Route Conventions
- Use `/api/v1/...`
- Prefer RESTful resource naming and nested ownership where it is real
- Keep action endpoints explicit for non-CRUD behavior such as approvals, playback, reorder, or bulk actions

## Controller Rules
- Constructor-inject services
- Accept FormRequests and route-model-bound models
- Keep business decisions in services
- Convert domain failures into stable API responses

## Validation Rules
- Validate request shape in FormRequests
- Keep authorization in the service layer unless route-level authorization is clearly simpler and already established
- Provide request examples or body parameter docs when Scribe needs them

## Resource Rules
- Use resources for complex payloads and admin responses
- Eager load related display fields when the resource exposes them
- Keep admin payloads readable and additive where compatibility matters

## Error Handling
- Map domain failures to stable HTTP statuses
- Preserve existing error code shape where the surface already relies on it

## Documentation
- Regenerate Scribe when public or admin API contracts changed
- Document required path, query, and body fields
- Call out scope and permission assumptions explicitly
