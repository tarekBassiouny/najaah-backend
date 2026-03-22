# Web Portal Settings Governance

## Status: Planning (2026-03-22)
## Related: [Student & Parent Web Portal](student-parent-web-portal.md)

---

## Overview

The student web portal and parent portal introduce new center-facing configuration that must follow the existing 3-layer governance model:

```
Feature Flag (system admin)  →  gates availability per center
    ↓
Center Setting (center admin)  →  enables/configures within their center
    ↓
System Limit / Override (system admin)  →  caps values or force-disables globally
```

This document classifies every new setting, defines ownership, governance constraints, catalog entries, seeder values, and resolution rules.

---

## Classification Summary

### Feature Flags (System Admin Managed)

Added to existing `features` object in `center_settings.settings` JSON.

| Flag | Default | Purpose |
|------|---------|---------|
| `web_access` | `false` | Gates whether this center can use the student web portal |
| `web_playback` | `false` | Gates whether this center can have video playback on web |
| `parent_portal` | `false` | Gates whether this center can use the parent portal |

### Center Settings (Center Admin Managed)

| Setting | Type | Default | Feature Gate | System Override | System Limit | Group |
|---------|------|---------|-------------|----------------|-------------|-------|
| `allow_web_access` | bool | false | `web_access` | `force_disable_web_access` | — | web_portal |
| `allow_web_playback` | bool | false | `web_playback` | `force_disable_web_playback` | — | web_portal |
| `web_device_limit` | int | 1 | `web_access` | — | `max_web_device_limit` | web_portal |
| `allow_parent_portal` | bool | false | `parent_portal` | `force_disable_parent_portal` | — | parent_portal |

### System Settings (System Admin Managed)

| Setting | Type | Default | Group | Purpose |
|---------|------|---------|-------|---------|
| `max_web_device_limit` | int | 3 | limits | Ceiling for center `web_device_limit` |
| `force_disable_web_access` | bool | false | overrides | Emergency kill switch for all web portals |
| `force_disable_web_playback` | bool | false | overrides | Emergency kill switch for web playback |
| `force_disable_parent_portal` | bool | false | overrides | Emergency kill switch for parent portals |

---

## Governance Flows

### Web Access

```
System admin enables feature flag `web_access` for Center X
    ↓
Center X admin sees "Web Access" in their settings page
    ↓
Center X admin sets allow_web_access = true
    ↓
Students in Center X can log in via web portal
    ↓
If system admin sets force_disable_web_access = true
    ↓
ALL centers web access disabled regardless of center setting
```

### Web Playback

```
System admin enables feature flag `web_playback` for Center X
    (requires web_access flag also enabled)
    ↓
Center X admin sets allow_web_playback = true
    ↓
Students can watch videos on web
    ↓
If system admin sets force_disable_web_playback = true
    ↓
ALL centers web playback disabled, students can still browse but not play
```

### Web Device Limit

```
Center admin sets web_device_limit = 5
System admin has max_web_device_limit = 3
    ↓
Resolved value = min(5, 3) = 3
    ↓
Student can bind at most 3 web browsers
```

### Parent Portal

```
System admin enables feature flag `parent_portal` for Center X
    ↓
Center X admin sets allow_parent_portal = true
    ↓
Parents can register/login for Center X
    ↓
If system admin sets force_disable_parent_portal = true
    ↓
ALL centers parent portal disabled
```

---

## Catalog Entries

To be added to `config/settings_catalog.php`:

### Feature Flags (under existing `features` definition)

```php
'web_access'      => ['default' => false],
'web_playback'    => ['default' => false],
'parent_portal'   => ['default' => false],
```

### Center Settings

```php
'allow_web_access' => [
    'scope' => 'center',
    'managed_by' => 'center',
    'group' => 'web_portal',
    'feature_group' => 'web_portal',
    'type' => 'boolean',
    'default' => false,
    'storage' => 'center_settings.settings.allow_web_access',
    'feature_flag' => 'web_access',
    'system_override' => 'force_disable_web_access',
    'rules' => ['boolean'],
],
'allow_web_playback' => [
    'scope' => 'center',
    'managed_by' => 'center',
    'group' => 'web_portal',
    'feature_group' => 'web_portal',
    'type' => 'boolean',
    'default' => false,
    'storage' => 'center_settings.settings.allow_web_playback',
    'feature_flag' => 'web_playback',
    'system_override' => 'force_disable_web_playback',
    'depends_on' => 'allow_web_access',
    'rules' => ['boolean'],
],
'web_device_limit' => [
    'scope' => 'center',
    'managed_by' => 'center',
    'group' => 'web_portal',
    'feature_group' => 'web_portal',
    'type' => 'integer',
    'default' => 1,
    'storage' => 'center_settings.settings.web_device_limit',
    'feature_flag' => 'web_access',
    'system_limit' => 'max_web_device_limit',
    'rules' => ['integer', 'min:1', 'max:10'],
],
'allow_parent_portal' => [
    'scope' => 'center',
    'managed_by' => 'center',
    'group' => 'parent_portal',
    'feature_group' => 'parent_portal',
    'type' => 'boolean',
    'default' => false,
    'storage' => 'center_settings.settings.allow_parent_portal',
    'feature_flag' => 'parent_portal',
    'system_override' => 'force_disable_parent_portal',
    'rules' => ['boolean'],
],
```

### System Settings

```php
'max_web_device_limit' => [
    'scope' => 'system',
    'managed_by' => 'system',
    'group' => 'limits',
    'feature_group' => 'web_portal',
    'type' => 'integer',
    'default' => 3,
    'storage' => 'system_settings.max_web_device_limit',
    'value_key' => 'value',
    'rules' => ['integer', 'min:1', 'max:10'],
],
'force_disable_web_access' => [
    'scope' => 'system',
    'managed_by' => 'system',
    'group' => 'overrides',
    'feature_group' => 'web_portal',
    'type' => 'boolean',
    'default' => false,
    'storage' => 'system_settings.force_disable_web_access',
    'value_key' => 'enabled',
    'rules' => ['boolean'],
],
'force_disable_web_playback' => [
    'scope' => 'system',
    'managed_by' => 'system',
    'group' => 'overrides',
    'feature_group' => 'web_portal',
    'type' => 'boolean',
    'default' => false,
    'storage' => 'system_settings.force_disable_web_playback',
    'value_key' => 'enabled',
    'rules' => ['boolean'],
],
'force_disable_parent_portal' => [
    'scope' => 'system',
    'managed_by' => 'system',
    'group' => 'overrides',
    'feature_group' => 'parent_portal',
    'type' => 'boolean',
    'default' => false,
    'storage' => 'system_settings.force_disable_parent_portal',
    'value_key' => 'enabled',
    'rules' => ['boolean'],
],
```

---

## Seeder Values

### SystemSettingSeeder (add to existing seeder)

```php
['key' => 'max_web_device_limit',          'value' => ['value' => 3],       'is_public' => false],
['key' => 'force_disable_web_access',      'value' => ['enabled' => false], 'is_public' => false],
['key' => 'force_disable_web_playback',    'value' => ['enabled' => false], 'is_public' => false],
['key' => 'force_disable_parent_portal',   'value' => ['enabled' => false], 'is_public' => false],
```

### CenterSettingSeeder (add to existing center defaults)

```php
'allow_web_access' => false,
'allow_web_playback' => false,
'web_device_limit' => 1,
'allow_parent_portal' => false,
```

### Feature Flags (add to existing features object)

```php
'features' => [
    // existing
    'ai_content' => true,
    'codes_access' => true,
    'whatsapp_bulk' => true,
    'guest_browsing' => true,
    'pdf_downloads' => true,
    // new
    'web_access' => false,
    'web_playback' => false,
    'parent_portal' => false,
],
```

---

## Resolution Rules

### SettingsResolverService Updates

Add to `applySystemConstraints()`:

```
web_device_limit = min(web_device_limit, max_web_device_limit)
```

### PolicySettingsService Updates

Add to `applyCenterGovernance()`:

```
if feature_flag `web_access` is disabled OR force_disable_web_access is true:
    allow_web_access = false
    allow_web_playback = false  (cascades — no web access means no playback)
    web_device_limit = 0

if feature_flag `web_playback` is disabled OR force_disable_web_playback is true:
    allow_web_playback = false

if feature_flag `parent_portal` is disabled OR force_disable_parent_portal is true:
    allow_parent_portal = false
```

Note: `web_playback` depends on `web_access` — disabling web access cascades to playback.

### Middleware Enforcement

- `JwtWebStudentMiddleware` checks resolved `allow_web_access` on every request
- `JwtWebStudentMiddleware` checks resolved `allow_web_playback` only on playback routes
- `JwtWebParentMiddleware` checks resolved `allow_parent_portal` on every request

---

## UI Groups

### Center Admin Settings Page

| Group | Settings Shown | Visible When |
|-------|---------------|-------------|
| Web Portal | allow_web_access, allow_web_playback, web_device_limit | Feature flag `web_access` is enabled |
| Parent Portal | allow_parent_portal | Feature flag `parent_portal` is enabled |

Settings appear grayed-out with explanation when:
- Feature flag is disabled: "This feature is not available for your center. Contact support."
- System override is active: "This feature has been temporarily disabled by the platform."

### System Admin Pages

| Page/Section | Settings |
|-------------|----------|
| Feature Flags (per center) | web_access, web_playback, parent_portal |
| System Limits | max_web_device_limit |
| System Overrides | force_disable_web_access, force_disable_web_playback, force_disable_parent_portal |

---

## Dependency Matrix

```
web_playback depends on web_access:
    - Cannot enable web_playback feature flag without web_access
    - Cannot set allow_web_playback = true without allow_web_access = true
    - Disabling web_access cascades to disable web_playback

parent_portal is independent:
    - No dependency on web_access or web_playback
    - Parents never access playback — read-only portal
```

---

## Validation Rules

### CenterSettingsService.enforceSystemConstraints()

```
allow_web_access:
    - Must be boolean
    - Forced false when: feature flag off OR system override on

allow_web_playback:
    - Must be boolean
    - Forced false when: feature flag off OR system override on OR allow_web_access is false

web_device_limit:
    - Must be integer >= 1 (admin input validation)
    - Capped at min(value, max_web_device_limit) (resolution)
    - Forced to 0 when allow_web_access is false (resolution override — bypasses min:1 rule
      because this is system-enforced, not admin-submitted)

allow_parent_portal:
    - Must be boolean
    - Forced false when: feature flag off OR system override on
```

### UpdateCenterSettingsRequest

Add to allowed center setting keys:
- `allow_web_access` → `'boolean'`
- `allow_web_playback` → `'boolean'`
- `web_device_limit` → `'integer|min:1|max:10'`
- `allow_parent_portal` → `'boolean'`

### UpdateCenterFeaturesRequest

Add to allowed feature flag keys:
- `web_access` → `'boolean'`
- `web_playback` → `'boolean'`
- `parent_portal` → `'boolean'`

---

## Migration Strategy

Single migration to add all settings:

1. Add new system settings to `system_settings` table via seeder
2. Add new feature flags to all existing `center_settings.settings.features` JSON
3. Add new center settings with defaults to all existing `center_settings.settings` JSON
4. No new columns on `centers` table — these settings only live in JSON (no legacy dual-storage needed since this is greenfield)

---

## Testing Requirements

- System admin can toggle feature flags per center
- Center admin can only edit center settings when feature flag is enabled
- Center admin cannot edit feature flags
- System overrides force-disable center settings
- web_device_limit is capped by max_web_device_limit
- Disabling web_access cascades to web_playback
- Middleware rejects requests when resolved setting is false
- Seeder correctly initializes all new settings
