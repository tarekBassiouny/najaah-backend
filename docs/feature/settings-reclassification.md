# Settings Reclassification & Feature Flags

## Objective

Audit and reclassify all system/center settings so that:
- System admin controls platform limits, infrastructure config, and feature availability
- Center admin configures behavior **within** system-defined boundaries
- Every new feature is gated by a center-level feature flag managed by system admin
- Add `require_parent_number` setting for student registration/profile

## Decisions (Approved)

### 1. Move to System Admin Scope
| Setting | Reason |
|---------|--------|
| `whatsapp_bulk_settings` | Infrastructure rate-limiting — not a center decision |

### 2. System Sets Limit, Center Works Within It (Option B)
| Setting | System Admin Controls | Center Admin Controls |
|---------|----------------------|----------------------|
| `default_view_limit` | `max_view_limit` (ceiling) | Any value ≤ system max |
| `device_limit` | `max_device_limit` (ceiling) | Any value ≤ system max |
| `allow_extra_view_requests` | `force_disable_extra_view_requests` (override) | Toggle ON/OFF when not force-disabled |
| `pdf_download_permission` | `force_disable_pdf_download` (override) | Toggle ON/OFF when not force-disabled |
| `allow_guest_browsing` | `force_disable_guest_browsing` (override) | Toggle ON/OFF when not force-disabled |

### 3. Center Feature Flags (New)
System admin enables/disables features per center via `features` in `center_settings`:

```json
{
  "ai_content": false,
  "codes_access": false,
  "whatsapp_bulk": false,
  "guest_browsing": true,
  "pdf_downloads": true
}
```

When a feature flag is `false`, the feature is unavailable to that center's users.

UI model:
- system admin sees feature flags inside the full per-center settings page
- center admin does not manage raw feature flags directly
- center admin only gets simplified summaries when platform policy blocks a workflow

### 4. `require_parent_phone` — ALREADY IMPLEMENTED
- Already exists as `education_profile.require_parent_phone` and `education_profile.enable_parent_phone`
- Fully wired: seeders, factories, `PolicySettingsService`, `UpdateCenterSettingsRequest`, `StudentProfileCompletionService`, tests
- **No work needed**

### 5. Settings That Stay As-Is
| Setting | Scope | Owner |
|---------|-------|-------|
| `site_name` | system | system admin |
| `support_email` | system | system admin |
| `timezone` | system | system admin |
| `require_device_approval` | system | system admin |
| `attendance_required` | system | system admin |
| `branding` | center | center admin |
| `education_profile` | center | center admin |
| `requires_video_approval` | center | center admin |
| `video_code_expiry_days` | center | center admin |

---

## Final Settings Map

### System Settings (`system_settings` table — system admin only)

| Key | Type | Default | Notes |
|-----|------|---------|-------|
| `site_name` | object | `{en, ar}` | Existing |
| `support_email` | string | `support@example.com` | Existing |
| `timezone` | string | `UTC` | Existing |
| `require_device_approval` | boolean | `false` | Existing |
| `attendance_required` | boolean | `false` | Existing |
| `whatsapp_bulk_settings` | object | batch config | **Moved from center** |
| `max_view_limit` | integer | `10` | **New** — ceiling for center `default_view_limit` |
| `max_device_limit` | integer | `3` | **New** — ceiling for center `device_limit` |
| `force_disable_extra_view_requests` | boolean | `false` | **New** — when true, overrides all centers |
| `force_disable_pdf_download` | boolean | `false` | **New** — when true, overrides all centers |
| `force_disable_guest_browsing` | boolean | `false` | **New** — when true, overrides all centers |

### Center Feature Flags (in `center_settings` — system admin only)

| Flag | Default | Controls |
|------|---------|----------|
| `features.ai_content` | `false` | AI content generation |
| `features.codes_access` | `false` | Video access codes |
| `features.whatsapp_bulk` | `false` | Bulk WhatsApp messaging |
| `features.guest_browsing` | `true` | Guest course browsing |
| `features.pdf_downloads` | `true` | PDF download capability |

### Center Settings Domain (role-aware)

The underlying domain is shared, but it is exposed through 2 center settings views:

- **System Admin Center Settings**
  - full per-center control page
  - includes center settings, feature flags, and AI policy
- **Center Admin Settings**
  - simplified page
  - includes only center-owned settings and limited summaries

### Center-Owned Settings (center admin — within system limits)

| Key | Type | Default | Constraint |
|-----|------|---------|------------|
| `default_view_limit` | integer | `2` | ≤ system `max_view_limit` |
| `device_limit` | integer | `1` | ≤ system `max_device_limit` |
| `allow_extra_view_requests` | boolean | `true` | Ignored if system force-disabled |
| `pdf_download_permission` | boolean | `false` | Ignored if system force-disabled OR feature flag off |
| `allow_guest_browsing` | boolean | `false` | Ignored if system force-disabled OR feature flag off |
| `requires_video_approval` | boolean | `false` | — |
| `video_code_expiry_days` | nullable int | `null` | Only when `features.codes_access` enabled |
| `branding` | object | `{}` | `logo_url`, `primary_color` |
| `education_profile` | object | defaults | grade/school/college toggles + `require_parent_phone` (already implemented) |

---

## Execution Phases

### Phase 1: Architecture — Schema & Catalog Updates
**Specialist**: `najaah-architecture`

1. Add new system setting keys via seeder/migration:
   - `whatsapp_bulk_settings` (migrate existing center values → system)
   - `max_view_limit` (default: 10)
   - `max_device_limit` (default: 3)
   - `force_disable_extra_view_requests` (default: false)
   - `force_disable_pdf_download` (default: false)
   - `force_disable_guest_browsing` (default: false)

2. Update `PolicySettingsService::catalog()`:
   - Add all new system keys with metadata
   - Add `features` group as center-scoped but system-admin-managed
   - Add `require_parent_number` under `education_profile`
   - Move `whatsapp_bulk_settings` scope from center to system

3. Data migration:
   - Copy each center's `whatsapp_bulk_settings` to a system default (or pick the most common config)
   - Remove `whatsapp_bulk_settings` from all `center_settings.settings` JSON
   - Seed default `center_features` for all existing centers (all existing features enabled)

**Files affected**:
- `app/Services/Settings/PolicySettingsService.php`
- `database/migrations/YYYY_MM_DD_XXXXXX_reclassify_settings.php` (new)
- `database/seeders/SystemSettingSeeder.php`
- `database/seeders/CenterSettingSeeder.php`

### Phase 2: Features — Service Layer Changes
**Specialist**: `najaah-features`

1. **`CenterSettingsService`**:
   - On `update()`: validate `default_view_limit ≤ max_view_limit` and `device_limit ≤ max_device_limit` by reading system settings
   - On `get()`: apply force-disable overrides and feature flag gating to the resolved output
   - Reject writes to `features.*` from center admin (only system admin can change feature flags)

2. **`SystemSettingService`**:
   - Accept the new system keys
   - When `max_view_limit` or `max_device_limit` changes: validate no center exceeds new ceiling (or clamp them)

3. **`SettingsResolverService`**:
   - Check system force-disable flags before returning boolean settings
   - Check feature flags before including feature-gated settings
   - Include `require_parent_number` in education profile resolution

4. **New: `CenterFeatureService`** (or extend `CenterSettingsService`):
   - `getFeatures(Center): array` — returns resolved feature flags
   - `updateFeatures(User $systemAdmin, Center, array): void` — system admin only
   - `isFeatureEnabled(Center, string $feature): bool` — check single flag

**Files affected**:
- `app/Services/Settings/CenterSettingsService.php`
- `app/Services/Settings/SystemSettingService.php`
- `app/Services/Settings/SettingsResolverService.php`
- `app/Services/Settings/PolicySettingsService.php`
- `app/Services/Settings/CenterFeatureService.php` (new, if separate)
- `app/Services/Settings/Contracts/CenterFeatureServiceInterface.php` (new, if separate)

### Phase 3: API — Routes, Requests, Controllers, Resources
**Specialist**: `najaah-api`

1. **System settings endpoints**:
   - existing `GET/POST/PUT/DELETE /api/v1/admin/settings`
   - response now acts as a backend-driven registry source for system settings UI

2. **Grouped center settings endpoint**:
   - `GET/PATCH /api/v1/admin/centers/{center}/settings`
   - role-aware payload
   - system admin gets full policy + AI + features surface
   - center admin gets simplified editable payload

3. **Legacy focused endpoints**:
   - `GET/PATCH /api/v1/admin/centers/{center}/features`
   - `GET/PUT /api/v1/admin/centers/{center}/ai/providers/{provider}`
   - kept for compatibility, but grouped center settings is the preferred frontend settings surface

**Files affected**:
- `app/Http/Requests/Admin/Centers/UpdateCenterSettingsRequest.php`
- `app/Http/Controllers/Admin/Centers/CenterOperationsController.php`
- `app/Http/Resources/Admin/Centers/CenterSettingResource.php`
- `routes/api/v1/admin/centers.php`
- New request/controller for center features (system admin)

### Phase 4: Quality — Tests & Validation
**Specialist**: `najaah-quality`

1. Test system limit enforcement:
   - Center admin cannot set `default_view_limit` > `max_view_limit`
   - Center admin cannot set `device_limit` > `max_device_limit`

2. Test force-disable overrides:
   - When `force_disable_pdf_download = true`, resolved settings always return `false`
   - When `force_disable_guest_browsing = true`, resolved settings always return `false`

3. Test feature flags:
   - Center admin cannot modify `features.*`
   - System admin can toggle features per center
   - Feature-gated settings hidden when feature is off

4. Test data migration:
   - `whatsapp_bulk_settings` removed from center settings after migration
   - Existing center configs preserved during migration

5. Test `require_parent_number`:
   - Included in education profile resolution
   - Respected during student registration/profile completion

**Files affected**:
- `tests/Feature/Admin/AdminCenterSettingsControllerTest.php`
- `tests/Feature/Admin/SystemSettingControllerTest.php`
- `tests/Feature/Admin/CenterPolicySettingsTest.php`
- `tests/Feature/Admin/CenterFeatureFlagsTest.php` (new)
- `tests/Feature/Admin/SettingsPreviewTest.php`

### Phase 5: Documentation
- Update Postman/Scribe docs
- Update this plan with final implementation notes

---

## Risks & Assumptions

| Risk | Mitigation |
|------|------------|
| Existing center `whatsapp_bulk_settings` lost during migration | Migration copies to system default first |
| Centers currently exceeding new system max limits | Migration clamps existing values or sets generous defaults |
| Frontend expects `whatsapp_bulk_settings` in center response | Frontend update needed — coordinate with frontend team |
| Feature flags break existing center admin workflows | Default all flags to `true` for existing centers |
| `require_parent_number` affects existing students | Only enforce on new registrations / profile updates, not retroactively |

## Implementation Status

- [x] Phase 1: Architecture — migration, catalog, seeders, factory updates
- [x] Phase 2: Features — service enforcement, system constraints, feature flags, guest browsing force-disable
- [x] Phase 3: API — routes, requests, controllers, resources, `allow_guest_browsing` moved to settings
- [x] Phase 4: Quality — syntax validation (tests require PHP 8.4, run in CI)
- [x] Phase 5: Documentation — frontend guide created at `docs/feature/settings-reclassification-frontend-guide.md`

## Resolved Items
- `max_view_limit` default = 10, `max_device_limit` default = 3 (confirmed)
- Feature flag defaults for existing centers = all enabled (confirmed)
- `require_parent_phone` already fully implemented — no work needed
- `allow_guest_browsing` moved from `UpdateCenterRequest` to `UpdateCenterSettingsRequest`, synced to column via dual-storage pattern
- final page model is:
  - system settings
  - system admin center settings
  - center admin settings
