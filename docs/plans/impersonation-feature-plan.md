# Center Impersonation Feature Plan

## Overview
Allow super admins to "login as" a branded center admin for support purposes, with full audit trail and transparency.

---

## Dashboard & Navigation Flows

### Super Admin Dashboard (After Login)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  🏠 Najaah Platform                              👤 Super Admin ▼      │
├──────────────────┬──────────────────────────────────────────────────────┤
│                  │                                                      │
│  PLATFORM        │   📊 Platform Overview                               │
│  ──────────────  │   ─────────────────────────────────────────────      │
│                  │                                                      │
│  📊 Dashboard    │   ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐   │
│  🏢 Centers      │   │ Centers │ │Students │ │ Courses │ │ Revenue │   │
│  👥 All Users    │   │   24    │ │  12,450 │ │   892   │ │ $45.2K  │   │
│  📚 All Courses  │   └─────────┘ └─────────┘ └─────────┘ └─────────┘   │
│  📋 Surveys      │                                                      │
│     (System)     │   Recent Centers                                     │
│  📈 Analytics    │   ┌──────────────────────────────────────────────┐  │
│  ⚙️ Settings     │   │ Elite Learning (Branded)    [🔑 Login As]   │  │
│                  │   │ Smart Academy (Branded)     [🔑 Login As]   │  │
│  ──────────────  │   │ Najaah Academy (Unbranded)  [Manage]        │  │
│  SYSTEM          │   └──────────────────────────────────────────────┘  │
│  ──────────────  │                                                      │
│  🔐 Roles        │   System Surveys           Platform Analytics        │
│  📝 Audit Logs   │   ┌─────────────────┐     ┌─────────────────────┐   │
│  🔧 System Config│   │ 3 Active        │     │ [Chart: Enrollments]│   │
│                  │   │ 2 Draft         │     │                     │   │
│                  │   └─────────────────┘     └─────────────────────┘   │
│                  │                                                      │
└──────────────────┴──────────────────────────────────────────────────────┘
```

**Super Admin Sidebar Menu:**
```
PLATFORM
├── 📊 Dashboard (Platform overview)
├── 🏢 Centers
│   ├── All Centers (list with Login As for branded)
│   ├── Create Center
│   └── Center Types
├── 👥 Users
│   ├── All Students (cross-center)
│   ├── All Admins
│   └── Invitations
├── 📚 Courses (cross-center view)
├── 📋 Surveys
│   ├── System Surveys (scope_type=1)
│   └── All Surveys (read-only for branded)
├── 📈 Analytics
│   ├── Platform Analytics
│   ├── Revenue Reports
│   └── Usage Reports
└── ⚙️ Platform Settings

SYSTEM
├── 🔐 Roles & Permissions
├── 📝 Audit Logs
├── 🔧 System Configuration
├── 🔑 API Keys (Platform level)
└── 👁 Impersonation Logs
```

---

### Center Admin Dashboard (After Login)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  🏢 Elite Learning Academy                       👤 Center Admin ▼     │
├──────────────────┬──────────────────────────────────────────────────────┤
│                  │                                                      │
│  CENTER          │   📊 Center Overview                                 │
│  ──────────────  │   ─────────────────────────────────────────────      │
│                  │                                                      │
│  📊 Dashboard    │   ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐   │
│  📚 Courses      │   │ Active  │ │Students │ │ Courses │ │  Views  │   │
│  👥 Students     │   │  450    │ │   450   │ │   12    │ │  8.2K   │   │
│  👨‍🏫 Instructors │   └─────────┘ └─────────┘ └─────────┘ └─────────┘   │
│  📋 Surveys      │                                                      │
│  📈 Analytics    │   Recent Activity                                    │
│  💳 Billing      │   ┌──────────────────────────────────────────────┐  │
│                  │   │ • 5 new enrollments today                    │  │
│  ──────────────  │   │ • Survey "Feedback Q1" has 23 responses     │  │
│  SETTINGS        │   │ • Course "Laravel 101" published            │  │
│  ──────────────  │   └──────────────────────────────────────────────┘  │
│  ⚙️ Center Info  │                                                      │
│  🎨 Branding     │   My Surveys              Quick Actions              │
│  🔑 API Keys     │   ┌─────────────────┐     ┌─────────────────────┐   │
│  👥 Admin Users  │   │ 2 Active        │     │ [+ New Course]      │   │
│  📝 Access Logs  │   │ 1 Draft         │     │ [+ New Survey]      │   │
│                  │   └─────────────────┘     │ [+ Enroll Students] │   │
│                  │                           └─────────────────────┘   │
└──────────────────┴──────────────────────────────────────────────────────┘
```

**Center Admin Sidebar Menu:**
```
CENTER
├── 📊 Dashboard (Center overview)
├── 📚 Courses
│   ├── My Courses
│   ├── Create Course
│   └── Categories
├── 👥 Students
│   ├── All Students (center only)
│   ├── Enrollments
│   └── Import Students
├── 👨‍🏫 Instructors
├── 📋 Surveys (center surveys only)
│   ├── My Surveys
│   └── Create Survey
├── 📈 Analytics
│   ├── Enrollment Analytics
│   ├── Video Analytics
│   └── Survey Reports
└── 💳 Billing (if branded)

SETTINGS
├── ⚙️ Center Information
├── 🎨 Branding & Theme
├── 🔑 API Keys
├── 👥 Admin Users & Roles
└── 📝 Access Logs (who accessed center)
```

---

### Super Admin Impersonating Center

```
┌─────────────────────────────────────────────────────────────────────────┐
│ ⚠️ IMPERSONATING: Elite Learning Academy         [Exit Impersonation]  │
├─────────────────────────────────────────────────────────────────────────┤
│  🏢 Elite Learning Academy                       👤 As Center Admin    │
├──────────────────┬──────────────────────────────────────────────────────┤
│                  │                                                      │
│  CENTER          │   📊 Center Overview                                 │
│  ──────────────  │   (Same as Center Admin view)                        │
│                  │                                                      │
│  📊 Dashboard    │                                                      │
│  📚 Courses      │   ┌─────────────────────────────────────────────┐   │
│  👥 Students     │   │                                              │   │
│  👨‍🏫 Instructors │   │   Full center admin functionality           │   │
│  📋 Surveys      │   │   available here...                          │   │
│  📈 Analytics    │   │                                              │   │
│  💳 Billing 🔒   │   └─────────────────────────────────────────────┘   │
│     (Blocked)    │                                                      │
│                  │                                                      │
│  ──────────────  │                                                      │
│  SETTINGS        │                                                      │
│  ──────────────  │                                                      │
│  ⚙️ Center Info  │                                                      │
│  🎨 Branding     │                                                      │
│  🔑 API Keys     │                                                      │
│  👥 Admin Users  │                                                      │
│  📝 Access Logs  │                                                      │
│  ❌ Delete 🔒    │                                                      │
│     (Blocked)    │                                                      │
│                  │                                                      │
└──────────────────┴──────────────────────────────────────────────────────┘

Visual Indicators:
- Orange/amber warning banner at top (persistent)
- "Exit Impersonation" button always visible
- Blocked items shown with 🔒 lock icon
- Slightly tinted/bordered UI (optional)
```

---

## Scope Comparison Table

| Feature | Super Admin | Center Admin | Impersonating |
|---------|-------------|--------------|---------------|
| **Dashboard** | Platform-wide stats | Center stats only | Center stats only |
| **Centers** | View/Manage all | Own center only | Target center only |
| **Students** | All students | Center students | Center students |
| **Courses** | All courses | Center courses | Center courses |
| **Surveys - System** | Full CRUD | Read-only (if unbranded) | ❌ Hidden |
| **Surveys - Center** | View all, Edit own | Full CRUD own center | Full CRUD |
| **Analytics** | Platform + all centers | Center only | Center only |
| **Billing** | View all | Manage own | 🔒 Blocked |
| **API Keys** | Platform keys | Center keys | Center keys ✅ |
| **User Management** | All users | Center admins only | Center admins |
| **Roles** | System roles | ❌ No access | ❌ No access |
| **Audit Logs** | All logs | Center access logs | Center access logs |
| **System Config** | Full access | ❌ No access | ❌ No access |
| **Delete Center** | Can delete | ❌ No access | 🔒 Blocked |

---

## API Scoping Rules

### Endpoints by Role

| Endpoint Pattern | Super Admin | Center Admin | Impersonating |
|------------------|-------------|--------------|---------------|
| `GET /admin/centers` | ✅ All | ❌ 403 | ❌ 403 |
| `GET /admin/centers/{id}` | ✅ Any | ✅ Own only | ✅ Target only |
| `GET /admin/students` | ✅ All (filterable) | ✅ Center scoped | ✅ Center scoped |
| `GET /admin/courses` | ✅ All (filterable) | ✅ Center scoped | ✅ Center scoped |
| `GET /admin/surveys` | ✅ All | ✅ Center only | ✅ Center only |
| `POST /admin/surveys` (system) | ✅ | ❌ 403 | ❌ 403 |
| `POST /admin/surveys` (center) | ✅ Any center | ✅ Own center | ✅ Target center |
| `GET /admin/analytics/platform` | ✅ | ❌ 403 | ❌ 403 |
| `GET /admin/analytics/center/{id}` | ✅ Any | ✅ Own only | ✅ Target only |
| `PUT /admin/centers/{id}/billing` | ✅ | ✅ Own only | 🔒 403 Blocked |
| `DELETE /admin/centers/{id}` | ✅ | ❌ 403 | 🔒 403 Blocked |
| `POST /admin/impersonate/{id}` | ✅ | ❌ 403 | ❌ 403 |
| `GET /admin/impersonation-logs` | ✅ | ❌ 403 | ❌ 403 |

### Scoping Logic in Services

```php
// Pattern for all scoped services
public function list(User $actor, Filters $filters): Collection
{
    $query = Model::query();

    if ($actor->isImpersonating()) {
        // Impersonation: scope to impersonated center
        $query->where('center_id', $actor->getImpersonatedCenterId());
    } elseif ($actor->hasRole('super_admin')) {
        // Super admin: optional center filter
        if ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        }
    } else {
        // Center admin: always scope to own center
        $query->where('center_id', $actor->center_id);
    }

    return $query->get();
}
```

---

## User Flow

### 1. Starting Impersonation

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Super Admin > Centers                                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │ 🏢 Elite Learning Academy                              Branded  │   │
│  │    12 courses • 450 students • Active                           │   │
│  │                                                                  │   │
│  │    [View Details]  [Analytics]  [🔑 Login as Admin]            │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │ 🏢 Najaah Academy                                    Unbranded  │   │
│  │    45 courses • 2,300 students • Active                         │   │
│  │                                                                  │   │
│  │    [Manage]  [Analytics]  [Settings]                            │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2. Confirmation Modal

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   🔑 Login as Center Admin                                              │
│   ─────────────────────────────────────────────────────────────────     │
│                                                                         │
│   You are about to access:                                              │
│   Elite Learning Academy                                                │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │ Reason for access (required)                                    │   │
│   │ ┌─────────────────────────────────────────────────────────────┐ │   │
│   │ │ Investigating survey submission issue reported in ticket    │ │   │
│   │ │ #4521                                                       │ │   │
│   │ └─────────────────────────────────────────────────────────────┘ │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│   ⚠️  This session will be logged and visible to the center.           │
│                                                                         │
│                              [Cancel]  [Start Session]                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3. Impersonation Active State

```
┌─────────────────────────────────────────────────────────────────────────┐
│ ⚠️ IMPERSONATION MODE: Elite Learning Academy    [Exit to Super Admin] │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  🏠 Dashboard     📚 Courses     👥 Students     📋 Surveys             │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                                                                  │   │
│  │   Welcome to Elite Learning Academy Dashboard                    │   │
│  │                                                                  │   │
│  │   (Center admin sees their normal dashboard)                     │   │
│  │                                                                  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

Visual Differences:
- Orange/amber warning bar at top
- Slightly tinted background (optional)
- "Exit" button always visible
- Center logo/branding shown
```

### 4. Exit Impersonation

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   Exit Impersonation Session?                                           │
│   ─────────────────────────────────────────────────────────────────     │
│                                                                         │
│   Session Duration: 12 minutes                                          │
│   Center: Elite Learning Academy                                        │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │ Session notes (optional)                                        │   │
│   │ ┌─────────────────────────────────────────────────────────────┐ │   │
│   │ │ Fixed survey #42 visibility settings. Issue resolved.      │ │   │
│   │ └─────────────────────────────────────────────────────────────┘ │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│                                    [Cancel]  [End Session & Return]     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Frontend Implementation

### State Management

```typescript
// stores/impersonation.ts
interface ImpersonationState {
  isImpersonating: boolean;
  originalToken: string | null;
  impersonationToken: string | null;
  center: {
    id: number;
    name: string;
    logo_url: string | null;
  } | null;
  sessionId: number | null;
  startedAt: string | null;
  reason: string | null;
}

// Actions
- startImpersonation(centerId: number, reason: string)
- endImpersonation(notes?: string)
- restoreOriginalSession()
```

### Token Handling

```typescript
// On start impersonation:
1. Store current token as `originalToken`
2. Call POST /api/v1/admin/impersonate/{center}
3. Receive new `impersonationToken`
4. Replace auth token with impersonation token
5. Store session info in state
6. Redirect to center dashboard

// On API requests while impersonating:
- Use impersonationToken
- Backend validates and scopes to center

// On exit impersonation:
1. Call POST /api/v1/admin/impersonate/exit
2. Restore originalToken
3. Clear impersonation state
4. Redirect to super admin dashboard
```

### Components Needed

```
src/
├── components/
│   ├── impersonation/
│   │   ├── ImpersonationBanner.vue      # Top warning bar
│   │   ├── StartImpersonationModal.vue  # Confirmation + reason
│   │   ├── ExitImpersonationModal.vue   # Exit + notes
│   │   └── ImpersonationBadge.vue       # Small indicator
│   │
│   └── centers/
│       └── CenterCard.vue               # Add "Login As" button
│
├── stores/
│   └── impersonation.ts                 # Pinia store
│
├── composables/
│   └── useImpersonation.ts              # Shared logic
│
└── layouts/
    └── AdminLayout.vue                  # Include banner conditionally
```

### Persistent Storage

```typescript
// localStorage keys
'impersonation_original_token'    // Original super admin token
'impersonation_session'           // { centerId, sessionId, startedAt }

// On page refresh:
1. Check if impersonation session exists in localStorage
2. Validate session is still active (API call)
3. If valid: restore impersonation state
4. If invalid: clear and restore original token
```

---

## Backend Implementation

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/admin/impersonate/{center}` | Start impersonation |
| POST | `/api/v1/admin/impersonate/exit` | End impersonation |
| GET | `/api/v1/admin/impersonate/current` | Get current session info |
| GET | `/api/v1/admin/impersonate/logs` | List impersonation logs (super admin) |
| GET | `/api/v1/admin/center/access-logs` | View who accessed center (center admin) |

### Request/Response Examples

#### Start Impersonation
```http
POST /api/v1/admin/impersonate/5
Authorization: Bearer <super_admin_token>
Content-Type: application/json

{
  "reason": "Investigating survey issue reported in ticket #4521"
}
```

```json
{
  "success": true,
  "data": {
    "session_id": 123,
    "token": "eyJ...<impersonation_jwt>",
    "expires_at": "2026-02-11T18:00:00Z",
    "center": {
      "id": 5,
      "name": "Elite Learning Academy",
      "logo_url": "https://..."
    }
  }
}
```

#### Exit Impersonation
```http
POST /api/v1/admin/impersonate/exit
Authorization: Bearer <impersonation_token>
Content-Type: application/json

{
  "notes": "Fixed survey #42 visibility settings"
}
```

```json
{
  "success": true,
  "data": {
    "session_duration_seconds": 720,
    "message": "Impersonation session ended"
  }
}
```

### Database Schema

```sql
CREATE TABLE impersonation_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    super_admin_id BIGINT UNSIGNED NOT NULL,
    center_id BIGINT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    notes TEXT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (super_admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
    INDEX idx_super_admin (super_admin_id),
    INDEX idx_center (center_id),
    INDEX idx_active (ended_at, expires_at)
);
```

### JWT Claims for Impersonation

```json
{
  "sub": 123,                    // Super admin user ID
  "center_id": 5,                // Impersonated center
  "impersonation_session": 456,  // Session ID for audit
  "is_impersonating": true,      // Flag for middleware
  "original_roles": ["super_admin"],
  "effective_roles": ["center_admin"],
  "exp": 1707699600              // 8 hour expiry
}
```

### Backend Files to Create/Modify

```
app/
├── Http/
│   ├── Controllers/Admin/
│   │   └── ImpersonationController.php
│   ├── Requests/Admin/
│   │   ├── StartImpersonationRequest.php
│   │   └── EndImpersonationRequest.php
│   ├── Resources/Admin/
│   │   ├── ImpersonationSessionResource.php
│   │   └── ImpersonationLogResource.php
│   └── Middleware/
│       └── HandleImpersonation.php
│
├── Models/
│   └── ImpersonationSession.php
│
├── Services/
│   └── Impersonation/
│       ├── ImpersonationService.php
│       └── Contracts/
│           └── ImpersonationServiceInterface.php
│
└── database/
    └── migrations/
        └── 2026_02_11_create_impersonation_sessions_table.php

routes/
└── api/v1/admin/
    └── impersonation.php
```

---

## Security Considerations

### 1. Session Limits
- Max 8 hours per session
- Auto-expire inactive sessions after 1 hour
- Only one active impersonation per super admin

### 2. Audit Trail
- Log all actions during impersonation
- Include session_id in audit logs
- Cannot delete impersonation logs

### 3. Restrictions While Impersonating
- Cannot impersonate another center (must exit first)
- Cannot access super admin features
- Cannot modify billing/subscription settings
- Cannot delete the center
- CAN manage API keys (allowed)

### 4. Transparency
- Center admins can view access logs
- Session reason visible in logs
- No email notifications (to avoid noise)

---

## Implementation Phases

### Phase 1: Core Backend (Day 1)
- [ ] Migration for impersonation_sessions
- [ ] ImpersonationSession model
- [ ] ImpersonationService with start/end logic
- [ ] JWT token generation with impersonation claims
- [ ] ImpersonationController endpoints
- [ ] HandleImpersonation middleware
- [ ] Unit tests

### Phase 2: Scoping Updates (Day 1-2)
- [ ] Update SurveyService to respect impersonation
- [ ] Update other services (courses, students, etc.)
- [ ] Integration tests

### Phase 3: Frontend - State & API (Day 2)
- [ ] Impersonation Pinia store
- [ ] API service methods
- [ ] Token handling logic
- [ ] LocalStorage persistence

### Phase 4: Frontend - UI (Day 2-3)
- [ ] ImpersonationBanner component
- [ ] StartImpersonationModal component
- [ ] ExitImpersonationModal component
- [ ] Update CenterCard with "Login As" button
- [ ] Update AdminLayout

### Phase 5: Access Logs (Day 3)
- [ ] Logs endpoint for super admin
- [ ] Access logs endpoint for center admin
- [ ] Logs UI components

### Phase 6: Testing & Polish (Day 3-4)
- [ ] E2E tests
- [ ] Error handling
- [ ] Loading states
- [ ] Edge cases

---

## Decisions

1. **Notification**: No email notification when impersonation starts
   - Center admins can view access logs for transparency

2. **Access mode**: Full access only (no read-only mode)
   - Simplifies implementation
   - Super admin needs edit capability for support

3. **Session duration**: 8 hours maximum
   - Sufficient for extended support sessions
   - Auto-expire after 8 hours

4. **Restricted actions during impersonation**:
   - ❌ Billing/subscription changes - BLOCKED
   - ❌ Center deletion - BLOCKED
   - ✅ API key management - ALLOWED
   - ✅ All other admin actions - ALLOWED

---

## Approval

- [x] Backend plan approved
- [x] Frontend plan approved
- [x] Decisions finalized
- [ ] Ready for implementation
