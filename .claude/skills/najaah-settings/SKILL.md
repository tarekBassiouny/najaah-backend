---
name: najaah-settings
description: Classify and implement Najaah LMS system settings, center settings, center feature flags, and configuration governance. Use when adding features, touching admin configuration, or deciding what should remain hard-coded.
---

# Najaah Settings Skill

## Use This Skill When
- adding or changing admin-managed settings
- deciding whether a new feature needs a system setting, center setting, or feature flag
- auditing hard-coded values versus configurable behavior
- updating center settings, system settings, or settings-aware services

## Source Of Truth
- Static registry: `config/settings_catalog.php`
- Runtime resolver and governance logic: `app/Services/Settings/PolicySettingsService.php`
- Center admin settings request: `app/Http/Requests/Admin/Centers/UpdateCenterSettingsRequest.php`
- System admin settings requests: `app/Http/Requests/Admin/Settings/StoreSystemSettingRequest.php`, `app/Http/Requests/Admin/Settings/UpdateSystemSettingRequest.php`
- Center feature flags request: `app/Http/Requests/Admin/Centers/UpdateCenterFeaturesRequest.php`

## Mandatory Classification
For every new or touched knob, choose exactly one:
1. `system setting`: platform-wide and owned by system admin
2. `center setting`: center-specific and owned by center admin
3. `center feature flag`: per-center availability, owned by system admin
4. `operational default`: internal constant or safety fallback, not admin-managed
5. `hard-coded invariant`: must not be configurable

Do not leave a new behavior unclassified.

For center-facing configuration, also decide these four attributes explicitly:
- `owner`: who manages the source value
- `viewer`: who can see the raw value, resolved value, or both
- `governance`: which system limits, overrides, or feature flags can constrain it
- `category`: which UI section it belongs to so the page still scales as more settings are added

## Workflow
1. Inspect the static settings registry in `config/settings_catalog.php`.
2. Decide whether the behavior is global, center-specific, availability-gated, or internal-only.
3. For center settings, map system-admin ownership, center-admin ownership, resolved-only visibility, and any platform-managed constraints before touching API payloads or UI contracts.
4. If it is admin-managed, add it to `config/settings_catalog.php` first.
5. Keep the catalog metadata expressive enough to drive grouping and permissions without duplicating ad hoc key lists across requests, services, and page builders.
6. Wire the matching request validation, defaults, runtime resolution, and tests in `PolicySettingsService` and the affected feature/API layers.
7. If it stays hard-coded, document why in the task notes or feature doc.

## Implementation Rules
- System settings must be declared in the registry before they are accepted by admin requests.
- Center settings must be declared in the registry before center admin can update them.
- Per-center feature flags belong under `features` and are managed by system admin only.
- Runtime services must respect system overrides and relevant feature flags.
- Avoid duplicating allowed keys across requests and services; derive them from the registry.
- Center settings pages must separate `editable value` from `resolved effective value` when platform policy can override the center-owned value.
- Shared settings pages must not blur ownership: system-managed flags and limits can appear alongside center-managed settings, but the API contract must make the edit boundary obvious.
- Categories should optimize for operator UX and growth. Prefer stable domain groups such as access, playback, downloads, branding, profile, or integrations over one-off page-specific buckets.

## Done Checklist
- Registry updated
- Scope ownership is clear
- Viewer and editor roles are clear
- Constraints and resolved-value behavior are clear
- Admin request validation updated
- Runtime resolution updated
- Category or grouping supports the intended UI
- Tests updated
- Frontend-facing contract clarified when needed

## Read Next Only When Needed
- Settings audit and governance reference: `references/settings-governance.md`
