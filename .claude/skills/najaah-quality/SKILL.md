---
name: najaah-quality
description: Test and verify Najaah LMS changes with Pest, factories, Pint, PHPStan, and review workflows. Use for regression coverage and quality gates.
---

# Najaah Quality Skill

## Use This Skill When
- writing unit, feature, or integration tests
- adding or updating factories
- running Pint or PHPStan
- reviewing changed code for regressions and gaps

## Prerequisite
Read `.claude/skills/najaah/SKILL.md` first.

## Default Workflow
1. Start with a regression test for the changed behavior.
2. Read the orchestrator working memory if the task is coordinated.
3. Cover both service logic and API surface when the change crosses layers.
4. Update factories and fixtures when schema or defaults changed.
5. Run the smallest relevant test slice first, then broader checks as needed.
6. Record the verification ledger, remaining gaps, and flaky areas in working memory.
7. Report what was verified and what remains unverified.

## Expectations
- Use Pest conventions already present in the repo.
- Keep project-wide coverage at 90% or better when running coverage gates.
- Aim for full coverage on critical auth, playback, session, and view-limit behavior.
- Prefer narrow, high-signal tests over brittle end-to-end duplication.
- If a contract or workflow is not stable yet, ask for a fresh handoff memo before expanding the test matrix.

## Read Next Only When Needed
- Testing patterns and commands: `references/testing-patterns.md`
