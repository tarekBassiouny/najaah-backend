# XYZ Laravel LMS — Full Codex Refactor Instructions

This repository must be refactored by GitHub Copilot/Codex to achieve:

- 100% PHPStan Level 8 compliance
- 100% PSR-12 / Laravel Pint compliance
- 100% typed Laravel 11 codebase
- Fully consistent Models, Migrations, Factories, Seeders
- Controllers, Services, Resources fully refactored and type-safe
- Zero dynamic/mixed usages
- Domain consistency enforced across the entire repo
- No business logic in controllers (strict service-layer pattern)
- No unused imports or dead code
- Correct generic type definitions on Eloquent relations
- Strict return types everywhere
- Consistent JSON translation fields (no Spatie Translatable)

Codex must read and follow the rules defined in:
- `/docs/codex/CODEX_DOMAIN_RULES.md`
- `/AI_INSTRUCTIONS.md`
- `/infrastructure.md`
- `/laravel12-best-practices.md`

All changes must be applied through a Pull Request.

---

## 🔧 TASKS FOR CODEX

### 1. **Models**
Refactor all models according to domain rules:
- Add typed properties
- Add `@property` docblocks for PHPStan
- Add correct `@return` types with full generics:
  - `BelongsTo<Model, ThisModel>`
  - `HasMany<Model, ThisModel>`
  - `BelongsToMany<Model, ThisModel>`
  - `MorphTo`
- Add SoftDeletes globally
- Remove unused traits
- Add consistent `$fillable` and `$casts`
- Fix all translation JSON casts

### 2. **Migrations**
- Ensure all migrations match the models exactly
- Ensure all timestamps, foreign keys, and indexes are correct
- Ensure soft deletes exist on all models
- Ensure pivot tables follow naming conventions

### 3. **Factories**
- Ensure factories match schema
- Ensure multilingual fields use consistent JSON encoding
- Fix faker usages
- Ensure no optional() issues

### 4. **Seeders**
- Ensure no duplicate unique values
- Ensure seeder logic matches correct table structure

### 5. **Services**
Refactor all services to:
- Accept interfaces (dependency inversion)
- Use constructor injection
- Remove `app()` container calls
- Provide strict return types
- Add unit tests for:
  - OtpService
  - JwtService
  - DeviceService

### 6. **Controllers**
- Inject services via constructor
- Remove all mixed usages
- Add strict return types
- Move all logic to services

### 7. **Resources**
- Add typed `$this->resource` annotations
- Implement full return array typing
- No dynamic property access

### 8. **Middleware**
- Add strict types
- Enforce typed `$next(…)`

### 9. **Testing**
Codex must create:
- Feature tests:
  - OTP send
  - OTP verify
  - Login
  - Device register
  - JWT refresh
  - JWT-protected route
  - Invalid token
  - Expired token
  - Invalid OTP
  - Admin login using Sanctum
  - Admin protected routes
- Unit tests for:
  - OtpService
  - JwtService
  - DeviceService
  - Middleware

### 10. **Static Analysis**
Codex must ensure:
- `phpstan.neon` passes with 0 errors
- `./vendor/bin/sail pint --test` returns 0 issues
- `php artisan test` returns green

---

# ✔ Deliverable
A complete pull request implementing all improvements above, with commits grouped logically by component (models, migrations, factories, seeders, services, controllers, tests, QA tools).
