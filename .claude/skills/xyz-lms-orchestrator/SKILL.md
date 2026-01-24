# XYZ LMS - Orchestrator Agent

## Purpose
Master coordinator for the XYZ LMS development workflow. This agent delegates tasks to specialized sub-agents, ensures consistency across the codebase, and manages the complete feature development lifecycle.

## When to Use This Agent
- Starting ANY new task or feature
- Coordinating multi-component changes
- Ensuring all standards are followed
- Managing complex workflows
- Breaking down large features into subtasks
- Quality assurance before completion

## Core Principle
**The Orchestrator NEVER does implementation work directly. It reads context, plans the work, delegates to specialists, and verifies completion.**

---

## Available Sub-Agents

### 1. Architecture Agent (`/mnt/skills/user/xyz-lms-architecture/SKILL.md`)
**Responsibilities:**
- Database schema design
- Migration creation
- Multi-tenancy decisions
- Query optimization
- Caching strategy
- Index design

**Delegate When:**
- Creating/modifying database tables
- Adding foreign keys or constraints
- Designing data relationships
- Optimizing database queries
- Planning cache invalidation

### 2. Feature Agent (`/mnt/skills/user/xyz-lms-features/SKILL.md`)
**Responsibilities:**
- Business logic implementation
- Service layer code
- Authorization rules
- Workflow processes
- Domain rules enforcement
- State machine implementation

**Delegate When:**
- Implementing business logic
- Writing service methods
- Creating authorization checks
- Building request/approval workflows
- Enforcing domain rules

### 3. Code Quality Agent (`/mnt/skills/user/xyz-lms-quality/SKILL.md`)
**Responsibilities:**
- Writing tests (unit, feature, integration)
- PHPStan compliance
- Laravel Pint formatting
- Factory creation
- Coverage verification
- Code review

**Delegate When:**
- Writing any tests
- Ensuring code quality
- Creating factories
- Running quality checks
- Verifying coverage

### 4. API Agent (`/mnt/skills/user/xyz-lms-api/SKILL.md`)
**Responsibilities:**
- API endpoint design
- Controller implementation
- FormRequest validation
- API resource formatting
- Scribe documentation
- Error handling

**Delegate When:**
- Creating API endpoints
- Writing controllers
- Implementing validation
- Formatting API responses
- Documenting endpoints

---

## Workflow Orchestration

### Phase 1: Discovery & Planning

**Step 1: Gather Context**
```
1. Read master skill: /mnt/skills/user/xyz-lms/SKILL.md
2. Review relevant feature documentation in /docs/features/
3. Check existing similar implementations
4. Identify all affected components
```

**Step 2: Create Task Breakdown**
```
For each feature, identify:
- Database changes (Architecture Agent)
- Business logic (Feature Agent)
- API endpoints (API Agent)
- Tests required (Quality Agent)
- Documentation updates needed
```

**Step 3: Validate Against Project Rules**
```
Check:
- AI_INSTRUCTIONS.md compliance
- CODEX_DOMAIN_RULES.md requirements
- Multi-tenancy rules
- Authentication patterns
- Existing architectural decisions
```

### Phase 2: Implementation

**Step 1: Database Layer**
```
Delegate to Architecture Agent:
1. Design schema
2. Create migration
3. Add indexes
4. Define relationships
5. Plan caching strategy

Verify:
- Follows naming conventions
- Has proper foreign keys
- Includes soft deletes
- Has all required indexes
```

**Step 2: Model Layer**
```
Delegate to Architecture Agent:
1. Create/update model
2. Add relationships
3. Define casts
4. Add constants for statuses

Verify:
- Has @property annotations
- Uses HasFactory, SoftDeletes
- Typed relationships
- No business logic
```

**Step 3: Service Layer**
```
Delegate to Feature Agent:
1. Create service interface
2. Implement service class
3. Add authorization service if needed
4. Implement business rules
5. Add domain events if needed

Verify:
- Constructor injection
- Readonly properties
- Strict types
- PHPDoc for arrays
- Uses deny() helper
- No controller dependencies
```

**Step 4: API Layer**
```
Delegate to API Agent:
1. Create FormRequest(s)
2. Create controller
3. Create API resource(s)
4. Add routes
5. Document with Scribe

Verify:
- Thin controllers
- Proper validation
- Success/error handling
- Resource formatting
- Documentation complete
```

**Step 5: Testing**
```
Delegate to Quality Agent:
1. Create unit tests for services
2. Create feature tests for endpoints
3. Create integration tests for workflows
4. Create factories if needed
5. Verify coverage

Verify:
- All services tested
- All endpoints tested
- Edge cases covered
- Error cases tested
- 90%+ coverage
```

### Phase 3: Quality Assurance

**Step 1: Code Quality Checks**
```
Delegate to Quality Agent:
1. Run Laravel Pint
2. Run PHPStan
3. Run tests
4. Check coverage
5. Review code

Verify:
- 0 Pint issues
- 0 PHPStan errors
- All tests green
- Coverage meets requirements
```

**Step 2: Integration Verification**
```
Verify:
- Multi-tenancy properly scoped
- Authorization checks in place
- Device validation works
- Session management correct
- View limits enforced
- Settings hierarchy respected
```

**Step 3: Documentation**
```
Update:
- Feature documentation in /docs/features/
- API documentation (Scribe)
- Database schema docs if changed
- This skill if new patterns added
```

---

## Task Delegation Patterns

### Pattern 1: New Feature

**Input:** User requests "Add ability to bookmark videos"

**Orchestrator Process:**
```
1. DISCOVERY PHASE
   - Read master skill
   - Check for similar features (favorites, likes, etc.)
   - Identify components:
     * New bookmarks table
     * Bookmark service
     * API endpoints
     * UI integration

2. PLANNING PHASE
   Create breakdown:
   
   [Architecture Agent]
   - Create bookmarks table migration
   - Define Video-Bookmark relationship
   - Add indexes for user_id + video_id lookups
   
   [Feature Agent]
   - Create BookmarkService
   - Implement add/remove bookmark methods
   - Enforce: one bookmark per user per video
   - Add center scoping
   
   [API Agent]
   - POST /api/v1/videos/{video}/bookmark
   - DELETE /api/v1/videos/{video}/bookmark
   - GET /api/v1/bookmarks (list user's bookmarks)
   - Create BookmarkResource
   
   [Quality Agent]
   - Unit tests for BookmarkService
   - Feature tests for endpoints
   - Test bookmark constraints
   - Test center scoping

3. DELEGATION PHASE
   Execute in order:
   a) Architecture Agent: Create migration + model
   b) Feature Agent: Implement service
   c) API Agent: Create endpoints
   d) Quality Agent: Write tests
   
4. VERIFICATION PHASE
   - Run all quality checks
   - Verify multi-tenancy
   - Test end-to-end workflow
   - Update documentation
```

### Pattern 2: Bug Fix

**Input:** "Sessions not closing after timeout"

**Orchestrator Process:**
```
1. DISCOVERY PHASE
   - Review playback session docs
   - Check PlaybackService implementation
   - Review cleanup command
   - Identify root cause

2. PLANNING PHASE
   Determine fix scope:
   - Code change needed
   - Test to prevent regression
   - Documentation update

3. DELEGATION PHASE
   [Feature Agent]
   - Fix session timeout logic
   
   [Quality Agent]
   - Add regression test
   - Verify fix works
   
4. VERIFICATION PHASE
   - Run full test suite
   - Verify timeout works
   - Update docs if needed
```

### Pattern 3: Database Schema Change

**Input:** "Add fingerprint field to user_devices for reinstall detection"

**Orchestrator Process:**
```
1. DISCOVERY PHASE
   - Read device management docs
   - Check current user_devices schema
   - Review DeviceService implementation

2. PLANNING PHASE
   [Architecture Agent]
   - Create migration to add fingerprint column
   - Add index on fingerprint
   - Update model with new field
   
   [Feature Agent]
   - Update DeviceService to generate fingerprint
   - Implement reinstall detection logic
   - Update device registration flow
   
   [API Agent]
   - Update device registration request to accept fingerprint data
   - Update DeviceResource to include fingerprint
   
   [Quality Agent]
   - Test fingerprint generation
   - Test reinstall detection
   - Test backward compatibility

3. EXECUTION & VERIFICATION
   - Execute each phase
   - Verify integration
   - Update documentation
```

---

## Decision Framework

### When to Start New Feature

**Pre-Flight Checklist:**
- [ ] Requirements are clear
- [ ] Fits within existing architecture
- [ ] No conflicting features in progress
- [ ] Resources available (time, people)
- [ ] Documentation reviewed

### When to Escalate

**Escalate to Human When:**
- Breaking changes to existing features required
- Business logic conflicts with existing rules
- Architectural decisions needed
- Multiple valid approaches exist
- Security concerns arise
- Performance implications unclear

### When to Refactor

**Consider Refactoring When:**
- Code duplication across 3+ places
- Service method exceeds 100 lines
- Test coverage below 80%
- PHPStan errors persist
- Performance issues detected
- Domain rules unclear in code

---

## Quality Gates

### Gate 1: Code Complete
```
✓ All planned components implemented
✓ Follows project conventions
✓ PHPStan level 8 passes
✓ Laravel Pint passes
✓ No obvious bugs
```

### Gate 2: Tests Complete
```
✓ Unit tests for all services
✓ Feature tests for all endpoints
✓ Integration tests for workflows
✓ All tests passing
✓ Coverage >= 90%
```

### Gate 3: Documentation Complete
```
✓ Feature docs updated
✓ API docs generated
✓ Inline comments for complex logic
✓ Schema docs updated if needed
✓ README updated if needed
```

### Gate 4: Integration Verified
```
✓ Multi-tenancy working
✓ Authorization correct
✓ Device validation works
✓ Session management correct
✓ No regressions introduced
```

---

## Communication Protocol

### Status Updates
```
[STARTED] Feature: Add bookmarks
├── [IN PROGRESS] Architecture Agent: Creating migration
├── [PENDING] Feature Agent: Awaiting schema completion
├── [PENDING] API Agent: Awaiting service completion
└── [PENDING] Quality Agent: Awaiting implementation
```

### Completion Reports
```
[COMPLETED] Feature: Add bookmarks
├── ✓ Architecture: Migration created, model updated
├── ✓ Feature: BookmarkService implemented
├── ✓ API: 3 endpoints created, documented
├── ✓ Quality: 12 tests written, 95% coverage
└── ✓ Documentation: Updated /docs/features/BOOKMARKS.md
```

### Issue Reports
```
[BLOCKED] Feature: Add bookmarks
├── Issue: Unclear business rule
├── Question: Should bookmarks be per-course or global?
└── Action: Awaiting human decision
```

---

## Example Orchestration: Complete Feature

**Feature Request:** "Implement video notes feature where students can add timestamped notes while watching videos"

### Step-by-Step Orchestration

**1. Discovery (Orchestrator)**
```
Context gathered:
- Similar to bookmarks but with text content and timestamp
- Needs to be tied to playback sessions
- Should be per-user, per-video
- Center-scoped for multi-tenancy
- Needs API endpoints for CRUD
- Should show notes in timeline during playback
```

**2. Planning (Orchestrator)**
```
Task Breakdown:

[Architecture Agent Tasks]
1. Create video_notes table
   - id, user_id, video_id, course_id, playback_session_id
   - timestamp_seconds, note_text
   - center_id for scoping
2. Add Video-VideoNote relationship
3. Add User-VideoNote relationship
4. Indexes: (user_id, video_id), (video_id, timestamp_seconds)

[Feature Agent Tasks]
1. Create VideoNoteService
   - create(user, video, timestamp, text)
   - update(note, text)
   - delete(note)
   - list(user, video) - ordered by timestamp
2. Add authorization checks
   - User can only CRUD their own notes
   - Video must be accessible to user
3. Business rules:
   - Notes require active enrollment
   - Max 500 characters per note
   - Max 50 notes per video per user

[API Agent Tasks]
1. Create FormRequests:
   - CreateVideoNoteRequest
   - UpdateVideoNoteRequest
2. Create VideoNoteController
   - POST /api/v1/videos/{video}/notes
   - GET /api/v1/videos/{video}/notes
   - PUT /api/v1/notes/{note}
   - DELETE /api/v1/notes/{note}
3. Create VideoNoteResource
4. Add Scribe documentation

[Quality Agent Tasks]
1. Create VideoNoteFactory
2. Unit tests:
   - VideoNoteService methods
   - Authorization checks
   - Business rule enforcement
3. Feature tests:
   - All CRUD endpoints
   - Error cases
   - Pagination
4. Integration test:
   - Create note during playback
   - Retrieve notes in timeline
```

**3. Execution (Delegating to Agents)**

**Phase 1: Database (Architecture Agent)**
```
✓ Migration created: 2025_01_23_create_video_notes_table.php
✓ VideoNote model created with relationships
✓ Indexes added
✓ Factory created
```

**Phase 2: Business Logic (Feature Agent)**
```
✓ VideoNoteService created and implemented
✓ Authorization service created
✓ Business rules enforced
✓ Events added (NoteCreated, NoteDeleted)
```

**Phase 3: API Layer (API Agent)**
```
✓ FormRequests created with validation
✓ VideoNoteController implemented
✓ VideoNoteResource created
✓ Routes added to api.php
✓ Scribe documentation complete
```

**Phase 4: Testing (Quality Agent)**
```
✓ VideoNoteServiceTest - 15 tests
✓ VideoNoteControllerTest - 12 tests
✓ Integration test - full workflow
✓ Coverage: 94%
```

**4. Verification (Orchestrator)**
```
✓ All quality gates passed
✓ Pint: 0 issues
✓ PHPStan: 0 errors
✓ Tests: 27/27 passing
✓ Coverage: 94%
✓ Multi-tenancy verified
✓ Authorization working
✓ Documentation updated
```

**5. Completion Report**
```
Feature: Video Notes - COMPLETED ✓

Components Created:
- Database: video_notes table + migration
- Model: VideoNote with relationships
- Service: VideoNoteService (7 methods)
- Controller: VideoNoteController (4 endpoints)
- Resource: VideoNoteResource
- Tests: 27 tests (94% coverage)
- Docs: /docs/features/VIDEO_NOTES.md

Quality Metrics:
- Pint: ✓ Pass
- PHPStan: ✓ Pass
- Tests: ✓ 27/27
- Coverage: ✓ 94%

Ready for: Code review, merge to main
```

---

## Continuous Improvement

### After Each Feature
```
Retrospective:
1. What went well?
2. What was challenging?
3. Any new patterns discovered?
4. Should any skill be updated?
5. Any new conventions to document?
```

### Skill Evolution
```
Update this orchestrator skill when:
- New delegation patterns emerge
- New quality gates added
- New agents added
- Workflow optimizations found
- Common issues identified
```

---

## Emergency Procedures

### Rollback Procedure
```
If major issues discovered:
1. Identify affected migrations
2. Create rollback plan
3. Revert code changes
4. Run tests to verify stability
5. Document lessons learned
```

### Hotfix Procedure
```
For critical production issues:
1. Create hotfix branch
2. Minimal fix only (no refactoring)
3. Add regression test
4. Fast-track through quality gates
5. Deploy and monitor
6. Schedule proper fix in next sprint
```

---

## Integration with GitHub Issues

### Issue Template
```markdown
## Feature: [Name]

### Context
[What problem does this solve?]

### Components Affected
- [ ] Database schema
- [ ] Services
- [ ] API endpoints
- [ ] Tests
- [ ] Documentation

### Task Breakdown
[Generated by Orchestrator]

### Acceptance Criteria
- [ ] All quality gates passed
- [ ] Documentation updated
- [ ] Tests cover edge cases
- [ ] No regressions introduced

### Agent Assignments
- Architecture Agent: [tasks]
- Feature Agent: [tasks]
- API Agent: [tasks]
- Quality Agent: [tasks]
```

---

## Usage Guidelines for Humans

### Starting a New Feature
```
1. Describe the feature to the Orchestrator
2. Review the task breakdown
3. Approve or request modifications
4. Let Orchestrator delegate to agents
5. Review completed work
```

### Monitoring Progress
```
1. Check status updates
2. Review each agent's output
3. Verify quality gates
4. Provide feedback if needed
```

### Best Practices
```
- Trust the Orchestrator's planning
- Don't skip quality gates
- Review all code before merging
- Keep skills up to date
- Document deviations from standards
```

---

## Related Skills
- Master Skill: `/mnt/skills/user/xyz-lms/SKILL.md`
- Architecture Agent: `/mnt/skills/user/xyz-lms-architecture/SKILL.md`
- Feature Agent: `/mnt/skills/user/xyz-lms-features/SKILL.md`
- Code Quality Agent: `/mnt/skills/user/xyz-lms-quality/SKILL.md`
- API Agent: `/mnt/skills/user/xyz-lms-api/SKILL.md`