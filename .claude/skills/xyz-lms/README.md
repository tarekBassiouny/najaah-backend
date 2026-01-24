# XYZ LMS - AI Skills & Agents System

## Overview

This directory contains a comprehensive multi-agent system designed to assist with XYZ LMS development. The system consists of a master skill (knowledge base) and specialized agents that handle different aspects of development.

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    ORCHESTRATOR AGENT                            │
│              (Coordinates & Delegates Tasks)                     │
└───────────────────────┬─────────────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┬───────────────┐
        │               │               │               │
        ▼               ▼               ▼               ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│Architecture  │ │   Feature    │ │Code Quality  │ │     API      │
│    Agent     │ │    Agent     │ │    Agent     │ │    Agent     │
├──────────────┤ ├──────────────┤ ├──────────────┤ ├──────────────┤
│- Schema      │ │- Services    │ │- Testing     │ │- Endpoints   │
│- Migrations  │ │- Bus. Logic  │ │- PHPStan     │ │- Validation  │
│- Indexes     │ │- Auth        │ │- Pint        │ │- Resources   │
│- Caching     │ │- Workflows   │ │- Coverage    │ │- Docs        │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
                        │
                        ▼
              ┌──────────────────┐
              │   MASTER SKILL   │
              │(Knowledge Base)  │
              └──────────────────┘
```

## Skills Directory Structure

```
/mnt/skills/user/
├── xyz-lms/                    # Master skill (comprehensive knowledge)
│   └── SKILL.md
├── xyz-lms-architecture/       # Database, schema, caching
│   └── SKILL.md
├── xyz-lms-features/           # Business logic, domain rules
│   └── SKILL.md
├── xyz-lms-quality/            # Testing, code quality
│   └── SKILL.md
├── xyz-lms-api/                # API design, endpoints
│   └── SKILL.md
└── xyz-lms-orchestrator/       # Master coordinator
    └── SKILL.md
```

## Quick Start

### For Claude (Conversational AI)

**Starting any development task:**
```
1. Read the Orchestrator skill: /mnt/skills/user/xyz-lms-orchestrator/SKILL.md
2. Orchestrator will delegate to appropriate specialists
3. Each specialist reads their skill before working
```

**Example conversation:**
```
User: "Add ability to bookmark videos"

Claude: [Reads orchestrator skill]
I'll coordinate this feature across multiple agents:

1. Architecture Agent will create the database schema
2. Feature Agent will implement the business logic
3. API Agent will create the endpoints
4. Quality Agent will write comprehensive tests

Let me break this down...
```

### For Claude Code (CLI Agent)

**In your terminal:**
```bash
# Start a new feature
claude-code "Read /mnt/skills/user/xyz-lms-orchestrator/SKILL.md and help me implement video bookmarks feature"

# Work on specific layer
claude-code "Read /mnt/skills/user/xyz-lms-architecture/SKILL.md and create migration for bookmarks table"

# Run quality checks
claude-code "Read /mnt/skills/user/xyz-lms-quality/SKILL.md and write tests for the BookmarkService"
```

## Skills Reference

### 1. Master Skill (`xyz-lms`)
**Purpose:** Complete project knowledge base

**Contains:**
- Project overview & tech stack
- System architecture
- Core domain models
- Business rules
- Service patterns
- Database standards
- API standards
- File paths reference
- Common commands

**When to Read:**
- Before starting any task
- When you need project context
- When unsure about conventions
- When looking for examples

### 2. Architecture Agent (`xyz-lms-architecture`)
**Purpose:** Database design and system architecture

**Contains:**
- Migration standards
- Schema design patterns
- Multi-tenancy rules
- Indexing strategies
- Relationship patterns
- Query optimization
- Caching patterns

**When to Read:**
- Creating/modifying database tables
- Adding foreign keys or indexes
- Designing data relationships
- Optimizing queries
- Planning cache strategy

### 3. Feature Agent (`xyz-lms-features`)
**Purpose:** Business logic and domain rules

**Contains:**
- Service layer patterns
- Authorization patterns
- Business rules enforcement
- Workflow implementation
- State machine patterns
- Settings hierarchy
- Domain events

**When to Read:**
- Implementing business logic
- Writing service methods
- Creating authorization checks
- Building workflows
- Enforcing domain rules

### 4. Code Quality Agent (`xyz-lms-quality`)
**Purpose:** Testing and code quality

**Contains:**
- Testing standards (Pest)
- Coverage requirements
- PHPStan configuration
- Laravel Pint standards
- Factory patterns
- Quality check commands
- CI/CD guidelines

**When to Read:**
- Writing tests
- Ensuring code quality
- Creating factories
- Running quality checks
- Setting up CI/CD

### 5. API Agent (`xyz-lms-api`)
**Purpose:** API design and implementation

**Contains:**
- RESTful conventions
- Controller patterns
- FormRequest validation
- API resource formatting
- Response standards
- Scribe documentation
- Rate limiting

**When to Read:**
- Creating API endpoints
- Writing controllers
- Implementing validation
- Formatting responses
- Documenting APIs

### 6. Orchestrator Agent (`xyz-lms-orchestrator`)
**Purpose:** Master coordinator

**Contains:**
- Workflow orchestration
- Task delegation patterns
- Quality gates
- Decision framework
- Status reporting
- Integration procedures

**When to Read:**
- Starting any new feature
- Breaking down complex tasks
- Coordinating multiple agents
- Ensuring quality compliance

## Usage Patterns

### Pattern 1: New Feature Development

```
Step 1: Talk to Orchestrator
"Help me implement [feature]"

Step 2: Orchestrator Analyzes
- Reads master skill for context
- Identifies affected components
- Creates task breakdown
- Delegates to specialists

Step 3: Specialists Execute
- Architecture: Schema & migrations
- Feature: Services & business logic
- API: Endpoints & validation
- Quality: Tests & verification

Step 4: Orchestrator Verifies
- All quality gates passed
- Documentation updated
- Integration verified
```

### Pattern 2: Bug Fix

```
Step 1: Report to Orchestrator
"Fix: [describe bug]"

Step 2: Orchestrator Investigates
- Reviews relevant docs
- Identifies root cause
- Plans fix approach

Step 3: Specialist Fixes
- Feature Agent fixes logic
- Quality Agent adds regression test

Step 4: Verification
- Tests pass
- No regressions
```

### Pattern 3: Code Review

```
Step 1: Submit to Quality Agent
"Review this code for quality"

Step 2: Quality Checks
- PHPStan compliance
- Pint formatting
- Test coverage
- Convention adherence

Step 3: Report
- Issues found
- Suggestions
- Approval status
```

## Integration with Your Workflow

### With GitHub Issues

The Orchestrator can break down issues into agent tasks:

```markdown
Issue #123: Add Video Bookmarks

Agent Assignments:
- [ ] Architecture: Create bookmarks table
- [ ] Feature: Implement BookmarkService
- [ ] API: Create 3 endpoints
- [ ] Quality: Write 15+ tests

Status: In Progress
Progress: 2/4 agents complete
```

### With Claude Code

```bash
# Let Orchestrator plan the work
claude-code "Read orchestrator skill and plan implementation of bookmarks"

# Execute phases
claude-code "Execute phase 1: Architecture tasks from plan"
claude-code "Execute phase 2: Feature tasks from plan"
claude-code "Execute phase 3: API tasks from plan"
claude-code "Execute phase 4: Quality tasks from plan"
```

### With CI/CD

The Quality Agent's standards can be enforced in your pipeline:

```yaml
# .github/workflows/quality.yml
- name: PHPStan
  run: ./vendor/bin/phpstan analyse
  
- name: Pint
  run: ./vendor/bin/pint --test
  
- name: Tests
  run: ./vendor/bin/pest --coverage --min=90
```

## Maintenance

### Updating Skills

When you discover new patterns or conventions:

1. Identify which skill should contain it
2. Update the skill file
3. Update this README if structure changes
4. Commit changes to repository

### Adding New Skills

To add a new specialist:

1. Create `/mnt/skills/user/xyz-lms-[name]/SKILL.md`
2. Define its responsibilities
3. Update Orchestrator to delegate to it
4. Update this README

### Skill Version Control

All skills should be in your git repository:

```bash
# In your project root
git add mnt/skills/user/xyz-lms*
git commit -m "Update agent skills"
git push
```

## Troubleshooting

### "Agent doesn't have context"

**Solution:** Make sure the agent reads its skill file first:
```
claude-code "First read /mnt/skills/user/xyz-lms-architecture/SKILL.md, then create migration for..."
```

### "Conflicting guidance from different agents"

**Solution:** Orchestrator resolves conflicts. Always start with Orchestrator for complex tasks.

### "Skill information outdated"

**Solution:** Update the skill file with current information, commit to repo.

## Best Practices

1. **Always Start with Orchestrator** for new features
2. **Read Master Skill First** for any development task
3. **One Agent at a Time** - don't try to do multiple agent tasks in one go
4. **Verify Quality Gates** before considering work complete
5. **Update Skills** when you discover new patterns
6. **Document Deviations** from standards when necessary
7. **Trust the Process** - the agents are designed to work together

## Examples

### Example 1: Complete Feature

```
User: "Add video notes feature"

Orchestrator Response:
"I'll coordinate this feature:

Phase 1 - Architecture Agent:
✓ Create video_notes table
✓ Add relationships
✓ Define indexes

Phase 2 - Feature Agent:
✓ Implement VideoNoteService
✓ Add authorization
✓ Enforce business rules

Phase 3 - API Agent:
✓ Create CRUD endpoints
✓ Add validation
✓ Document with Scribe

Phase 4 - Quality Agent:
✓ Write 27 tests
✓ Verify 94% coverage
✓ Pass all quality checks

Feature complete! ✓"
```

### Example 2: Quick Fix

```
User: "Fix session timeout bug"

Orchestrator Response:
"Bug analysis:
- Issue in PlaybackService::updateProgress()
- Missing expires_at extension

Fix plan:
1. Feature Agent: Update progress method
2. Quality Agent: Add regression test

Executing... Done! ✓"
```

## Support

For questions or issues with the skills system:

1. Check this README
2. Review relevant skill file
3. Check `/docs/` for feature documentation
4. Refer to master skill for project context

## Version History

- v1.0 (2025-01-23): Initial skills system created
  - Master skill with comprehensive knowledge
  - 4 specialized agents (Architecture, Feature, Quality, API)
  - Orchestrator for coordination

---

**Remember:** This system is designed to help you build high-quality code faster. Trust the process, follow the workflow, and maintain the skills as your project evolves.
