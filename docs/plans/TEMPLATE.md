# Feature Plan: [Feature Name]

> Brief one-line description of what this feature does.

**Status:** Draft | In Review | Approved | In Progress | Completed
**Author:** [Name]
**Created:** YYYY-MM-DD
**Last Updated:** YYYY-MM-DD

---

## Overview

### Problem Statement

What problem does this feature solve? Why is it needed?

### Proposed Solution

High-level description of the solution approach.

### Success Criteria

- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

---

## Requirements

### Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-1 | Description | Must Have |
| FR-2 | Description | Should Have |
| FR-3 | Description | Nice to Have |

### Non-Functional Requirements

| ID | Requirement | Metric |
|----|-------------|--------|
| NFR-1 | Performance | Response time < 200ms |
| NFR-2 | Security | Authentication required |
| NFR-3 | Scalability | Support 1000 concurrent users |

### Out of Scope

- Item 1
- Item 2

---

## Technical Design

### Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                     Component Diagram                                │
└─────────────────────────────────────────────────────────────────────┘

    ┌──────────┐     ┌──────────┐     ┌──────────┐
    │ Component│────►│ Component│────►│ Component│
    │    A     │     │    B     │     │    C     │
    └──────────┘     └──────────┘     └──────────┘
```

### Database Changes

#### New Tables

```sql
-- table_name
CREATE TABLE table_name (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    column_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

#### Schema Modifications

| Table | Change | Migration Name |
|-------|--------|----------------|
| `table_name` | Add column `x` | `add_x_to_table_name` |

### API Endpoints

#### New Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/resource` | Create resource |
| GET | `/api/v1/resource/{id}` | Get resource |
| PUT | `/api/v1/resource/{id}` | Update resource |
| DELETE | `/api/v1/resource/{id}` | Delete resource |

#### Request/Response Examples

**POST /api/v1/resource**

Request:
```json
{
    "field": "value"
}
```

Response (201):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "field": "value"
    }
}
```

### Service Layer

#### New Services

| Service | Purpose |
|---------|---------|
| `App\Services\Feature\FeatureService` | Core business logic |

#### Service Methods

```php
// FeatureService.php
public function methodName(Type $param): ReturnType
{
    // Description of what this method does
}
```

### Models

#### New Models

| Model | Table | Key Relationships |
|-------|-------|-------------------|
| `Feature` | `features` | belongsTo User, hasMany Items |

### Events/Jobs (if applicable)

| Class | Type | Trigger |
|-------|------|---------|
| `FeatureCreated` | Event | After feature creation |
| `ProcessFeature` | Job | Queue processing |

---

## Dependencies

### Internal Dependencies

| Dependency | Purpose | Status |
|------------|---------|--------|
| `SettingsResolverService` | Resolve feature settings | Existing |
| `AuthService` | User authentication | Existing |

### External Dependencies

| Dependency | Purpose | Version |
|------------|---------|---------|
| Package name | Purpose | ^1.0 |

### Blocked By

- [ ] Dependency 1 (ticket/PR link)
- [ ] Dependency 2 (ticket/PR link)

---

## Implementation Tasks

### Phase 1: Foundation

| Task | Description | Estimate | Assignee |
|------|-------------|----------|----------|
| 1.1 | Create migration for new table | S | - |
| 1.2 | Create Model with relationships | S | - |
| 1.3 | Create base Service class | M | - |

### Phase 2: Core Logic

| Task | Description | Estimate | Assignee |
|------|-------------|----------|----------|
| 2.1 | Implement main business logic | L | - |
| 2.2 | Add validation rules | S | - |
| 2.3 | Error handling | M | - |

### Phase 3: API Layer

| Task | Description | Estimate | Assignee |
|------|-------------|----------|----------|
| 3.1 | Create FormRequest classes | S | - |
| 3.2 | Create Controller | M | - |
| 3.3 | Define routes | S | - |
| 3.4 | Create Resource classes | S | - |

### Phase 4: Testing & Documentation

| Task | Description | Estimate | Assignee |
|------|-------------|----------|----------|
| 4.1 | Unit tests for Service | M | - |
| 4.2 | Feature tests for API | M | - |
| 4.3 | Update API documentation | S | - |

**Estimate Key:** S = Small (< 2 hours), M = Medium (2-4 hours), L = Large (4-8 hours), XL = Extra Large (> 8 hours)

---

## Task Details

### Task 1.1: Create migration for new table

**Files to create/modify:**
- `database/migrations/YYYY_MM_DD_HHMMSS_create_table_name_table.php`

**Acceptance criteria:**
- [ ] Migration creates table with all required columns
- [ ] Indexes defined for foreign keys and frequently queried columns
- [ ] Migration is reversible

**Notes:**
- Any special considerations

---

### Task 2.1: Implement main business logic

**Files to create/modify:**
- `app/Services/Feature/FeatureService.php`

**Acceptance criteria:**
- [ ] Service handles core use case
- [ ] Proper error handling with DomainException
- [ ] Transaction wrapping for multi-step operations

**Dependencies:**
- Task 1.1, 1.2 must be complete

---

## Testing Plan

### Unit Tests

| Test Class | Coverage |
|------------|----------|
| `FeatureServiceTest` | Service methods |
| `FeatureModelTest` | Model relationships, scopes |

### Feature Tests

| Test Class | Coverage |
|------------|----------|
| `FeatureApiTest` | API endpoints |
| `FeatureIntegrationTest` | Full flow |

### Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Happy path - create resource | Feature | High |
| Validation failure | Feature | High |
| Unauthorized access | Feature | High |
| Edge case - duplicate | Unit | Medium |

### Test Commands

```bash
# Run all tests for this feature
./vendor/bin/sail test --filter="Feature"

# Run with coverage
./vendor/bin/sail test --filter="Feature" --coverage
```

---

## Rollout Plan

### Pre-deployment

- [ ] All tests passing
- [ ] Code review approved
- [ ] Documentation updated
- [ ] Staging environment tested

### Deployment Steps

1. Run database migrations
2. Deploy code changes
3. Clear caches (`php artisan cache:clear`, `php artisan config:clear`)
4. Verify endpoints responding
5. Monitor error logs

### Feature Flags (if applicable)

| Flag | Purpose | Default |
|------|---------|---------|
| `feature.enabled` | Enable/disable feature | false |

### Rollback Plan

**Trigger conditions:**
- Error rate > 5%
- Response time > 2s
- Critical bug discovered

**Rollback steps:**
1. Revert code deployment
2. Run rollback migration (if safe)
3. Clear caches
4. Notify stakeholders

**Data considerations:**
- Describe any data created during feature usage
- How to handle orphaned data on rollback

---

## Documentation Updates

### Files to Update

- [ ] `docs/CLAUDE_CONTEXT.md` - Add feature to overview
- [ ] `docs/features/FEATURE_NAME.md` - Create feature documentation
- [ ] `docs/architecture/DATABASE_SCHEMA.md` - Add new tables
- [ ] `README.md` - Update if needed

### API Documentation

- [ ] FormRequest `bodyParameters()` defined
- [ ] FormRequest `queryParameters()` defined (if GET)
- [ ] Run `php artisan scribe:generate`
- [ ] Verify generated docs

---

## Open Questions

| Question | Status | Answer |
|----------|--------|--------|
| Question 1? | Open | - |
| Question 2? | Resolved | Answer here |

---

## References

- Related PRs: #123, #456
- Related tickets: TICKET-123
- External docs: [Link](url)
- Design mockups: [Link](url)

---

## Changelog

| Date | Author | Change |
|------|--------|--------|
| YYYY-MM-DD | Name | Initial draft |
| YYYY-MM-DD | Name | Added technical design |
