# Najaah LMS — Claude/Codex Alignment Map

## Goal

Claude agents, Claude skills, and Codex workflows must read the same repo-local markdown files so all AI agents follow one shared source of truth.

The source of truth is the repo-local `.claude/` skill and agent files plus the feature/workflow docs under `docs/`.

Codex should not maintain a second competing set of workflow rules when a `.claude` file already exists for that concern.

---

## Shared Source Of Truth

Use these files first:

1. `.claude/skills/najaah/SKILL.md`
2. `.claude/skills/najaah-orchestrator/SKILL.md`
3. specialist skills under `.claude/skills/`
4. agent definitions under `.claude/agents/`
5. feature plans under `docs/feature/`
6. progress trackers under `docs/feature/*-progress.md`
7. frontend contract docs under `docs/contracts/`

Codex-specific docs in `docs/codex/` should only:
- point Codex to the shared `.claude` files
- add Codex-specific invocation or git usage notes
- avoid duplicating domain or workflow rules that already live in `.claude`

Single-entry Codex start file:
- `docs/codex/CODEX_ORCHESTRATOR_ENTRY.md`

---

## Agent Mapping

| Claude Agent File | Purpose | Codex Equivalent Behavior | Files Codex Must Read |
|-------------------|---------|---------------------------|-----------------------|
| `.claude/agents/orchestrator.md` | planning, sequencing, approvals, cross-phase coordination | Codex planning mode for complex feature work | `.claude/agents/orchestrator.md`, `.claude/skills/najaah/SKILL.md`, `.claude/skills/najaah-orchestrator/SKILL.md` |
| `.claude/agents/feature-builder.md` | end-to-end phase execution | Codex implementation mode for one approved phase | `.claude/agents/feature-builder.md`, relevant specialist skills, current phase plan, progress tracker |
| `.claude/agents/reviewer.md` | review and quality gate | Codex review mode before commit/PR | `.claude/agents/reviewer.md`, `.claude/skills/najaah-quality/SKILL.md` |
| `.claude/agents/contract-generator.md` | frontend handoff contract generation | Codex docs/contracts mode after a backend phase | `.claude/agents/contract-generator.md`, `.claude/skills/najaah-api/SKILL.md`, `.claude/skills/najaah-frontend-handoff/SKILL.md` |
| `.claude/agents/cross-repo.md` | backend/frontend lane coordination | Codex cross-repo read-only coordination | `.claude/agents/cross-repo.md`, feature tracker, contract docs |

---

## Skill Mapping

| Claude Skill File | Purpose | Codex Must Use It When |
|-------------------|---------|------------------------|
| `.claude/skills/najaah/SKILL.md` | master repo context | always first in backend work |
| `.claude/skills/najaah-orchestrator/SKILL.md` | planning, phase gating, working memory | new features, multi-layer fixes, phased work |
| `.claude/skills/najaah-architecture/SKILL.md` | schema, migrations, indexes, relations | schema/model phases |
| `.claude/skills/najaah-features/SKILL.md` | services, authorization, workflows | business logic phases |
| `.claude/skills/najaah-api/SKILL.md` | routes, requests, controllers, resources, handoff answers | API or frontend-contract phases |
| `.claude/skills/najaah-quality/SKILL.md` | tests, lint, static analysis, PR checks | review or verification phases |
| `.claude/skills/najaah-frontend-handoff/SKILL.md` | contract generation and frontend-facing handoff | after backend phases that change contracts |

---

## Shared Phase Workflow With The User

This is the required workflow for Claude and Codex alike.

### 1. Plan With The User

- read the master skill and orchestrator skill
- inspect nearby code and repository state
- produce or update the feature plan
- wait for user approval before implementation

### 2. Use A Progress Tracker

- every cross-repo feature must have `docs/feature/{feature}-progress.md`
- phase status, blockers, next actions, and decisions live there
- frontend checks progress there instead of inferring from branches

### 3. Complete The Phase Review Gate

Before each phase:
- re-read the phase in the feature plan
- inspect the nearby implementation
- list affected files/modules
- record contract impact
- record phase-local adjustments
- define verification for the phase
- get explicit approval to implement that phase

Status convention:
- `pending`
- `reviewed`
- `in_progress`
- `blocked`
- `done`

### 4. Implement One Phase At A Time

- load only the skill(s) needed for that phase
- keep the phase scope tight
- do not mix later phases into the current branch

### 4A. Use Git Worktrees For Phase Branches

- keep the main checkout available for planning, tracker edits, and reviews
- create a dedicated git worktree for each implementation phase branch
- use separate backend and frontend worktrees when both repos are active in parallel
- do not treat worktree usage as Codex-only; Claude and Codex should follow the same pattern

### 5. Verify And Handoff

- run the phase-specific checks
- update the progress tracker
- generate/update contract docs if the phase changed frontend-facing behavior
- run review before commit/PR

---

## Docs Workflow Map

| Artifact | Purpose | Owner | Read By |
|---------|---------|-------|---------|
| `docs/feature/{feature}.md` | business and implementation plan | backend planning | Claude + Codex + frontend leads |
| `docs/feature/{feature}-progress.md` | lane status, phase status, blockers, review gates | backend orchestrator | Claude + Codex + frontend |
| `docs/contracts/{feature-slug}/*.md` | frontend-facing API contracts per phase | backend after phase work | Claude + Codex + frontend |
| `docs/workflow-guide.md` | operator-facing workflow guide | repo docs | humans + AI prompt authors |
| `docs/codex/CODEX_CLAUDE_ALIGNMENT.md` | Codex-to-Claude mapping | repo docs | Codex prompt/template users |

---

## Codex Rules

When Codex starts work in this repo, it must:

1. Treat `.claude` markdown files as the primary workflow/domain source of truth.
2. Use `docs/feature/` and `docs/contracts/` as the live execution artifacts.
3. Follow the same phase review gate and approval flow used by Claude.
4. Use the same git worktree phase-isolation workflow described in `docs/workflow-guide.md`.
5. Avoid creating duplicate instructions when a `.claude` or `docs/feature` file already exists.

---

## Practical Start Map

Default single-entry start for Codex:

- read `docs/codex/CODEX_ORCHESTRATOR_ENTRY.md`
- start from `.claude/agents/orchestrator.md`
- let orchestrator load the right skills and decide whether to stay in planning mode or switch roles

If the user asks Codex to:

- plan a feature:
  read `.claude/agents/orchestrator.md` and the two master skills first
- implement a phase:
  read `.claude/agents/feature-builder.md`, the current phase, and the progress tracker first
- review changes:
  read `.claude/agents/reviewer.md` and quality skill first
- generate a contract:
  read `.claude/agents/contract-generator.md` and the frontend handoff skill first
- check frontend/backend progress:
  read `.claude/agents/cross-repo.md`, the progress tracker, and contract docs first
