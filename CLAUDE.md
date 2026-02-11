# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Najaah LMS is a multi-tenant Learning Management System backend built with Laravel 11 and PHP 8.4. It supports multiple centers (branded with unique subdomains, or unbranded under Najaah org) sharing a single database with `center_id` isolation.

## Build & Development Commands

```bash
# Dependencies
composer install
npm install

# Run local server
php artisan serve
npm run dev          # Vite for assets

# Testing
composer test                              # Run all tests
php artisan test                           # Alternative
php artisan test --filter SomeTest         # Single test
php artisan test --coverage --min=90       # With coverage

# Code Quality
composer lint        # Pint (format check) + PHPStan
composer fix         # Pint + Rector auto-fixes
composer quality     # Full suite: lint + Rector dry-run + tests

# Static Analysis
./vendor/bin/phpstan analyse               # PHPStan level 7
./vendor/bin/pint --test                   # Format check only
./vendor/bin/rector process --dry-run      # Rector dry-run

# API Documentation
composer postman:generate                  # Generate Postman collection via Scribe
```

## Architecture Pattern

Follow the layered architecture strictly:

```
Controller → Action/Manager → Service → Model
```

- **Controllers**: Thin, no business logic. Use Form Requests for validation, call Actions/Services, return Resources.
- **Actions/Managers** (`app/Actions/`): Orchestration layer for multi-step operations.
- **Services** (`app/Services/`): Business logic only. Each service must have an interface in `/app/Services/Contracts/`.
- **Models** (`app/Models/`): Relationships and casts only. No business logic.
- **Resources** (`app/Http/Resources/`): Always use API Resources for responses—no direct arrays.

## Domain Rules

Reference `docs/AI_INSTRUCTIONS.md` for the authoritative rules on:
- Multi-center architecture (branded vs unbranded)
- Authentication (Sanctum for admin/web, JWT for mobile students)
- Device binding (one active device per student)
- Course hierarchy: Course → Sections → Videos/PDFs (no "lesson" layer)
- Video playback security (Bunny Stream, signed URLs, view limits)
- Settings override priority: Student > Video > Course > Center

Reference `docs/codex/CODEX_DOMAIN_RULES.md` for model/service constraints.

## Key Conventions

### Models
- Always use `SoftDeletes` and `HasFactory`
- Typed `$fillable` and `$casts`
- Full generic relation types: `HasMany<Model, ThisModel>`, `BelongsTo<Model, ThisModel>`
- Translation fields as JSON: `title_translations`, `description_translations`

### Migrations
- BIGINT UNSIGNED AUTO_INCREMENT for primary keys
- Always include `timestamps()` and `softDeletes()`
- Foreign keys with `cascadeOnDelete()` and `cascadeOnUpdate()`
- Index all foreign keys, slugs, and `deleted_at`

### Services
- Must have interface in `/app/Services/Contracts/`
- Constructor injection only (no `app()` calls)
- Strict return types, throw custom exceptions

### API Responses
Unified format under `/api/v1/`:
```json
{"success": true, "message": "...", "data": {...}}
{"success": false, "error": {"code": "...", "message": "..."}}
```

### Testing
- Pest + PHPUnit in `tests/Feature/` and `tests/Unit/`
- Test helpers in `tests/Helpers/` (MakesTestUsers, AdminTestHelper, ApiTestHelper, etc.)
- Coverage target: 90%

## Commit Style

Use short prefix patterns: `core: ...`, `fix: ...`, `feat: ...`
