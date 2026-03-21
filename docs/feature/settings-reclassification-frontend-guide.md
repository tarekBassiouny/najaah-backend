# Settings Reclassification — Frontend Guide

## Goal

Frontend should treat backend settings metadata as the source of truth.

Do not hard-code:
- setting keys
- setting groups
- feature-flag lists
- AI provider lists
- AI model lists
- system ceilings

When backend adds a new setting to the catalog, frontend should pick it up from API responses and render it without a frontend release for each new key.

## Final Page Model

There are 3 settings pages:

| Page | Audience | Primary endpoint | Purpose |
|------|----------|------------------|---------|
| System Settings | system admin | `GET /api/v1/admin/settings` | Base platform settings and defaults |
| System Admin Center Settings | system admin | `GET /api/v1/admin/centers/{center}/settings` | Full per-center policy + operational settings |
| Center Admin Settings | center admin | `GET /api/v1/admin/centers/{center}/settings` | Simple center-facing settings page |

`/api/v1/admin/centers/{center}/settings` is now a role-aware endpoint:
- system admin receives the full grouped payload
- center admin receives a simplified payload

Legacy endpoints still exist:
- `GET/PATCH /api/v1/admin/centers/{center}/features`
- `GET/PUT /api/v1/admin/centers/{center}/ai/providers/{provider}`

Frontend should prefer the grouped center settings endpoint for settings pages. Legacy endpoints are mainly compatibility and focused tools.

## 1. System Settings Page

### Endpoint

- `GET /api/v1/admin/settings`
- `POST /api/v1/admin/settings`
- `PUT /api/v1/admin/settings/{id}`
- `DELETE /api/v1/admin/settings/{id}`

### GET response contract

`data[]` contains stored rows.

`meta` now also contains the backend metadata needed for dynamic rendering:

```json
{
  "success": true,
  "data": [
    {
      "id": 7,
      "key": "support_email",
      "value": {
        "email": "ops@example.com"
      },
      "is_public": true
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1,
    "catalog": {
      "support_email": {
        "scope": "system",
        "managed_by": "system",
        "group": "general",
        "type": "string",
        "storage": "system_settings.key=support_email",
        "default": "support@example.com"
      }
    },
    "catalog_groups": {
      "general": ["site_name", "timezone", "support_email"],
      "security": ["require_device_approval", "attendance_required"],
      "limits": ["max_view_limit", "max_device_limit"],
      "overrides": [
        "force_disable_extra_view_requests",
        "force_disable_pdf_download",
        "force_disable_guest_browsing"
      ],
      "infrastructure": ["whatsapp_bulk_settings"]
    },
    "defaults": {
      "site_name": {
        "en": "Najaah LMS",
        "ar": "..."
      },
      "timezone": "UTC",
      "support_email": "ops@example.com"
    }
  }
}
```

### Frontend rendering rules

1. Build a `settingsByKey` map from `data[]`.
2. Iterate `meta.catalog_groups` to render sections.
3. For each key, read field metadata from `meta.catalog[key]`.
4. Use stored row value when present, otherwise fall back to `meta.defaults[key]`.
5. Submit updates by key/value through the existing CRUD endpoints.

### Important note

System settings are still persisted as key/value rows, so frontend should treat this page as a dynamic settings registry UI, not as a fixed form with hard-coded keys.

## 2. System Admin Center Settings Page

### Endpoint

- `GET /api/v1/admin/centers/{center}/settings`
- `PATCH /api/v1/admin/centers/{center}/settings`

### Purpose

This is the full control panel for one center.

System admin can update:
- center settings
- feature flags
- AI provider availability
- AI provider allowed models
- AI provider default model
- AI provider per-center limits

### GET response shape

Important fields:

```json
{
  "data": {
    "id": 1,
    "center_id": 5,
    "settings": {
      "...": "raw stored settings"
    },
    "resolved_settings": {
      "...": "effective settings after system constraints and feature gating"
    },
    "system_constraints": {
      "max_view_limit": 10,
      "max_device_limit": 3,
      "force_disable_extra_view_requests": false,
      "force_disable_pdf_download": false,
      "force_disable_guest_browsing": false
    },
    "page": {
      "type": "system_admin_center_settings",
      "actor_scope": "system",
      "editable": {
        "settings": ["default_view_limit", "branding", "..."],
        "features": ["ai_content", "codes_access", "..."],
        "ai": {
          "providers": {
            "openai": [
              "is_enabled",
              "allowed_models",
              "default_model",
              "limits"
            ]
          }
        }
      }
    },
    "features": {
      "ai_content": true,
      "codes_access": true,
      "guest_browsing": true,
      "pdf_downloads": true
    },
    "catalog": {
      "...": "full settings catalog from backend"
    },
    "system_defaults": {
      "...": "resolved system defaults"
    },
    "sections": {
      "settings": {
        "groups": {
          "playback": {
            "default_view_limit": 3
          },
          "branding": {
            "branding": {
              "logo_url": "https://...",
              "primary_color": "#123456"
            }
          }
        },
        "resolved_groups": {
          "...": "effective grouped values"
        }
      },
      "features": {
        "values": {
          "ai_content": true,
          "codes_access": true
        }
      },
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
}
```

### PATCH request shape

Send only the sections being changed.

```json
{
  "settings": {
    "default_view_limit": 4,
    "branding": {
      "primary_color": "#0f172a"
    }
  },
  "features": {
    "ai_content": false
  },
  "ai": {
    "providers": {
      "openai": {
        "is_enabled": true,
        "allowed_models": ["gpt-4o-mini"],
        "default_model": "gpt-4o-mini",
        "limits": {
          "daily_job_limit": 20
        }
      }
    }
  }
}
```

### Frontend rendering rules

Use the response in this order:

1. `page.type` to detect system-admin view.
2. `sections.settings.groups` to render editable grouped form fields.
3. `catalog` to determine field type, group, nested properties, ownership, feature gates, and system-limit keys.
4. `system_constraints` to set max values and disabled states.
5. `sections.features.values` for feature toggles.
6. `sections.ai.providers` for provider cards, model lists, and per-provider limits.
7. `resolved_settings` or `sections.settings.resolved_groups` when UI needs the effective post-governance value.

Do not read feature state from raw `settings.features`. Use top-level `features` or `sections.features.values`.

## 3. Center Admin Settings Page

### Endpoint

- `GET /api/v1/admin/centers/{center}/settings`
- `PATCH /api/v1/admin/centers/{center}/settings`

### Purpose

This page is intentionally simple.

Center admin should see:
- editable center-owned settings
- a small number of plain-language summaries when platform policy blocks something
- simplified AI controls only where allowed

Center admin should not see:
- raw feature-flag keys
- full catalog
- provider internal policy matrix
- raw limit JSON
- system-only force-disable internals

### GET response shape

```json
{
  "data": {
    "settings": {
      "...": "raw center-owned values"
    },
    "resolved_settings": {
      "...": "effective values after platform policy"
    },
    "system_constraints": {
      "max_view_limit": 10,
      "max_device_limit": 3,
      "force_disable_extra_view_requests": false,
      "force_disable_pdf_download": false,
      "force_disable_guest_browsing": false
    },
    "page": {
      "type": "center_admin_settings",
      "actor_scope": "center",
      "editable": {
        "settings": ["default_view_limit", "branding", "..."],
        "features": [],
        "ai": {
          "providers": {
            "openai": ["default_model"]
          }
        }
      }
    },
    "sections": {
      "settings": {
        "groups": {
          "...": "grouped editable settings"
        },
        "resolved_groups": {
          "...": "effective grouped values"
        }
      },
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
}
```

### PATCH request shape

Center admin can update only allowed local settings and AI `default_model`.

```json
{
  "settings": {
    "branding": {
      "primary_color": "#2563eb"
    },
    "education_profile": {
      "require_parent_phone": true
    }
  },
  "ai": {
    "providers": {
      "openai": {
        "default_model": "gpt-4o-mini"
      }
    }
  }
}
```

If center admin sends `features`, `is_enabled`, `allowed_models`, or `limits`, backend rejects the request with `422 VALIDATION_ERROR`.

### Frontend rendering rules

1. Use `page.type` to detect center-admin view.
2. Render only `sections.settings.groups`.
3. Use `page.editable` to decide which fields are interactive.
4. Use `sections.ai.providers` to render any allowed AI default-model selector.
5. Use `summaries` to explain blocked workflows in plain language.
6. Use `system_constraints` for numeric ceilings and forced disabled states.

Do not show read-only policy internals as a large disabled form.

## Dynamic Rendering Strategy

### Generic rules

Frontend should be data-driven:

1. Group fields by backend-provided group.
2. Infer control shape from backend `type`:
   - `boolean` -> toggle
   - `integer` -> number input
   - `string` -> text input
   - `object` -> nested fieldset
3. For nested objects, recurse through `properties`.
4. Use `editable` metadata to switch field mode:
   - editable
   - read-only summary
   - hidden
5. Use `feature_flag` and `system_override` metadata from the catalog when present.

### Required frontend behavior

- Do not keep a local allowlist of settings keys.
- Do not keep a local AI provider registry.
- Do not assume fixed groups such as `playback` or `branding`; read them from backend.
- Do not infer max values locally; read `system_constraints`.
- Do not infer which AI provider fields center admin can edit; read `page.editable.ai.providers`.

## Recommended Page Layout

### System Settings

- General
- Security
- Limits
- Overrides
- Infrastructure

Render from `meta.catalog_groups`.

### System Admin Center Settings

- Playback
- Devices
- Downloads
- Guest Access
- Video Access
- Branding
- Student Profile
- Features
- AI

Render from `sections.settings.groups`, `sections.features`, and `sections.ai`.

### Center Admin Settings

- Branding
- Student Profile
- Playback and access settings they own
- AI default model if editable
- Managed-by-platform notices from `summaries`

## AI-Specific Notes

- Provider availability, allowed models, and limits are platform policy.
- Center admin can only edit `default_model`, and only when backend marks it editable.
- AI content job creation screens should still load provider/model dropdowns from `GET /api/v1/admin/centers/{center}/ai/options`.
- Settings pages should load provider policy from grouped center settings payload.

## Error Handling

Common cases:

| Case | Status | Code |
|------|--------|------|
| unsupported setting key | `422` | `VALIDATION_ERROR` |
| center admin tries to update `features` | `422` | `VALIDATION_ERROR` |
| center admin tries to update provider limits or enablement | `422` | `VALIDATION_ERROR` |
| value exceeds system ceiling | `422` | `SYSTEM_LIMIT_EXCEEDED` |

## Preferred Frontend Contract

For future-safe rendering:

- base system settings page reads `GET /api/v1/admin/settings`
- both center settings pages read `GET /api/v1/admin/centers/{center}/settings`
- forms submit partial grouped payloads back to the same endpoints

If backend adds:
- a new system setting
- a new center setting
- a new feature flag
- a new AI provider
- a new AI model

frontend should pick it up from backend metadata instead of requiring a hard-coded release.
