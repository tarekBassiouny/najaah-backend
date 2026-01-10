# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains application code (controllers, actions, services, models).
- `config/`, `routes/`, `database/` (migrations/seeders), and `resources/` follow standard Laravel layout.
- `tests/` uses Pest with `Feature/` and `Unit/` suites.
- `docs/` holds domain and architecture rules; `docs/AI_INSTRUCTIONS.md` and `docs/codex/CODEX_DOMAIN_RULES.md` are authoritative.
- Frontend assets are built via Vite using `resources/` and `vite.config.js`.

## Build, Test, and Development Commands
- `composer install` sets up PHP dependencies.
- `php artisan serve` runs the local Laravel server.
- `npm install` installs Vite/Tailwind tooling.
- `npm run dev` starts Vite for local asset builds.
- `composer test` or `php artisan test` runs the test suite.
- `composer lint` runs Pint (format check) and PHPStan.
- `composer fix` applies Pint + Rector auto-fixes.
- `composer quality` runs formatting, static analysis, Rector dry-run, and tests.

## Coding Style & Naming Conventions
- PHP follows Laravel conventions and PSR-12-style formatting; use Pint for formatting.
- Keep controllers thin and use the project’s layered pattern: Controller → Action/Manager → Service → Model.
- Naming: classes in `StudlyCase`, methods/variables in `camelCase`, tables/columns in `snake_case`.
- Prefer explicit types, avoid dynamic properties, and use Form Requests for validation.

## Architecture & Domain Rules
- Follow `docs/AI_INSTRUCTIONS.md` for authentication, device binding, and course/video rules.
- Follow `docs/codex/CODEX_DOMAIN_RULES.md` for model/migration/service constraints (soft deletes, typed fillable/casts, service interfaces).

## Testing Guidelines
- Frameworks: Pest + PHPUnit (`tests/Pest.php`, `tests/Feature`, `tests/Unit`).
- Name tests `*Test.php` and place them in the matching suite.
- Run targeted tests with `php artisan test --filter SomeTest`.
- Coverage target is 90% minimum (`php artisan test --coverage --min=90`).

## Commit & Pull Request Guidelines
- Commit messages follow a short prefix pattern like `core: ...` or `fix: ...`.
- Keep commits focused and descriptive; avoid mixing unrelated changes.
- PRs should include a short summary, test command/results, and linked issues or tickets when applicable.

## Security & Configuration Tips
- Use `.env` for secrets; never commit credentials.
- Auth uses Sanctum for admins and JWT for students; follow the rules in `docs/AI_INSTRUCTIONS.md`.
- Media storage uses Bunny (videos) and S3-compatible storage (PDFs).
