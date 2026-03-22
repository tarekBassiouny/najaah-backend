---
name: najaah-architecture
description: Design Najaah LMS schema, migrations, relationships, indexes, and scoped query changes. Use for database and architectural updates.
---

# Najaah Architecture Skill

## Use This Skill When
- creating or changing database tables
- adding relationships, indexes, or constraints
- changing query shape or tenant scoping
- planning schema support for a feature

## Context
Multi-tenant Laravel 11 backend (center_id scoping). JWT+device for mobile students, Sanctum for admin. Layered: Controller → Action → Service → Model. For full context, read `.claude/skills/najaah/SKILL.md`.

## Default Workflow
1. Inspect the current schema, model, factories, and related queries.
2. Read the orchestrator working memory if the task is coordinated.
3. Claim ownership of schema-touching files before editing.
4. Design the smallest compatible schema change that supports the feature.
5. Add indexes for real query paths, not by habit alone.
6. Update model casts and relationships in the same pass.
7. Write schema decisions, compatibility notes, and blockers back into working memory.

## Mandatory Rules
- Entity tables should use `id`, `timestamps()`, and `softDeletes()` unless there is a clear exception.
- Center-scoped entities should carry `center_id` with proper foreign keys.
- Status fields should remain integer-based with constants in code.
- Scope-sensitive queries must stay center-aware.
- Add indexes for foreign keys, status filters, soft deletes, and common composite lookups.
- If schema choices affect services, API resources, or tests, publish a handoff memo before those lanes proceed.

## Read Next Only When Needed
- Schema, relationship, and index patterns: `references/schema-patterns.md`
