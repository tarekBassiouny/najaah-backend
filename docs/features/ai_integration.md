# AI Integration (System + Center Scope)

## Status
- Feature: Generic AI integration service + provider/model management + center-level controls
- Branch: `feature/assignment-quizz`
- Last updated: `2026-03-11`
- Owner: Backend + Frontend
- Progress: `80%`

## Goals
- Build one reusable AI integration layer for all AI features (assignment, quiz, summary, flashcards, interactive activities).
- Allow system to configure providers/models/keys once.
- Allow per-center enable/disable and model selection.
- Allow per-center AI usage limits and enforce them at runtime.
- Provide backend-driven provider/model metadata so frontend is dynamic, not hardcoded.

## Locked Decisions
- API keys are never returned in API responses.
- Students never call AI directly.
- AI output is always draft-first and admin-reviewed before publish.
- Current AI content job lifecycle remains the backbone:
  - `pending -> processing -> completed -> approved -> published`
  - terminal paths: `failed`, `discarded`

## Current Implemented State
- Admin AI content jobs endpoints exist and are active.
- Provider/model can be selected per job payload (`ai_provider`, `ai_model`).
- Supported providers in backend service:
  - `openai`
  - `anthropic`
  - `gemini`
- Env-driven credentials currently supported:
  - `OPENAI_API_KEY`
  - `ANTHROPIC_API_KEY`
  - `GEMINI_API_KEY`

## Target Generic Architecture
1. `AIIntegrationService`
- Single entry point used by feature services (assignment generation first).
- Resolves provider client, model, and effective config.

2. Provider Clients
- `OpenAIClient`
- `AnthropicClient`
- `GeminiClient`
- All implement common contract (`generate(prompt, model, options): array`).

3. Config Resolution Layers
- System scope: provider definitions, credentials, default models.
- Center scope: provider enable/disable + allowed models + center default + limits.
- Effective config priority:
  - Center override -> System default -> Provider fallback.

4. Job Snapshot
- Each job stores provider/model snapshot used for processing.
- API key is not exposed in resources.

## Proposed Data Model (Planned)
1. `ai_provider_configs` (system)
- `provider_key` (`openai|anthropic|gemini|...`)
- `display_name`
- `is_enabled`
- `default_model`
- `models` (json array)
- `api_key_ref` (preferred) or encrypted secret field
- timestamps

2. `ai_center_provider_settings` (center)
- `center_id`
- `provider_key`
- `is_enabled`
- `allowed_models` (json array)
- `default_model`
- `limits` (json object)
- timestamps

### Per-center limits (in `limits` JSON)
- `daily_job_limit` (max jobs/day)
- `monthly_job_limit` (max jobs/month)
- `daily_token_limit` (max estimated tokens/day)
- `monthly_token_limit` (max estimated tokens/month)
- `max_input_chars` (prompt/source cap)
- `max_output_chars` (response cap)
- `max_concurrent_jobs` (processing queue cap per center)

## API Plan for Frontend

### A) Existing AI Content Job APIs (already usable)
- `GET /api/v1/admin/centers/{center}/ai-content/jobs`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs`
- `GET /api/v1/admin/centers/{center}/ai-content/jobs/{job}`
- `PATCH /api/v1/admin/centers/{center}/ai-content/jobs/{job}/review`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/approve`
- `POST /api/v1/admin/centers/{center}/ai-content/jobs/{job}/publish`
- `DELETE /api/v1/admin/centers/{center}/ai-content/jobs/{job}`

### B) Provider Configuration APIs
- `GET /api/v1/admin/centers/{center}/ai/options`
  - frontend dropdown source for AI content job creation
  - returns effective center options
- `GET /api/v1/admin/ai/providers`
  - system-level provider list/config
- `PUT /api/v1/admin/ai/providers/{provider}`
  - system-level provider config update
- `GET /api/v1/admin/centers/{center}/ai/providers`
  - legacy focused center-level provider settings
- `PUT /api/v1/admin/centers/{center}/ai/providers/{provider}`
  - legacy focused provider update endpoint
- `GET /api/v1/admin/centers/{center}/settings`
  - preferred settings-page source
  - system admin gets full AI policy payload
  - center admin gets simplified AI settings payload
- `PATCH /api/v1/admin/centers/{center}/settings`
  - preferred grouped settings update endpoint
  - system admin can update provider enablement, allowlists, default model, and limits
  - center admin can update only `default_model`

## Frontend Contract

### A) AI content job dropdowns

### Response shape (planned)
```json
{
  "success": true,
  "data": {
    "default_provider": "openai",
    "providers": [
      {
        "key": "openai",
        "label": "OpenAI",
        "enabled": true,
        "configured": true,
        "default_model": "gpt-4o-mini",
        "limits": {
          "daily_job_limit": 200,
          "monthly_job_limit": 3000,
          "daily_token_limit": 1200000,
          "monthly_token_limit": 30000000,
          "max_input_chars": 60000,
          "max_output_chars": 30000,
          "max_concurrent_jobs": 10
        },
        "models": [
          "gpt-4o-mini",
          "gpt-4.1-mini"
        ]
      },
      {
        "key": "gemini",
        "label": "Gemini",
        "enabled": true,
        "configured": true,
        "default_model": "gemini-1.5-flash",
        "limits": {
          "daily_job_limit": 200,
          "monthly_job_limit": 3000,
          "daily_token_limit": 1200000,
          "monthly_token_limit": 30000000,
          "max_input_chars": 60000,
          "max_output_chars": 30000,
          "max_concurrent_jobs": 10
        },
        "models": [
          "gemini-1.5-flash",
          "gemini-1.5-pro"
        ]
      }
    ]
  }
}
```

### Frontend behavior
1. Load dropdown data from `GET /ai/options`.
2. Show only `enabled && configured` providers.
3. When provider changes, refresh model dropdown from that provider's `models`.
4. Preselect `default_provider` and provider `default_model`.
5. Submit selected values in create job payload:
```json
{
  "course_id": 12,
  "source_type": "video",
  "source_id": 55,
  "target_type": "assignment",
  "ai_provider": "gemini",
  "ai_model": "gemini-1.5-flash"
}
```
6. Use returned limits to show UI guardrails before submit (char counters, disabled submit when exceeded).

### B) Settings pages

Frontend should not use hard-coded provider lists or AI policy fields on settings pages.

Use grouped center settings instead:

- `GET /api/v1/admin/centers/{center}/settings`
- `PATCH /api/v1/admin/centers/{center}/settings`

System admin view:
- reads `data.sections.ai.providers`
- can edit `is_enabled`, `allowed_models`, `default_model`, and `limits`
- should also respect top-level `data.features.ai_content`

Center admin view:
- reads simplified `data.sections.ai.providers`
- can edit only `default_model`
- should use `data.summaries` for plain-language policy notices
- should not show raw provider limits or allowlists

Example system-admin AI section from grouped center settings:

```json
{
  "sections": {
    "ai": {
      "feature_enabled": true,
      "providers": [
        {
          "key": "openai",
          "label": "OpenAI",
          "enabled": true,
          "configured": true,
          "default_model": "gpt-4o-mini",
          "models": ["gpt-4o-mini", "gpt-4.1-mini"],
          "allowed_models": ["gpt-4o-mini"],
          "limits": {
            "daily_job_limit": 20
          },
          "editable_fields": [
            "is_enabled",
            "allowed_models",
            "default_model",
            "limits"
          ]
        }
      ]
    }
  }
}
```

Example center-admin AI section from grouped center settings:

```json
{
  "sections": {
    "ai": {
      "feature_enabled": true,
      "providers": [
        {
          "key": "openai",
          "label": "OpenAI",
          "enabled": true,
          "configured": true,
          "default_model": "gpt-4o-mini",
          "models": ["gpt-4o-mini"],
          "managed_by": "platform",
          "editable_fields": ["default_model"]
        }
      ]
    }
  },
  "summaries": [
    {
      "type": "info",
      "title": "AI provider managed by platform",
      "message": "OpenAI is configured for your center. Provider availability and limits are managed by platform admin."
    }
  ]
}
```

## Env Keys (Current)
```env
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
```

## Security Rules
1. Never expose API keys to frontend.
2. Never store API keys in plain text.
3. Log provider/model usage and failures without secret material.
4. Keep provider config changes under strict admin permission + audit logs.

## Runtime Enforcement Rules (Per Center)
1. Reject create job if center exceeds daily/monthly job cap.
2. Reject create job if center exceeds concurrent processing cap.
3. Reject create/review/publish if payload size exceeds center max input/output char rules.
4. Record usage counters per center/provider/model for day and month windows.
5. Return stable error codes for frontend:
- `AI_LIMIT_DAILY_JOBS_EXCEEDED`
- `AI_LIMIT_MONTHLY_JOBS_EXCEEDED`
- `AI_LIMIT_DAILY_TOKENS_EXCEEDED`
- `AI_LIMIT_MONTHLY_TOKENS_EXCEEDED`
- `AI_LIMIT_CONCURRENT_JOBS_EXCEEDED`
- `AI_LIMIT_INPUT_TOO_LARGE`
- `AI_LIMIT_OUTPUT_TOO_LARGE`

## Rollout Plan

### Phase 1 - Integration Core
- [x] Centralize provider/model resolution in `AIIntegrationService`.
- [x] Keep current AI content job flow working without regressions.
- [ ] Extract provider clients behind shared contract (AI HTTP client split is pending).

### Phase 2 - System Config
- [x] Add system provider config persistence (DB).
- [x] Add system APIs for provider/model management.
- [ ] Add test endpoint for provider connectivity.

### Phase 3 - Center Overrides
- [x] Add center-level provider settings table/service.
- [x] Add center APIs for enable/disable, model allowlist, and limits.
- [x] Add effective options endpoint for frontend dropdown.
- [x] Add limit usage counters and enforcement checks.

### Phase 4 - Assignment Integration
- [x] Build assignment-generation adapter on top of `AIIntegrationService`.
- [x] Publish approved assignment payload into assignment domain.
- [x] Add end-to-end feature tests.

### Phase 5 - Docs + Quality
- [x] Update Scribe docs for new AI config endpoints (via FormRequest query/body metadata).
- [ ] Update Postman collection structure.
- [ ] Run `./vendor/bin/sail composer quality`.

## Test Checklist (Manual)
- [ ] Create job with OpenAI model.
- [ ] Create job with Anthropic model.
- [ ] Create job with Gemini model.
- [ ] Invalid provider -> `422`.
- [ ] Invalid model for selected provider -> `422`.
- [ ] Center-disabled provider cannot be used -> `422` or `403` (as finalized).
- [ ] Dropdown endpoint returns only effective center options.
- [ ] Daily job cap reached -> blocked with expected error code.
- [ ] Monthly job cap reached -> blocked with expected error code.
- [ ] Concurrent cap reached -> blocked with expected error code.
- [ ] Oversized input/output payload -> blocked with expected error code.

## Notes for Frontend Team
- Do not hardcode provider keys or model lists anywhere.
- Use `GET /ai/options` for AI job creation UX.
- Use grouped center settings for AI settings pages.
- Treat `editable_fields` as the source of truth for which AI controls are writable.
- If `features.ai_content` is off for a center, settings UI should show a platform-managed status instead of editable AI controls.
- Frontend should read center limits from backend responses and enforce pre-submit UX guards when limits are exposed.
