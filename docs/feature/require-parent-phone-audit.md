# require_parent_phone — Implementation Audit

## Status: FULLY IMPLEMENTED

The `require_parent_phone` feature is complete across all layers. No work needed.

## Flow Summary

### 1. Configuration (Admin)
- Center admin sets `education_profile.require_parent_phone = true` via `PATCH /centers/{center}/settings`
- Also has `enable_parent_phone` toggle (must be `true` for require to take effect)
- Stored in `center_settings.settings` JSON
- Defaults: `enable_parent_phone = true`, `require_parent_phone = false`

### 2. Profile Completion Check (Backend)
- `StudentProfileCompletionService::requiresParentPhoneCompletion()`:
  - Returns `false` if `enable_parent_phone != true` (feature disabled)
  - Returns `false` if `require_parent_phone != true` (not required)
  - Returns `true` if student's `parent_phone` is empty/whitespace
- When required and missing: added to `missing_steps: ['parent']` and `missing_fields: ['parent_phone']`
- `is_complete_profile = false` until filled

### 3. Mobile Client
- `EducationLookupController` exposes both flags in settings response
- Mobile app should:
  - Show/hide parent phone field based on `enable_parent_phone`
  - Mark as required based on `require_parent_phone`
  - Block navigation if `is_complete_profile = false`

### 4. Profile Update
- `PATCH /api/v1/mobile/me` accepts `parent_phone`
- Validated: regex `^[\d+\s().-]+$`, max 24 chars, nullable
- Normalized: strips non-digit/plus chars in `prepareForValidation()`
- Stored on `users.parent_phone` column

### 5. Registration
- `parent_phone` is NOT required at registration time
- Students register via OTP, then complete profile later
- Profile completion is a "soft block" — backend marks incomplete, mobile enforces UI block

### 6. Admin Student Management
- Admin can set `parent_phone` when creating/updating students
- `StoreStudentRequest` and `UpdateStudentRequest` both accept and validate it
- Visible in `StudentResource` and `StudentProfileResource`

## What Does NOT Happen (By Design)

- No hard backend block — students can still call APIs without parent_phone
- No forced logout when setting changes from false → true
- No migration of existing students — they just get `is_complete_profile = false` on next check
- No parent phone verification (no OTP to parent)

## Covered Files

| Layer | File | Status |
|-------|------|--------|
| Migration | `2026_03_21_000000_add_parent_phone_to_users_table.php` | Done |
| Model | `User.php` — `$fillable`, `$casts` | Done |
| Factory | `UserFactory.php` — `parent_phone => null` | Done |
| Service | `StudentProfileCompletionService.php` | Done |
| Service | `StudentEducationProfileService.php` | Done |
| Service | `StudentService.php` — create/update | Done |
| Controller | `MeController.php` — profile + update | Done |
| Controller | `EducationLookupController.php` — settings exposure | Done |
| Request | `UpdateProfileRequest.php` — validation | Done |
| Request | `StoreStudentRequest.php` — admin validation | Done |
| Request | `UpdateStudentRequest.php` — admin validation | Done |
| Resource | `StudentUserResource.php`, `StudentResource.php`, `StudentProfileResource.php` | Done |
| Settings | `PolicySettingsService.php` — catalog + defaults | Done |
| Settings | `CenterSettingFactory.php`, `CenterSettingSeeder.php` | Done |
| Settings | `UpdateCenterSettingsRequest.php` — validation | Done |
| Tests | `MeControllerTest.php`, `AuthControllerTest.php`, `EducationLookupTest.php`, `AdminCenterSettingsControllerTest.php`, `CenterPolicySettingsTest.php` | Done |
