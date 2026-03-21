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

## Workflow
1. Inspect the static settings registry in `config/settings_catalog.php`.
2. Decide whether the behavior is global, center-specific, availability-gated, or internal-only.
3. If it is admin-managed, add it to `config/settings_catalog.php` first.
4. Wire the matching request validation, defaults, runtime resolution, and tests in `PolicySettingsService` and the affected feature/API layers.
5. If it stays hard-coded, document why in the task notes or feature doc.

## Implementation Rules
- System settings must be declared in the registry before they are accepted by admin requests.
- Center settings must be declared in the registry before center admin can update them.
- Per-center feature flags belong under `features` and are managed by system admin only.
- Runtime services must respect system overrides and relevant feature flags.
- Avoid duplicating allowed keys across requests and services; derive them from the registry.

## Done Checklist
- Registry updated
- Scope ownership is clear
- Admin request validation updated
- Runtime resolution updated
- Tests updated
- Frontend-facing contract clarified when needed

## Read Next Only When Needed
- Settings audit and governance reference: `references/settings-governance.md`
