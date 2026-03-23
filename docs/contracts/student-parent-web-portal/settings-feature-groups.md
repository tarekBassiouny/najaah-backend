# Student & Parent Web Portal — Settings Feature Groups API Contract

## Status: Draft (from implementation)
## Generated: 2026-03-22
## Feature: Student & Parent Web Portal
## Source Phase: 0A — Catalog-Driven Services
## Backend PR: not yet
## Audience: Admin frontend (Phase 0B)

---

## Purpose

Phase 0A added a `feature_groups` structure to the center settings API response. This lets the admin frontend render **Feature-as-Section-Header cards** without hardcoding which settings belong to which feature. The frontend reads `sections.feature_groups` and dynamically builds grouped cards with toggle/disable behavior driven entirely by this payload.

---

## Scope

This contract covers:
- The center settings page GET/PATCH endpoints (existing routes, new response shape)
- The `sections.feature_groups` response structure built by `PolicySettingsService::featureGroups()`
- The catalog metadata (`feature_group`, `feature_flag`, `system_limit`, `system_override`, `depends_on`) that feeds the grouping
- Visibility rules for system admin vs center admin

This contract does not cover:
- Frontend component layout or design
- New endpoints (no new routes were added in Phase 0A)
- Future web portal / parent portal settings (those are Phase 1+)

---

## Endpoints

### CENTER SETTINGS PAGE

#### GET /api/v1/admin/centers/{center}/settings

- **Auth:** Sanctum (admin), `require.permission:settings.manage`, `scope.center`
- **Request:**
  - Headers: `Authorization: Bearer {token}`, `Accept: application/json`
  - Body: none
- **Response 200:**
  ```json
  {
    "success": true,
    "message": "Center settings retrieved successfully",
    "data": {
      "settings": { "..." : "raw center settings" },
      "resolved_settings": { "..." : "merged system + center defaults + overrides" },
      "system_constraints": { "..." : "system limits and force-disable flags" },
      "page": {
        "type": "system_admin_center_settings | center_admin_settings",
        "actor_scope": "system | center",
        "editable": {
          "settings": ["default_view_limit", "allow_extra_view_requests", "..."],
          "features": ["ai_content", "codes_access", "..."],
          "ai": { "providers": { "...": ["is_enabled", "..."] } }
        }
      },
      "sections": {
        "settings": {
          "groups": { "playback": {}, "downloads": {}, "..." : {} },
          "resolved_groups": { "playback": {}, "downloads": {}, "..." : {} }
        },
        "feature_groups": {
          "ai_content": { "..." : "see Feature Group Shape below" },
          "codes_access": { "..." : "..." },
          "whatsapp_bulk": { "..." : "..." },
          "guest_browsing": { "..." : "..." },
          "pdf_downloads": { "..." : "..." }
        },
        "ai": {
          "feature_enabled": true,
          "providers": []
        }
      },
      "system_defaults": { "..." : "only for system admin" },
      "features": { "..." : "only for system admin" },
      "catalog": { "..." : "only for system admin" },
      "summaries": [ "..." , "only for center admin" ]
    }
  }
  ```
- **Error Responses:**
  - `401 UNAUTHORIZED`: Missing or invalid auth token
  - `403 FORBIDDEN`: Insufficient permissions
  - `404 NOT_FOUND`: Center not found
- **Notes:**
  - System admins (`actor_scope: "system"`) receive `system_defaults`, `features`, `catalog`, and `sections.features`.
  - Center admins (`actor_scope: "center"`) receive `summaries` instead.
  - Both receive `sections.feature_groups`.

#### PATCH /api/v1/admin/centers/{center}/settings

- **Auth:** Sanctum (admin), `require.permission:settings.manage`, `scope.center`
- **Request:**
  - Headers: `Authorization: Bearer {token}`, `Accept: application/json`, `Content-Type: application/json`
  - Body (all fields optional):
    ```json
    {
      "settings": {
        "default_view_limit": 3,
        "allow_guest_browsing": true
      },
      "features": {
        "ai_content": false
      },
      "ai": {
        "providers": {
          "openai": { "default_model": "gpt-4o" }
        }
      }
    }
    ```
  - Validation: Rules are catalog-driven via `PolicySettingsService::centerSettingRules()` and `featureFlagRules()`.
  - Center admins can update `settings.*` only. Feature flags require system admin scope.
- **Response 200:** Same shape as GET (full page payload with updated values).
- **Error Responses:**
  - `401 UNAUTHORIZED`: Missing or invalid auth token
  - `403 FORBIDDEN`: Insufficient permissions or center scope mismatch
  - `422 UNPROCESSABLE_ENTITY`: Validation errors
- **Notes:** The response always returns the full settings page payload, not just the changed fields.

---

## Feature Group Shape

Each entry in `sections.feature_groups` has this structure:

```json
{
  "feature_flag": "string | null",
  "flag_enabled": "boolean",
  "center_settings": ["string"],
  "system_limits": ["string"],
  "system_overrides": ["string"],
  "depends_on": "string | null"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `feature_flag` | `string\|null` | The feature flag key that gates this group (e.g. `"ai_content"`). Null if the group has no feature flag. |
| `flag_enabled` | `boolean` | Whether the feature flag is currently enabled for this center. |
| `center_settings` | `string[]` | Keys of center-scoped settings that belong to this feature group. These are the settings the center admin can edit (when the flag is on). |
| `system_limits` | `string[]` | Keys of system settings (group=`limits`) that cap values in this feature group. System admin only. |
| `system_overrides` | `string[]` | Keys of system settings (group=`overrides`) that can force-disable settings in this feature group. System admin only. |
| `depends_on` | `string\|null` | If set, this group's settings are disabled when the depended-on setting is in its disabled state. Used for cascading dependencies (e.g. future `allow_web_playback` depends on `allow_web_access`). |

---

## Current Feature Groups (from implementation)

These are the feature groups currently returned by the API, built from `config/settings_catalog.php`:

### `ai_content`

```json
{
  "feature_flag": "ai_content",
  "flag_enabled": true,
  "center_settings": [],
  "system_limits": [],
  "system_overrides": [],
  "depends_on": null
}
```

- **Purpose:** Gates AI content generation for a center.
- **Note:** No center settings are directly grouped under `ai_content` yet. The AI provider configuration lives in `sections.ai` separately.

### `codes_access`

```json
{
  "feature_flag": "codes_access",
  "flag_enabled": true,
  "center_settings": [
    "requires_video_approval",
    "video_code_expiry_days"
  ],
  "system_limits": [],
  "system_overrides": [],
  "depends_on": null
}
```

- **Purpose:** Gates video access code functionality.
- **Center settings:**
  - `requires_video_approval` (boolean, default `false`) -- whether students need approval codes for video access.
  - `video_code_expiry_days` (integer|null, default `null`) -- number of days before access codes expire. Null means no expiry.

### `whatsapp_bulk`

```json
{
  "feature_flag": "whatsapp_bulk",
  "flag_enabled": true,
  "center_settings": [],
  "system_limits": [],
  "system_overrides": [],
  "depends_on": null
}
```

- **Purpose:** Gates WhatsApp bulk messaging for a center.
- **Note:** The `whatsapp_bulk_settings` system setting has `feature_group: "whatsapp_bulk"` but is an infrastructure-group system setting, not a limits/overrides group, so it does not appear in `system_limits` or `system_overrides`.

### `guest_browsing`

```json
{
  "feature_flag": "guest_browsing",
  "flag_enabled": true,
  "center_settings": [
    "allow_guest_browsing"
  ],
  "system_limits": [],
  "system_overrides": [
    "force_disable_guest_browsing"
  ],
  "depends_on": null
}
```

- **Purpose:** Gates whether unauthenticated users can browse center course catalogs.
- **Center settings:**
  - `allow_guest_browsing` (boolean, default `false`) -- center-level toggle.
- **System overrides:**
  - `force_disable_guest_browsing` (boolean, default `false`) -- when true, guest browsing is disabled platform-wide regardless of center setting.

### `pdf_downloads`

```json
{
  "feature_flag": "pdf_downloads",
  "flag_enabled": true,
  "center_settings": [
    "pdf_download_permission"
  ],
  "system_limits": [],
  "system_overrides": [
    "force_disable_pdf_download"
  ],
  "depends_on": null
}
```

- **Purpose:** Gates PDF download capability for a center.
- **Center settings:**
  - `pdf_download_permission` (boolean, default `false`) -- center-level toggle.
- **System overrides:**
  - `force_disable_pdf_download` (boolean, default `false`) -- when true, PDF downloads are disabled platform-wide.

---

## Governance Rules

The 3-layer governance model applies to all feature groups:

```
Feature Flag (system admin)  -->  gates availability per center
    |
Center Setting (center admin)  -->  enables/configures within their center
    |
System Limit / Override (system admin)  -->  caps values or force-disables globally
```

### Resolution Order

1. **System defaults** are loaded from `system_settings` table.
2. **Center defaults** are loaded from the `centers` table columns.
3. **Center overrides** from `center_settings.settings` JSON are merged on top.
4. **Governance constraints** are applied:
   - `system_limit`: `resolved_value = min(center_value, system_limit_value)`
   - `system_override`: if override is `true`, setting is forced to its disabled value
   - `feature_flag`: if flag is `false`, all governed settings are forced to their disabled values
   - `depends_on`: if the depended-on setting is in its disabled state, this setting is also disabled

### Visibility by Actor Scope

| Data | System Admin | Center Admin |
|------|-------------|-------------|
| `sections.feature_groups` | Yes | Yes |
| `page.editable.features` | All flag keys | Empty array |
| `features` (raw values) | Yes | No |
| `catalog` (full definitions) | Yes | No |
| `system_defaults` | Yes | No |
| `system_constraints` | Yes | Yes |
| `summaries` | No | Yes |

### Frontend Rendering Rules

For each feature group card:

1. Read `flag_enabled` to determine if the feature is active.
2. If `flag_enabled` is `false` and actor is center admin: show the card as disabled with an explanation (use `summaries` array for messaging).
3. If `flag_enabled` is `true`: render the `center_settings` as editable fields.
4. For system admin: also show `system_limits` and `system_overrides` as editable fields, plus the feature flag toggle.
5. Check `depends_on`: if the depended-on setting's resolved value equals its disabled state, show dependent settings as disabled with a dependency explanation.

---

## Settings / Feature Flags

All five feature flags are defined in `config/settings_catalog.php` under `features.properties`:

| Flag Key | Default | Managed By |
|----------|---------|------------|
| `ai_content` | `true` | System admin per center |
| `codes_access` | `true` | System admin per center |
| `whatsapp_bulk` | `true` | System admin per center |
| `guest_browsing` | `true` | System admin per center |
| `pdf_downloads` | `true` | System admin per center |

Feature flags are editable only by system admins via `page.editable.features`. Center admins see the effect (enabled/disabled cards) but cannot change flags.

---

## Error Codes

No new error codes were introduced in Phase 0A. The endpoints use existing codes:
- `UNAUTHORIZED` (401)
- `FORBIDDEN` (403)
- `NOT_FOUND` (404)
- `UNPROCESSABLE_ENTITY` (422)

---

## Breaking Changes

None. Phase 0A is additive:
- `sections.feature_groups` is a new key added to the existing response.
- All existing response fields remain unchanged.
- No existing endpoints were removed or renamed.

---

## What Frontend Should Build (Phase 0B)

From this contract, the admin frontend can build:

1. **Feature Group Cards**: For each key in `sections.feature_groups`, render a card/section with the feature name as header, a toggle reflecting `flag_enabled`, and the `center_settings` as form fields inside the card.
2. **System Admin Extras**: When `page.actor_scope === "system"`, show system limits and override toggles within each feature group card.
3. **Disabled State**: When `flag_enabled` is `false`, gray out the card body and show a platform-managed message.
4. **Dependency Awareness**: When `depends_on` is set, check the resolved value of the parent setting to determine if dependent settings should be interactable.

---

## Implementation References

| File | Purpose |
|------|---------|
| `config/settings_catalog.php` | Setting definitions with `feature_group` metadata |
| `app/Services/Settings/PolicySettingsService.php` | `featureGroups()` builds the structure from catalog |
| `app/Services/Settings/CenterSettingsPageService.php` | `buildPayload()` assembles the full response |
| `app/Http/Controllers/Admin/Centers/CenterOperationsController.php` | `show()` and `update()` endpoints |
| `routes/api/v1/admin/centers.php` | Route definitions |
