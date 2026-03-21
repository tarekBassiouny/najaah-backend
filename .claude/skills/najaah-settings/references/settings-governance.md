# Settings Governance

## Classification Model

Use these buckets for every configurable or semi-configurable behavior:

| Bucket | Owner | Scope | Examples |
|---|---|---|---|
| System setting | system admin | global | `timezone`, `support_email`, `max_view_limit` |
| Center setting | center admin | per center | `default_view_limit`, `branding`, `education_profile` |
| Center feature flag | system admin | per center | `features.ai_content`, `features.codes_access` |
| Operational default | engineering | internal | queue timeout fallback, job retry fallback |
| Hard-coded invariant | engineering | internal | tenancy boundaries, auth split, forbidden cross-center access |

## Current Source Of Truth

- Static settings registry: `config/settings_catalog.php`
- Runtime settings behavior: `app/Services/Settings/PolicySettingsService.php`
- Center setting update surface: `UpdateCenterSettingsRequest`
- Center feature flag update surface: `UpdateCenterFeaturesRequest`
- System setting update surface: `StoreSystemSettingRequest`, `UpdateSystemSettingRequest`

## Intentional Hard-Coded Values

These may stay internal unless product requirements say otherwise:

| Area | Reason |
|---|---|
| Queue and processing safety fallbacks | needed when stored settings are missing or malformed |
| Validation-only lower bounds like `min:1` | input safety, not product configuration |
| Multi-tenancy and authorization invariants | must not become admin-configurable |

## New Feature Checklist

When adding a feature:
1. Decide if the feature needs availability gating per center.
2. Decide if any behavior should be configurable by system admin.
3. Decide if any behavior should be configurable by center admin.
4. Add the setting or flag to the registry before wiring controllers or UI.
5. Update tests for both the configurable path and the fallback path.
