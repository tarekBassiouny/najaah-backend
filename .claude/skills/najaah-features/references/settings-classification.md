# Settings Classification

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

## Source Of Truth
- Static registry: `config/settings_catalog.php`
- Runtime resolver: `app/Services/Settings/PolicySettingsService.php`
- Center admin settings: `app/Http/Requests/Admin/Centers/UpdateCenterSettingsRequest.php`
- System admin settings: `app/Http/Requests/Admin/Settings/StoreSystemSettingRequest.php`, `UpdateSystemSettingRequest.php`
- Center feature flags: `app/Http/Requests/Admin/Centers/UpdateCenterFeaturesRequest.php`

## Implementation Rules
- Declare settings in the registry before they are accepted by admin requests.
- Runtime services must respect system overrides and relevant feature flags.
- Derive allowed keys from the registry; avoid duplicating key lists across requests and services.
- Center settings pages must separate `editable value` from `resolved effective value` when platform policy can override.
- Categories should optimize for operator UX and growth: access, playback, downloads, branding, profile, integrations.

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

## Deep Reference
- Settings governance audit and categorization rules: `.claude/skills/najaah-features/references/settings-governance.md`
