---
name: najaah-features
description: Implement Najaah LMS business logic, authorization services, workflows, and domain rules. Use for service-layer work and behavior changes.
---

# Najaah Features Skill

## Use This Skill When
- implementing or changing business logic
- adding or refactoring services
- enforcing authorization and workflow rules
- handling domain exceptions and state transitions

## Context
Multi-tenant Laravel 11 backend (center_id scoping). JWT+device for mobile students, Sanctum for admin. Layered: Controller → Action → Service → Model. For full context, read `.claude/skills/najaah/SKILL.md`.

## Default Workflow
1. Inspect the nearest existing service and its tests.
2. Read the orchestrator working memory if the task is coordinated.
3. Keep controllers, requests, and resources free of business logic.
4. Put authorization decisions in dedicated service logic.
5. Preserve multi-tenancy, settings resolution, and existing error shapes.
6. For new or changed features, classify whether the behavior needs a system setting, a center setting, a per-center feature flag, or only an internal operational default.
7. Load `references/settings-classification.md` when feature availability or admin-managed configuration is involved.
8. Record workflow decisions, authorization assumptions, and downstream needs in working memory.
9. Add or update tests for the changed behavior.

## Mandatory Rules
- Follow PHP and service rules from `.claude/skills/najaah/references/standards.md`.
- Throw stable domain failures from the service layer instead of leaking transport concerns.
- Keep cross-center access impossible unless the feature is explicitly system-scoped.
- Split services when one class starts owning unrelated workflows.
- Do not hide a new admin-configurable behavior behind a hard-coded value; add it to the settings registry or document why it must stay operational.
- If API or quality work depends on a new workflow rule, publish a handoff memo with the exact rule and expected surface impact.

## Read Next Only When Needed
- Service and authorization patterns: `references/service-patterns.md`
- Settings classification and registry workflow: `references/settings-classification.md`
- Settings governance audit and categorization: `references/settings-governance.md`
