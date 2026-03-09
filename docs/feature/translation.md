# Translation & Multi-Language (EN/AR) Tracking

## Overview
Track backend localization rollout for English/Arabic across API messages, validation errors, and translated content fields.

## Locked Decisions
- Supported locales: `en`, `ar`
- Locale precedence: `X-Locale` > `Accept-Language` > `config('app.locale')`
- Translation storage source of truth: `*_translations` JSON columns
- Fallback behavior: if `ar` missing, fallback to `en`
- Write policy: `en` required, `ar` optional
- Rollout strategy: phased by layer

## Goals
- Localize all backend API messages (success/error/validation) for EN/AR
- Keep response shape stable (no breaking API changes)
- Keep `error.code` stable and language-agnostic
- Ensure translated content is resolved consistently using request locale + fallback

## Non-Goals
- Adding languages beyond EN/AR in this phase
- Migrating to polymorphic `translations` table as primary storage
- Changing API endpoint paths or response contracts

---

## Progress Board

### Phase 1: Localization Infrastructure
Status: `COMPLETED`

- [x] Add centralized locale catalogs in config (`config/api_messages.php`)
- [x] Add localized validation catalog in centralized config structure
- [x] Add helper/util for localized API message retrieval
- [x] Add tests for locale resolution precedence/normalization
- [x] Add tests for normalized admin response defaults (EN/AR)

### Phase 2: Core API Message Migration
Status: `COMPLETED`

- [x] Add global API response localization middleware for mapped literal messages
- [x] Expand message map coverage across all controller/domain literals
- [x] Replace hardcoded messages in shared normalization middleware defaults
- [x] Localize FormRequest top-level validation wrapper messages via global mapped response localization
- [x] Ensure mapped errors keep stable `error.code`

### Phase 3: Content Translation Consistency
Status: `COMPLETED`

- [x] Audit all resources using `translate()` for consistency
- [x] Standardize fallback behavior in resource outputs
- [x] Replace locale-hardcoded query behavior where needed (mobile school/college lookup ordering)
- [x] Replace locale-hardcoded query behavior where needed (admin school/college ordering)
- [x] Add regression coverage for localized ordering behavior in EN/AR-sensitive lookups

### Phase 4: Docs & Contract Hardening
Status: `COMPLETED`

- [x] Update API docs with locale header behavior
- [x] Document fallback policy (`ar` -> `en`)
- [x] Document translation payload rules (`en` required, `ar` optional)
- [x] Add contributor guide for adding new localization keys

---

## API Contract Notes
- No endpoint path changes.
- No JSON shape changes.
- `message` and `error.message` are localized by request locale.
- `error.code` remains unchanged and should be used by frontend logic.
- `error.details` / `errors` validation strings are localized when locale is `ar`.

## Locale Header Behavior
- Resolution order is strict: `X-Locale` header, then `Accept-Language`, then `config('app.locale')`.
- Supported locales in this phase: `en`, `ar`.
- Regional language tags are normalized to primary locale:
  - `ar-EG` -> `ar`
  - `en-US` -> `en`
- Unsupported values fallback to configured app locale, then `en`.

## Translation Payload Rules
- Translation source remains `*_translations` JSON columns.
- Write contract:
  - `en` is required for write/create payloads.
  - `ar` is optional.
- Read contract:
  - Return value for current locale when available.
  - If missing in current locale, fallback to `en`.

## Contributor Guide (Localization Keys)
1. Add/modify keys in `config/api_messages.php` under `catalog.en` and `catalog.ar`.
2. For literal legacy English responses, map exact string in `reverse_en_to_key`.
3. For repeated dynamic patterns, prefer extending `ApiMessageCatalog::translatePattern()`.
4. For 422 validator details, extend `ApiValidationMessageLocalizer` regex patterns/templates.
5. Keep `error.code` language-agnostic; never localize machine error codes.
6. Add/adjust feature tests for `X-Locale=ar` and `X-Locale=en` behavior.

## Test Matrix (Required)
- [x] `X-Locale=ar` returns Arabic messages
- [x] `X-Locale=en` returns English messages
- [x] `X-Locale` overrides `Accept-Language`
- [x] Regional `Accept-Language` values normalize correctly (`ar-EG` => `ar`)
- [x] 422 responses are localized (top-level + field errors)
- [x] Missing `ar` content falls back to `en`
- [x] Full quality suite passes (`./vendor/bin/sail composer quality`)

---

## Risks
- Missing translation keys causing mixed-language responses
- Inconsistent fallback handling across resources
- Hardcoded strings missed in low-traffic endpoints

## Mitigations
- Add tests for key presence and EN/AR parity
- Add centralized key map for common messages/errors
- Run full regression and endpoint sampling in both locales

---

## Change Log
- `2026-03-09` - Initialized translation tracking doc with phased rollout plan.
- `2026-03-09` - Phase 1 started with config-based API message catalogs and normalized response localization tests (no `lang/` directory).
- `2026-03-09` - Added global API response localization middleware (header-driven EN/AR), mapped common literals, and made school/college lookup ordering locale-aware.
- `2026-03-09` - Added localized validation message templates and middleware localization for `error.details`/`errors` payloads.
- `2026-03-09` - Added middleware tests for locale precedence (`X-Locale` over `Accept-Language`) and regional locale normalization.
- `2026-03-09` - Extended locale-aware ordering consistency to admin school/college services.
