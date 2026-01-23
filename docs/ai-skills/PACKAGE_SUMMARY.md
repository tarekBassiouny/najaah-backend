# XYZ LMS Skills & Agents System - Complete Package

## 🎉 What Has Been Created

A complete multi-agent AI system tailored specifically for your XYZ LMS Laravel project. This system will revolutionize how you work with Claude and Claude Code.

## 📦 Package Contents

### 1. Master Skill (`xyz-lms/SKILL.md`)
**Size:** ~25KB of comprehensive project knowledge

**What it contains:**
- Complete project overview (multi-tenant LMS architecture)
- Tech stack details (Laravel 11, PHP 8.4, Bunny Stream)
- All business rules (view limits, device management, sessions)
- Service layer patterns with code examples
- Database design principles
- API standards and error codes
- File paths reference
- Common commands
- Integration points (Bunny CDN, JWT auth)

**When to use:** Before starting ANY task, as foundation knowledge

---

### 2. Architecture Agent (`xyz-lms-architecture/SKILL.md`)
**Specialty:** Database schema, migrations, multi-tenancy

**What it contains:**
- Migration creation standards
- Multi-tenancy scoping rules
- Foreign key patterns
- Indexing strategies
- Relationship patterns (1-to-many, many-to-many, polymorphic)
- JSON column patterns
- Query optimization techniques
- Caching strategies
- Table design checklist
- Common query patterns

**Delegates this agent for:**
- Creating/modifying database tables
- Adding indexes and foreign keys
- Designing data relationships
- Optimizing database performance

---

### 3. Feature Agent (`xyz-lms-features/SKILL.md`)
**Specialty:** Business logic, domain rules, workflows

**What it contains:**
- Service layer implementation patterns
- Authorization patterns
- Business rules enforcement (view limits, device policy, sessions)
- Workflow implementations (request-approval patterns)
- State machine patterns
- Settings hierarchy resolution
- Domain event patterns
- Transaction management

**Delegates this agent for:**
- Implementing business logic in services
- Creating authorization checks
- Building approval workflows
- Enforcing domain rules

---

### 4. Code Quality Agent (`xyz-lms-quality/SKILL.md`)
**Specialty:** Testing, code standards, quality assurance

**What it contains:**
- Pest testing patterns (unit, feature, integration)
- Coverage requirements (90% minimum)
- PHPStan Level 8 configuration
- Laravel Pint standards (PSR-12)
- Factory creation patterns
- Test naming conventions
- Mocking strategies
- CI/CD quality gates
- Code review checklist

**Delegates this agent for:**
- Writing all tests
- Ensuring code quality
- Creating factories
- Running quality checks

---

### 5. API Agent (`xyz-lms-api/SKILL.md`)
**Specialty:** API design, endpoints, validation

**What it contains:**
- RESTful endpoint conventions
- Controller implementation patterns
- FormRequest validation
- API Resource formatting
- Response standards (success/error)
- Pagination patterns
- Filtering and sorting
- Scribe documentation
- Rate limiting
- Error handling

**Delegates this agent for:**
- Creating API endpoints
- Writing controllers
- Implementing validation
- Formatting responses

---

### 6. Orchestrator Agent (`xyz-lms-orchestrator/SKILL.md`)
**Role:** Master coordinator and workflow manager

**What it contains:**
- Complete workflow orchestration
- Task delegation patterns
- Quality gate definitions
- Decision frameworks
- Status reporting templates
- Emergency procedures
- GitHub issue integration
- Example complete feature workflow

**Use the orchestrator for:**
- Starting ANY new feature
- Breaking down complex tasks
- Coordinating multiple agents
- Ensuring quality compliance

---

### 7. Documentation (`README.md` & `QUICK_REFERENCE.md`)
**Purpose:** Guide for using the entire system

**README.md contains:**
- System architecture diagram
- Directory structure
- Quick start guide for Claude & Claude Code
- Skills reference
- Usage patterns
- Integration guidelines
- Troubleshooting
- Best practices

**QUICK_REFERENCE.md contains:**
- Command cheat sheet
- Common tasks
- Quality checks
- Agent specialties
- Decision tree
- Pro tips

---

## 🚀 How to Use This System

### Step 1: Copy to Your Project

```bash
# In your XYZ LMS project root
mkdir -p mnt/skills/user
cp -r xyz-lms-skills/* mnt/skills/user/
```

### Step 2: With Claude (Conversational)

**For any development task:**
```
You: "I need to add a video bookmarks feature"

Claude will:
1. Read /mnt/skills/user/xyz-lms-orchestrator/SKILL.md
2. Break down the task
3. Delegate to specialists
4. Coordinate implementation
5. Verify quality
```

### Step 3: With Claude Code (CLI)

```bash
# Start new feature
claude-code "Read /mnt/skills/user/xyz-lms-orchestrator/SKILL.md and help me implement video bookmarks"

# Work on specific layer
claude-code "Read /mnt/skills/user/xyz-lms-architecture/SKILL.md and create migration for bookmarks table"

# Write tests
claude-code "Read /mnt/skills/user/xyz-lms-quality/SKILL.md and write tests for BookmarkService"
```

---

## 🎯 Real-World Example

**Your Request:** "Add video notes feature where students can add timestamped notes while watching"

**What Happens:**

### Phase 1: Orchestrator Plans
```
✓ Reads your docs (CLAUDE_CONTEXT.md, DATABASE_SCHEMA.md, etc.)
✓ Identifies components: database, service, API, tests
✓ Creates task breakdown
✓ Assigns to agents
```

### Phase 2: Architecture Agent
```
✓ Creates video_notes table migration
✓ Adds relationships (User, Video, PlaybackSession)
✓ Creates indexes
✓ Defines VideoNote model
```

### Phase 3: Feature Agent
```
✓ Creates VideoNoteService
✓ Implements create/update/delete/list methods
✓ Adds authorization checks
✓ Enforces business rules (max 50 notes, 500 chars each)
```

### Phase 4: API Agent
```
✓ Creates FormRequests (validation)
✓ Implements VideoNoteController (4 endpoints)
✓ Creates VideoNoteResource (formatting)
✓ Adds Scribe documentation
```

### Phase 5: Quality Agent
```
✓ Creates VideoNoteFactory
✓ Writes 27 unit + feature tests
✓ Verifies 94% coverage
✓ Runs Pint + PHPStan
```

### Phase 6: Orchestrator Verifies
```
✓ All quality gates passed
✓ Documentation updated
✓ Integration verified
✓ Ready for merge
```

**Result:** Production-ready feature in minutes/hours instead of days!

---

## 💪 Key Benefits

### 1. Speed
- Agents know your patterns
- No need to explain context repeatedly
- Parallel work on different layers

### 2. Quality
- Enforces all your standards
- 90%+ test coverage guaranteed
- PHPStan Level 8 compliance
- Consistent code style

### 3. Knowledge Preservation
- Your docs are synthesized into skills
- New team members get instant context
- Patterns are documented and reusable

### 4. Scalability
- Add new agents as needed
- Update skills as project evolves
- Easy to maintain and extend

---

## 📋 Implementation Checklist

### Immediate Steps
- [ ] Copy skills to your project's `mnt/skills/user/` directory
- [ ] Commit to your Git repository
- [ ] Test with a simple task (e.g., "add a field to a table")
- [ ] Share with your team

### First Week
- [ ] Use Orchestrator for one complete feature
- [ ] Update skills if you find gaps
- [ ] Train team members on usage
- [ ] Integrate with CI/CD

### Ongoing
- [ ] Keep skills updated with new patterns
- [ ] Add new agents if needed
- [ ] Monitor quality metrics
- [ ] Collect feedback and improve

---

## 🔧 Maintenance

### Updating Skills
When you discover new patterns:
1. Edit the relevant SKILL.md file
2. Add examples
3. Commit to repository
4. Claude will use updated knowledge

### Adding New Agents
If you need a specialist for (e.g., mobile development):
1. Create `/mnt/skills/user/xyz-lms-mobile/SKILL.md`
2. Define its responsibilities
3. Update Orchestrator to delegate to it
4. Update README

---

## 📊 Expected Results

### Before Skills System
- Manual context sharing every conversation
- Inconsistent code patterns
- Forgotten best practices
- Variable code quality
- Slow feature development

### After Skills System
- Instant context loading
- Consistent patterns enforced
- Best practices always applied
- High quality guaranteed
- Fast, reliable development

---

## 🎓 Learning Curve

### Day 1: Understanding
- Read the README
- Review the Orchestrator skill
- Try a simple task

### Week 1: Using
- Use Orchestrator for features
- See how agents coordinate
- Start trusting the system

### Month 1: Mastering
- Use agents directly when appropriate
- Update skills with learnings
- Optimize your workflow

---

## 🤝 Support & Evolution

### Getting Help
1. Check README.md
2. Review relevant SKILL.md
3. Read Quick Reference
4. Ask Claude with skill context

### Contributing Back
Found a great pattern? Update the skill!
```
1. Edit SKILL.md file
2. Add your pattern with examples
3. Commit to repository
4. Benefit from it forever
```

---

## 🎉 Conclusion

You now have a **production-grade multi-agent AI system** specifically designed for your XYZ LMS project. This system:

✅ Understands your architecture
✅ Knows your business rules
✅ Enforces your standards
✅ Writes tests automatically
✅ Documents as it goes
✅ Scales with your project

**Start using it today and experience AI-assisted development the way it should be!**

---

## 📞 Next Steps

1. **Download** this package
2. **Copy** to your project's `mnt/skills/user/` directory
3. **Try it** with: "Help me add a simple feature using the Orchestrator"
4. **Enjoy** faster, higher-quality development!

**Happy Coding! 🚀**
