# Video Access Codes — Video Code Batch Settings Contract
## Status: Draft (from implementation)
## Generated: 2026-03-26
## Feature: video-access-codes
## Backend PR: not yet

---

## Endpoints

### VIDEO CODE BATCHES

#### GET `/api/v1/admin/centers/{center}/video-code-batches`
- **Auth:** admin sanctum/session auth
- **Purpose:** returns the paginated list of video code batches for the selected center and the resolved settings the create form should use
- **Response additions:**

```json
{
  "success": true,
  "message": "Video code batches retrieved successfully",
  "data": [],
  "meta": {
    "page": 1,
    "current_page": 1,
    "per_page": 20,
    "total": 0,
    "last_page": 1
  },
  "settings": {
    "max_quantity": 100,
    "default_view_limit": 6
  }
}
```

- **Settings fields:**
  - `settings.max_quantity`: resolved center limit for batch quantity after system ceilings are applied
  - `settings.default_view_limit`: resolved center default used when the create request omits `view_limit_per_code`
- **Notes:**
  - these values are center-specific and should be treated as runtime policy, not frontend constants
  - the frontend should refetch them when the selected center changes

#### POST `/api/v1/admin/centers/{center}/courses/{course}/videos/{video}/code-batches`
- **Auth:** admin sanctum/session auth
- **Request body:**
  - `quantity`: integer, required, min `1`, max = `settings.max_quantity`
  - `view_limit_per_code`: integer, optional, min `1`, max = resolved system cap for video code batch view limit
- **Behavior:**
  - if `view_limit_per_code` is omitted, backend uses `settings.default_view_limit`
  - backend validates against resolved policy, so frontend should not hard-code `10000` or `2`

#### POST `/api/v1/admin/centers/{center}/code-batches/{batch}/expand`
- **Auth:** admin sanctum/session auth
- **Request body:**
  - `additional_quantity`: integer, required, min `1`, max = remaining quantity allowed before reaching `settings.max_quantity`
- **Behavior:**
  - backend rejects expansions that would push the batch total above the resolved center max

---

## Frontend Update

- Load `settings.max_quantity` and `settings.default_view_limit` from the list endpoint before rendering the create form.
- Use `settings.default_view_limit` as the initial form value for `view_limit_per_code`.
- Use `settings.max_quantity` as the quantity input max and client-side validation ceiling.
- Do not hard-code `10000` for quantity or `2` for the default view limit.
- Treat catalog metadata and resolved policy separately:
  - catalog: definitions, grouping, ownership, generic rules
  - resolved policy: the actual center-specific values the screen should enforce now

---

## Breaking Changes

- The list endpoint now includes a top-level `settings` object.
- The create form should stop assuming a fixed default view limit of `2`.
- The create and expand forms should stop assuming a fixed quantity max of `10000`.
