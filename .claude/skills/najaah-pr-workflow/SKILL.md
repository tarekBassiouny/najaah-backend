---
name: najaah-pr-workflow
description: Review Najaah LMS changes and prepare high-signal commits and pull requests. Use after implementation or when asked for a code review or PR workflow.
---

# Najaah PR Workflow Skill

## Use This Skill When
- reviewing a finished change set
- preparing a branch for commit or PR
- writing a structured review summary
- checking readiness before merge

## Prerequisite
Read `.claude/skills/najaah/SKILL.md` first, then inspect the actual diff.

## Default Workflow
1. Review changed files and group them by risk area.
2. Read the orchestrator working memory if the task was coordinated.
3. Check security, tenancy, authorization, contract compatibility, tests, and performance.
4. Validate that final diffs still match the recorded decisions and handoffs.
5. Run the relevant quality commands before calling the branch ready.
6. Summarize findings by severity before giving a merge recommendation.

## Expectations
- Findings come first in any review.
- Missing tests and tenant-scope regressions are high-signal issues.
- Do not create a PR summary that hides unresolved risks.
- Use concise commit and PR text that reflects the real scope of the change.

## Read Next Only When Needed
- Review checklist and PR notes: `references/pr-checklist.md`
