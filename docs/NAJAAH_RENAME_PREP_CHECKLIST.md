# Najaah Branding Baseline Checklist

Goal: keep backend/runtime/docs consistently branded as `Najaah` with domain/API references on `najaah.me`.

## 1. Canonical Branding Values

- Display name: `Najaah LMS`
- Primary domain: `najaah.me`
- Package/vendor naming baseline: `najaah/najaah-backend`
- Mobile defaults:
- iOS link path uses `https://apps.apple.com/app/najaah`
- Android package uses `com.najaah.lms`

## 2. Runtime-Critical Files

- `.env`: confirm `APP_NAME`, `APP_URL`, and `SPACES_BUCKET` values align with Najaah
- `docker/nginx/default.conf`: server names use `najaah.me`
- `config/sanctum.php`: stateful domains include `najaah.me`
- `config/notifications.php`: default app links point to Najaah
- `database/seeders/SystemSettingSeeder.php`: seeded site name is Najaah
- `app/Services/Students/StudentNotificationService.php`: fallback center name is Najaah
- `composer.json`: Postman download endpoint and package description/name are Najaah-aligned

## 3. Postman and API Docs

- `postman/restructure.js`: output file is `postman/najaah.postman.json`
- `postman/restructure.js`: collection title is `Najaah LMS API (v1)`
- `.gitignore`: ignores `postman/najaah.postman.json`

## 4. Public Admin Test Pages

- `public/admin/index.html`: title and heading are Najaah
- `public/admin/pdf-upload.html`: API base URL uses `najaah.me`
- `public/admin/pdf-view.html`: API base URL uses `najaah.me`
- `public/admin/video-upload.html`: API base URL uses `najaah.me`
- `public/admin/video-view.html`: API base URL uses `najaah.me`

## 5. Documentation and Agent Content

- `CLAUDE.md`
- `.github/ISSUE_TEMPLATE/lms-task.md`
- `docs/AI_INSTRUCTIONS.md`
- `docs/CLAUDE_CONTEXT.md`
- `docs/logging.md`
- `docs/ISSUES_TRACKER.md`
- `docs/codex/CODEX_DOMAIN_RULES.md`
- `docs/codex/CODEX_REFACTOR.md`
- `docs/Najaah LMS - Master Database Schema.md`
- `docs/ai-skills/PACKAGE_SUMMARY.md`
- `.claude/agents/orchestrator.md`
- `.claude/skills/najaah*/**`

## 6. Validation Commands

- Legacy-brand scan:
- `rg -n --hidden --glob '!.git' "\\bXYZ\\b|xyz-lms|xyz\\.com|com\\.xyz\\.lms|apps\\.apple\\.com/app/xyz-lms"`
- Smoke tests:
- `./vendor/bin/sail artisan test`
- Static analysis:
- `./vendor/bin/sail php ./vendor/bin/phpstan analyse`
- Postman regeneration:
- `./vendor/bin/sail composer postman:generate`

## 7. Done Criteria

- No runtime/user-facing defaults reference legacy XYZ naming
- No internal docs/agent text references legacy XYZ naming
- Postman output and naming are Najaah-based
- Scan command returns no legacy hits except explicitly approved historical references
