---
name: najaah
description: Master Najaah LMS backend context. Use first for work in this repo to load architecture, domain rules, coding standards, commands, and file-map references.
---

# Najaah Master Skill

## Read This First
Load this skill before any specialist skill.

Then load only the next skill you need:
- `.claude/skills/najaah-orchestrator/SKILL.md`
- `.claude/skills/najaah-architecture/SKILL.md`
- `.claude/skills/najaah-features/SKILL.md`
- `.claude/skills/najaah-api/SKILL.md`
- `.claude/skills/najaah-quality/SKILL.md`

## Stable Invariants
- This backend is multi-tenant. Center scoping is default behavior, not an optional filter.
- Mobile student auth is JWT and device-bound. Admin auth is Sanctum/session based.
- Settings resolve from broader scope to narrower scope, with later overrides winning.
- Keep admin output human-readable and locale-aware.
- Preserve existing contracts unless the task explicitly approves a contract change.
- Once an orchestrated task has a plan, the orchestrator working memory becomes the live coordination source for later phases and handoffs.

## Read Next Only When Needed
- Project architecture and actors: `references/project-overview.md`
- Domain behavior and invariants: `references/domain-rules.md`
- Coding standards and response rules: `references/standards.md`
- Commands, file map, and related docs: `references/commands-and-paths.md`

## Default Workflow
1. Read this file.
2. Inspect similar implementation in the codebase.
3. Load the one specialist skill needed for the current phase.
4. If the task is coordinated by the orchestrator, read from and write to the working memory instead of re-deriving settled decisions.
5. Keep changes aligned with the domain rules and standards references.
6. Update the relevant reference if you introduce a stable new pattern.
