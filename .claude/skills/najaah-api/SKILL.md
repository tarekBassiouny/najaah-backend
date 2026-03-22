---
name: najaah-api
description: Build and document Najaah LMS API routes, requests, controllers, resources, and frontend handoff contracts. Use for endpoint work and backend-to-frontend answers.
---

# Najaah API Skill

## Use This Skill When
- creating or updating routes
- writing FormRequests
- implementing controllers
- shaping API resources
- generating Scribe docs
- answering frontend questions with exact backend contracts

## Context
Multi-tenant Laravel 11 backend (center_id scoping). JWT+device for mobile students, Sanctum for admin. Layered: Controller → Action → Service → Model. For full context, read `.claude/skills/najaah/SKILL.md`.

## Default Workflow
1. Inspect existing routes, controller, request, resource, and tests for the nearest similar endpoint.
2. Read the orchestrator working memory if the task is coordinated.
3. Keep business logic in services and limit controllers to orchestration.
4. Preserve scope separation between system routes and center routes.
5. Freeze request and response contracts before quality work fans out broadly.
6. Add or update tests and docs for any contract change.
7. Publish a handoff memo whenever the API surface, docs, or frontend contract changes.

## Mandatory Policies
- Keep controllers thin.
- Use FormRequests for validation.
- Use resources for complex responses.
- For `app/Http/Resources/Admin/**`, prefer human-readable labels and related display fields over raw IDs and enum internals.
- Use locale-aware translated fields for user-facing output.
- Do not invent unsupported backend capabilities when answering frontend questions.
- If another lane depends on response fields, list the exact fields and scope rules in working memory instead of leaving them implied.

## Read Next Only When Needed
- General API patterns: `references/api-patterns.md`
- Admin/frontend endpoint map and handoff format: `references/admin-frontend-handoff.md`
