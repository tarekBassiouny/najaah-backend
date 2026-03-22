# PR Workflow

## When to Use
- Reviewing a finished change set
- Preparing a branch for commit or PR
- Writing a structured review summary
- Checking readiness before merge

## Review Pass
1. Review changed files and group them by risk area.
2. Read the orchestrator working memory if the task was coordinated.
3. Check security, tenancy, authorization, contract compatibility, tests, and performance.
4. Validate that final diffs still match the recorded decisions and handoffs.
5. Run the relevant quality commands before calling the branch ready.
6. Summarize findings by severity before giving a merge recommendation.

## Checklist
- changed files understood
- authorization and tenancy preserved
- API contract changes called out
- admin resources remain readable and locale-aware
- tests cover the changed behavior
- obvious N+1 or missing eager loads checked

## Validation Commands
```bash
./vendor/bin/sail composer quality
./vendor/bin/sail test
./vendor/bin/sail pint --test
./vendor/bin/sail composer phpstan
```

## Review Output
- findings ordered by severity
- open questions or assumptions
- short change summary
- recommendation: ready or needs follow-up

## Rules
- Findings come first in any review.
- Missing tests and tenant-scope regressions are high-signal issues.
- Do not create a PR summary that hides unresolved risks.
- Use concise commit and PR text that reflects the real scope of the change.
