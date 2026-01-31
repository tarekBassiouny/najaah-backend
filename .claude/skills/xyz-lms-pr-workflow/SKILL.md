# XYZ LMS - PR Review & Creation Workflow

## Purpose
Standardized workflow for reviewing code changes and creating pull requests with comprehensive quality assessments.

## When to Use This Workflow
- Before merging any feature branch
- After completing a feature implementation
- When requested to review PR changes
- Before creating a pull request

## Prerequisites
Always run quality checks before starting:
```bash
./vendor/bin/sail composer quality
```

---

## Complete PR Workflow

### Phase 1: Code Review

#### Step 1: Gather Changed Files
```bash
git status --short
git diff HEAD --name-only
```

#### Step 2: Review Each Category

**For each file, assess:**

1. **Security**
   - Authorization middleware applied?
   - Input validation present?
   - Center/tenant scoping enforced?
   - No secrets or sensitive data exposed?

2. **Logic**
   - Follows Controller → Service → Model pattern?
   - Error handling appropriate?
   - Edge cases covered?
   - Business rules correctly implemented?

3. **Style**
   - `declare(strict_types=1);` present?
   - Proper type hints on all parameters/returns?
   - PHPDoc annotations for complex types?
   - Follows naming conventions?

4. **Performance**
   - N+1 queries prevented?
   - Caching implemented where appropriate?
   - Queries optimized with proper indexes?

#### Step 3: Run Quality Checks
```bash
# Full quality suite
./vendor/bin/sail composer quality

# Or individually:
./vendor/bin/sail composer lint      # Pint + PHPStan
./vendor/bin/sail test               # All tests
```

#### Step 4: Document Issues Found

Categorize issues by priority:
- **High**: Security vulnerabilities, broken functionality, missing interfaces
- **Medium**: Style violations, missing tests, duplicate code
- **Low**: Minor optimizations, documentation gaps

---

### Phase 2: Fix Issues (if any)

1. Create missing interfaces in `app/Services/{Domain}/Contracts/`
2. Fix security issues immediately
3. Address style violations (or run `./vendor/bin/sail pint`)
4. Add missing tests

---

### Phase 3: Create Commit

```bash
# Stage all changes
git add -A

# Create commit with descriptive message
git commit -m "$(cat <<'EOF'
feat: [brief description]

- [bullet point of major change 1]
- [bullet point of major change 2]
- [bullet point of major change 3]

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>
EOF
)"
```

**Commit Message Prefixes:**
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code restructuring
- `docs:` - Documentation only
- `test:` - Adding tests
- `chore:` - Maintenance tasks

---

### Phase 4: Create Pull Request

```bash
# Push to remote
git push origin [branch-name]

# Create PR with full review
gh pr create --base dev --title "[type]: [Brief Title]" --body "$(cat <<'EOF'
## Summary
<!-- Brief description of what this PR implements -->

---

## Quality Checks

| Check | Status |
|-------|--------|
| **Pint (Style)** | ✅ PASS - X files |
| **PHPStan (Level 7)** | ✅ No errors |
| **Tests** | ✅ X passed (Y assertions) |

---

## Changes Overview

### Modified Files
| File | Change |
|------|--------|
| `path/to/file.php` | Brief description |

### New Files
- List new files or directories

---

## Code Review

### Security
| Aspect | Status | Details |
|--------|--------|---------|
| Authorization | ✅ | Details here |
| Input Validation | ✅ | Details here |
| Data Scoping | ✅ | Details here |

### Logic
| Aspect | Status | Details |
|--------|--------|---------|
| Architecture | ✅ | Details here |
| Error Handling | ✅ | Details here |
| Edge Cases | ✅ | Details here |

### Style
| Aspect | Status | Details |
|--------|--------|---------|
| Type Safety | ✅ | Details here |
| Naming | ✅ | Details here |
| Documentation | ✅ | Details here |

### Performance
| Aspect | Status | Details |
|--------|--------|---------|
| Query Efficiency | ✅ | Details here |
| Caching | ✅ | Details here |
| N+1 Prevention | ✅ | Details here |

---

## Test Coverage

| Test Suite | Tests | Assertions |
|------------|-------|------------|
| TestName | X | Y |
| **Total** | **X** | **Y** |

---

## Issues Found & Resolved

### High Priority
- ✅ Issue description and resolution

### Medium Priority
- ✅ Issue description and resolution

---

## Verdict

### ✅ APPROVED

All quality checks pass. Ready to merge.

---

🤖 Generated with [Claude Code](https://claude.ai/code)
EOF
)"
```

---

## PR Review Template Reference

The PR template is located at:
`.github/PULL_REQUEST_TEMPLATE/feature_with_review.md`

---

## Review Status Icons

Use these consistently:
- ✅ - Pass / Approved / Complete
- ⚠️ - Warning / Needs attention
- ❌ - Fail / Blocked / Critical issue

---

## Quality Gates (Must Pass)

Before creating PR:
- [ ] `./vendor/bin/sail composer lint` passes
- [ ] `./vendor/bin/sail test` passes
- [ ] All services have interfaces
- [ ] No security vulnerabilities
- [ ] Center scoping enforced (for multi-tenant data)

---

## Common Review Checklist

### Services
- [ ] Has interface in `Contracts/` directory
- [ ] Implements the interface
- [ ] Uses constructor injection
- [ ] Has strict return types

### Controllers
- [ ] Thin - delegates to services
- [ ] Uses Form Requests for validation
- [ ] Returns API Resources
- [ ] Has proper authorization middleware

### Tests
- [ ] Feature tests for endpoints
- [ ] Unit tests for services
- [ ] Edge cases covered
- [ ] Follows Pest conventions

### Database
- [ ] Migrations have proper indexes
- [ ] Foreign keys with cascade rules
- [ ] Soft deletes where appropriate

---

## Example: Complete PR Review Output

```markdown
# PR Review: Feature Name

## Quality Checks

| Check | Status |
|-------|--------|
| **Pint (Style)** | ✅ PASS - 663 files |
| **PHPStan (Level 7)** | ✅ No errors |
| **Tests** | ✅ 470 passed (1549 assertions) |

## Security ✅
- Authorization: `require.permission:X` middleware applied
- Input Validation: FormRequest validates all inputs
- Data Scoping: CenterScopeService enforces tenant isolation

## Logic ✅
- Architecture: Clean Controller → Service → Resource pattern
- Error Handling: DomainException with proper error codes
- Edge Cases: Handles empty data, divide-by-zero protection

## Style ✅
- Type Safety: `declare(strict_types=1)` on all files
- Naming: Follows project conventions
- Documentation: PHPDoc with type annotations

## Performance ✅
- Caching: Configurable TTL implemented
- Queries: Uses selectRaw with groupBy for aggregations
- N+1: Bulk loads related data

## Verdict: ✅ APPROVED
```

---

## Related Skills
- Quality Agent: `.claude/skills/xyz-lms-quality/SKILL.md`
- Orchestrator: `.claude/agents/orchestrator.md`
