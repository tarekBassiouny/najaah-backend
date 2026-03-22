# Student & Parent Web Portal — Settings Feature Groups Contract

## Status: Draft
## Source Phase: 0A — Catalog-Driven Services
## Audience: Admin frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the backend response shape that lets the admin frontend render feature-grouped settings cards without hardcoded field lists.

---

## Scope

This contract covers:
- center settings page payload additions
- catalog metadata needed for grouped rendering
- system admin vs center admin visibility boundaries

This contract does not cover:
- frontend component layout implementation
- non-settings admin pages

---

## Expected API Surface

| Method | Path | Purpose | Status |
|--------|------|---------|--------|
| `GET` | `/api/v1/admin/centers/{center}/settings` | Return catalog, settings, resolved settings, and feature groups | planned |
| `PATCH` or existing update route | `/api/v1/admin/centers/{center}/settings` | Accept grouped settings payload using existing backend contract shape | planned |

---

## Response Additions

### `catalog`

Each relevant setting definition is expected to expose:

```json
{
  "feature_group": "web_portal",
  "group": "web_portal",
  "type": "boolean",
  "managed_by": "center"
}
```

### `sections.feature_groups`

Expected draft shape:

```json
{
  "web_portal": {
    "feature_flag": "web_access",
    "flag_enabled": false,
    "center_settings": [
      "allow_web_access",
      "allow_web_playback",
      "web_device_limit"
    ],
    "system_limits": [
      "max_web_device_limit"
    ],
    "system_overrides": [
      "force_disable_web_access",
      "force_disable_web_playback"
    ],
    "depends_on": null
  },
  "parent_portal": {
    "feature_flag": "parent_portal",
    "flag_enabled": false,
    "center_settings": [
      "allow_parent_portal"
    ],
    "system_limits": [],
    "system_overrides": [
      "force_disable_parent_portal"
    ],
    "depends_on": null
  }
}
```

---

## Ownership Rules

- System admin can see and edit feature flags, system limits, and system overrides.
- Center admin can only see and edit center-owned settings.
- When a feature flag is off, center-admin-facing UI should treat governed settings as unavailable.

---

## Open Items

- Confirm final HTTP method/path for center settings update if frontend needs one canonical route reference.
- Confirm whether `sections.feature_groups` should include translated labels or keys only.
- Confirm whether system settings manage view also receives the same grouping structure or a parallel shape.

