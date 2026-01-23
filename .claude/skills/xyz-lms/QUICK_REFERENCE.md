# XYZ LMS Skills - Quick Reference

## 🚀 Quick Start

### For Any New Task
```bash
# Start here - Orchestrator will coordinate everything
claude-code "Read /mnt/skills/user/xyz-lms-orchestrator/SKILL.md and help me with [task]"
```

### For Specific Work

```bash
# Database work
claude-code "Read /mnt/skills/user/xyz-lms-architecture/SKILL.md and create migration for..."

# Business logic
claude-code "Read /mnt/skills/user/xyz-lms-features/SKILL.md and implement service for..."

# API endpoints
claude-code "Read /mnt/skills/user/xyz-lms-api/SKILL.md and create endpoint for..."

# Testing
claude-code "Read /mnt/skills/user/xyz-lms-quality/SKILL.md and write tests for..."

# General context
claude-code "Read /mnt/skills/user/xyz-lms/SKILL.md for project context"
```

## 📁 Skill Locations

| Skill | Path | Use For |
|-------|------|---------|
| Master | `/mnt/skills/user/xyz-lms/SKILL.md` | Project context, any task |
| Orchestrator | `/mnt/skills/user/xyz-lms-orchestrator/SKILL.md` | Coordinating features |
| Architecture | `/mnt/skills/user/xyz-lms-architecture/SKILL.md` | Database, schema, caching |
| Feature | `/mnt/skills/user/xyz-lms-features/SKILL.md` | Services, business logic |
| API | `/mnt/skills/user/xyz-lms-api/SKILL.md` | Endpoints, validation |
| Quality | `/mnt/skills/user/xyz-lms-quality/SKILL.md` | Tests, code quality |

## 🎯 Common Tasks

### Create New Feature
```
1. Tell Orchestrator: "Create [feature] feature"
2. Review task breakdown
3. Approve plan
4. Let agents execute
5. Verify quality gates
```

### Fix Bug
```
1. Tell Orchestrator: "Fix: [bug description]"
2. Review analysis
3. Let Feature Agent fix
4. Quality Agent adds regression test
5. Verify fix
```

### Add API Endpoint
```
1. API Agent: Create endpoint structure
2. Feature Agent: Implement service logic
3. Quality Agent: Write tests
4. Orchestrator: Verify integration
```

### Modify Database
```
1. Architecture Agent: Create migration
2. Update model
3. Quality Agent: Update tests
4. Verify in development
```

## ✅ Quality Checks

### Before Committing
```bash
./vendor/bin/sail pint --test          # Code style
./vendor/bin/sail composer phpstan      # Type safety
./vendor/bin/sail test                  # Run tests
./vendor/bin/sail test --coverage --min=90  # Coverage
```

### All at Once
```bash
./vendor/bin/sail composer quality
```

## 🔧 Agent Specialties

| Need | Ask This Agent |
|------|---------------|
| Database table | Architecture |
| Migration | Architecture |
| Indexes | Architecture |
| Service method | Feature |
| Business rule | Feature |
| Authorization | Feature |
| API endpoint | API |
| Validation rules | API |
| API response format | API |
| Unit test | Quality |
| Feature test | Quality |
| Factory | Quality |
| Code review | Quality |
| Coordinate all | Orchestrator |

## 📋 Workflow Checklist

### New Feature
- [ ] Orchestrator plans breakdown
- [ ] Architecture creates schema
- [ ] Feature implements logic
- [ ] API creates endpoints
- [ ] Quality writes tests
- [ ] All quality gates pass
- [ ] Documentation updated

### Bug Fix
- [ ] Orchestrator analyzes issue
- [ ] Feature Agent fixes code
- [ ] Quality adds regression test
- [ ] All tests pass
- [ ] No new issues introduced

## 🚨 Common Mistakes

❌ **Don't skip Orchestrator** for complex tasks
✅ Start with Orchestrator, let it delegate

❌ **Don't forget to read skills** before implementing
✅ Always read relevant skill file first

❌ **Don't write code without tests**
✅ Quality Agent writes tests for everything

❌ **Don't ignore quality gates**
✅ Pass all gates before merging

## 💡 Pro Tips

1. **Trust the System**: Agents know the patterns
2. **Read Skills First**: They contain hard-won knowledge
3. **Update When Needed**: Found a new pattern? Update the skill
4. **Quality First**: Better to do it right than fast
5. **Document Deviations**: If you must break a rule, document why

## 📞 Decision Tree

```
Got a task?
│
├─ Complex feature? → Orchestrator
├─ Database work? → Architecture Agent
├─ Business logic? → Feature Agent
├─ API work? → API Agent
├─ Need tests? → Quality Agent
└─ Just need context? → Master Skill
```

## 🎓 Learning Path

1. **Start**: Read Master Skill
2. **Explore**: Browse each specialist skill
3. **Practice**: Use Orchestrator for a small feature
4. **Master**: Use agents directly for specific tasks
5. **Contribute**: Update skills with new patterns

## ⚡ Emergency Commands

```bash
# Rollback last migration
./vendor/bin/sail artisan migrate:rollback

# Clear all caches
./vendor/bin/sail artisan optimize:clear

# Fix code style automatically
./vendor/bin/sail pint

# Run specific test
./vendor/bin/sail test --filter=TestName
```

## 📊 Success Metrics

Good development with skills:
- ✅ 90%+ test coverage
- ✅ 0 PHPStan errors
- ✅ 0 Pint issues
- ✅ Clear documentation
- ✅ Consistent patterns

## 🔗 Quick Links

- Full README: `/mnt/skills/user/xyz-lms/README.md`
- Project Docs: `/docs/`
- AI Instructions: `/docs/AI_INSTRUCTIONS.md`
- Database Schema: `/docs/architecture/DATABASE_SCHEMA.md`

---

**Remember**: The skills system is here to help you build better code faster. Use it, trust it, and keep it updated!
