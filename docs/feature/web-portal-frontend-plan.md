# Web Portal — Frontend Implementation Plan

## Status: Approved (2026-03-24)
## Execution: Run from najaah-frontend session, both streams in parallel

---

## Execution Strategy

All backend APIs are complete (7 phases merged to dev). Frontend work runs from a **single Claude session** in the `najaah-frontend` repo.

**Parallel execution:**
- **Stream 1** (Admin: FE-0C → FE-0D → FE-5B) — changes to existing admin pages
- **Stream 2** (Portal: FE-P1 → FE-P6) — new `(portal)` route group with custom design

Both streams use worktree agents on separate branches, running in parallel. No cross-stream dependencies.

**Reference docs** (read from backend repo):
- API contract: `/Users/tarekbassiouny/projects/najaah-backend/docs/feature/student-parent-web-portal-api.md`
- This plan: `/Users/tarekbassiouny/projects/najaah-backend/docs/feature/web-portal-frontend-plan.md`

**Auth note:** The entire app uses JWT (no Sanctum). Admin and portal share the same JWT token infrastructure with different storage keys and API endpoints.

---

## Overview

With all 7 backend phases complete, the frontend work covers **two independent streams**:

1. **Admin Frontend** — Parent management UI in the existing Next.js admin panel
2. **Web Portal Frontend** — New student + parent portal SPA (same Next.js app, separate route group)

---

## Architecture Decisions

### FD-1: Single Next.js App, Separate Route Groups

The web portal lives in the **same Next.js app** as the admin panel but under a separate route group with its own layout, auth context, and navigation.

```
src/app/
├── (auth)/           # Admin auth (existing)
├── (dashboard)/      # Admin panel (existing)
├── (portal)/         # NEW — Web portal route group
│   ├── student/      # Student portal pages
│   └── parent/       # Parent portal pages
└── landing/          # Public landing pages (existing)
```

**Why same app:** Shared UI components, shared HTTP client infrastructure, shared i18n, shared build pipeline. The portal is a different **audience** but the same **product**.

### FD-2: Separate Auth Context for Portal

The entire app uses JWT authentication. Admin and portal use different JWT guards and API endpoints but the same token storage/refresh infrastructure:

- `AuthContext` (existing) — admin JWT auth (`/api/v1/admin/auth/*`)
- `PortalAuthContext` (new) — portal JWT auth (`/api/v1/web/auth/student/*` or `/api/v1/web/auth/parent/*`)

Both share `token-storage.ts` and `token-refresh.ts` patterns but with different storage keys and API endpoints.

### FD-3: Portal HTTP Client

A separate Axios instance for portal requests:

- Base URL: same API backend
- Headers: `X-Api-Key` (center), `Authorization: Bearer {portal_token}`, `X-Locale`
- Token refresh: uses `/api/v1/web/auth/student/refresh` or `/api/v1/web/auth/parent/refresh`
- Same JWT pattern as admin, different guard and endpoints

### FD-4: Center Resolution

The portal is center-scoped. Center resolution:

- **Branded centers**: subdomain `{slug}.najaah.me` → resolve center via `GET /api/v1/resolve/centers/{slug}`
- **Unbranded centers**: `najaah.me` → show center picker, then store `center_id` in context

Once resolved, center info (id, api_key, name, branding) is stored in `PortalTenantContext`.

### FD-5: Role Switching

A user who is both student and parent has two separate JWT sessions. The portal provides a role switcher:

- Student login → student portal
- Parent login → parent portal
- Both tokens can coexist in storage (different keys)
- UI shows a "Switch to Parent/Student view" link when the user has both roles

---

## Stream 1: Admin Frontend (3 Phases)

### Phase FE-0C — Settings Feature Card Verification

**Depends on:** Phase 0B (PR #78 already submitted)

**Tasks:**

FE-0C.1. Merge Phase 0B PR #78 to dev
FE-0C.2. Verify existing features render correctly in new card layout:
  - `guest_browsing` group: `allow_guest_browsing` + `force_disable_guest_browsing` + flag toggle
  - `pdf_downloads` group: `pdf_download_permission` + `force_disable_pdf_download` + flag toggle
  - `codes_access` group: `video_code_expiry_days` + `requires_video_approval` + flag toggle
  - `whatsapp_bulk` group: flag-only card (no center settings)
  - `ai_content` group: flag-only card
FE-0C.3. Verify center admin view: cards hidden when flags off, no flag toggles visible
FE-0C.4. Verify system admin manage view: flag toggles + settings + limits in unified cards

**Output:** All existing features use the new card pattern. Ready for new web portal settings.

---

### Phase FE-0D — Web Portal Settings Cards

**Depends on:** Phase FE-0C

**Tasks:**

FE-0D.1. Verify new web portal feature groups auto-render from backend catalog:
  - `web_portal` group: `allow_web_access`, `allow_web_playback`, `web_device_limit` + flags + limits
  - `parent_portal` group: `allow_parent_portal` + flag
FE-0D.2. Add i18n translations for new setting keys:
  - `allow_web_access`, `allow_web_playback`, `web_device_limit`, `allow_parent_portal`
  - `force_disable_web_access`, `force_disable_web_playback`, `force_disable_parent_portal`
  - `max_web_device_limit`
  - Feature flag labels: `web_access`, `web_playback`, `parent_portal`
FE-0D.3. Verify `depends_on` cascade: disabling `allow_web_access` visually grays out `allow_web_playback`
FE-0D.4. Test save flow: all new settings save correctly via existing settings API

**Output:** Web portal settings manageable from admin panel before portal SPA is built.

---

### Phase FE-5B — Admin Parent Management UI

**Depends on:** Backend Phase 5A (complete), Phase FE-0C

**Tasks:**

FE-5B.1. Feature module: `src/features/parents/`
```
parents/
├── types/parent.ts              # Parent, ParentLink, ParentLinkStatus types
├── services/parents.service.ts  # API calls (list, show, create-link, update-link, pending)
├── hooks/use-parents.ts         # React Query hooks
└── components/
    ├── ParentsTable.tsx          # Paginated parent list with search
    ├── ParentDetailPanel.tsx     # Parent info + linked students
    ├── LinkManagementDialog.tsx  # Create link (select parent + student)
    ├── PendingRequestsQueue.tsx  # Approve/reject pending link requests
    └── StudentParentLinks.tsx    # Parent links section on student detail page
```

FE-5B.2. Page: Parent list page
  - Route: `/centers/[centerId]/parents`
  - Paginated table: parent name, phone, linked students count
  - Search by name or phone
  - Click row → parent detail panel

FE-5B.3. Page: Parent detail
  - Route: `/centers/[centerId]/parents/[parentId]`
  - Parent info (name, phone, registration date)
  - Linked students table (student name, link status, link method, linked date)
  - Actions: revoke link, create new link

FE-5B.4. Page: Pending link requests
  - Route: `/centers/[centerId]/parents/pending`
  - Queue of `PendingApproval` links
  - Approve / Reject actions per link
  - Auto-refresh on action

FE-5B.5. Component: Student parent links (on student detail page)
  - Show linked parents in a card on existing student detail page
  - API: `GET /api/v1/admin/students/{student}/parent-links`
  - Add parent link button → dialog to select parent user

FE-5B.6. Sidebar: Add "Parents" menu item
  - Under center admin section, after "Students"
  - Capability: `manage_parents` → maps to permission `parents.view`
  - Badge on sidebar item showing pending request count (optional, can defer)

FE-5B.7. Capabilities: Add to `capabilities.ts`
  - `view_parents` → `parents.view`
  - `manage_parents` → `parents.manage`

FE-5B.8. i18n: Add translations
  - Page titles, table headers, action buttons, status labels
  - `ParentLinkStatus` labels: Active, Pending Approval, Revoked
  - `ParentLinkMethod` labels: Admin Managed, Auto Matched, Parent Requested

FE-5B.9. Types:
```typescript
interface Parent {
  id: number;
  name: string;
  phone: string;
  is_parent: boolean;
  is_student: boolean;
  created_at: string;
  linked_students_count: number;
}

interface ParentLink {
  id: number;
  parent_user_id: number;
  student_user_id: number;
  center_id: number;
  status: 'active' | 'pending_approval' | 'revoked';
  link_method: 'admin_managed' | 'auto_matched' | 'parent_requested';
  linked_at: string;
  parent?: Parent;
  student?: Student;
}
```

**Output:** Admins can list parents, view details, manage links, and handle pending requests.

---

## Stream 2: Web Portal SPA (6 Phases)

### Phase FE-P1 — Portal Scaffold & Auth

**Depends on:** Backend Phase 2 (complete)

**Tasks:**

FE-P1.1. Route group: `src/app/(portal)/layout.tsx`
  - Portal-specific layout: clean header (center logo, nav, user menu), no admin sidebar
  - RTL support (existing i18n infrastructure)
  - Responsive: mobile-first design (students will use phones)
  - Dark/light mode (existing `next-themes`)

FE-P1.2. Portal auth context: `src/features/portal-auth/`
```
portal-auth/
├── context/portal-auth-context.tsx    # PortalAuthProvider + usePortalAuth()
├── services/portal-auth.service.ts    # API calls to /api/v1/web/auth/*
├── hooks/
│   ├── use-student-login.ts           # Send OTP, verify, refresh
│   ├── use-parent-login.ts            # Register, send OTP, verify, refresh
│   └── use-portal-logout.ts           # Logout (platform-scoped)
├── lib/
│   ├── portal-token-storage.ts        # Separate storage keys from admin
│   ├── portal-token-refresh.ts        # Refresh with web auth endpoints
│   └── portal-http.ts                 # Axios instance for portal
└── components/
    ├── PortalRouteGuard.tsx            # Requires portal auth, redirects to login
    ├── StudentLoginForm.tsx            # Phone + OTP two-step form
    ├── ParentLoginForm.tsx             # Phone + OTP (returning) or register (new)
    ├── ParentRegisterForm.tsx          # Name + phone + OTP registration
    └── OtpInput.tsx                    # 4-6 digit OTP input component
```

FE-P1.3. Portal tenant context: `src/features/portal-tenant/`
  - Resolve center from subdomain or URL parameter
  - Store center info (id, api_key, name, logo, primary_color)
  - Apply center branding (logo in header, primary color theme)
  - Provide `usePortalTenant()` hook

FE-P1.4. Auth pages:
  - `/student/login` — phone input → OTP verify → redirect to dashboard
  - `/parent/login` — phone input → OTP verify → redirect to dashboard
  - `/parent/register` — name + phone → OTP verify → redirect to dashboard
  - `/select-role` — shown when user has both student + parent roles

FE-P1.5. Portal HTTP client: `portal-http.ts`
  - Separate Axios instance
  - Injects `X-Api-Key` from portal tenant context
  - Injects `Authorization: Bearer {portal_token}`
  - 401 handler: attempt refresh, then redirect to portal login
  - Same JWT pattern as admin HTTP client, different storage keys and refresh endpoints

FE-P1.6. Portal header component:
  - Center logo (from branding)
  - Navigation: Home, My Courses (student) or My Students (parent)
  - User menu: profile, switch role (if dual-role), logout
  - Language toggle
  - Mobile hamburger menu

FE-P1.7. Portal route guard:
  - Checks portal auth token exists
  - Validates via `/me` endpoint
  - Redirects unauthenticated users to login
  - Student guard: checks `is_student` on user
  - Parent guard: checks `is_parent` on user

**Output:** Working auth flow, center branding, portal shell with header + guards.

---

### Phase FE-P2 — Student Portal: Course Discovery

**Depends on:** Phase FE-P1

**Tasks:**

FE-P2.1. Feature module: `src/features/portal-courses/`
```
portal-courses/
├── types/course.ts
├── services/portal-courses.service.ts
├── hooks/
│   ├── use-explore-courses.ts
│   ├── use-enrolled-courses.ts
│   ├── use-course-detail.ts
│   └── use-course-assets.ts
└── components/
    ├── CourseCard.tsx                # Course thumbnail, title, instructor, progress
    ├── CourseGrid.tsx                # Responsive grid of course cards
    ├── CourseDetail.tsx              # Full course view with sections/assets
    ├── CourseAssetList.tsx           # Videos, PDFs, quizzes, assignments
    ├── AssetItem.tsx                 # Individual asset row (type icon, title, progress)
    ├── CourseFilters.tsx             # Category, instructor, search filters
    └── EnrolledByInstructor.tsx      # Grouped view
```

FE-P2.2. Pages:
  - `/student/` — Dashboard: enrolled courses grid + weekly activity summary
  - `/student/explore` — Browse available courses with filters
  - `/student/courses/[centerId]/[courseId]` — Course detail with sections and assets
  - `/student/enrolled` — All enrolled courses (list/grid toggle)

FE-P2.3. Components:
  - Course card: thumbnail, title, instructor name, progress bar, center badge
  - Course detail: sections accordion, asset list per section
  - Explore filters: category dropdown, instructor dropdown, search input
  - Empty states: no enrolled courses, no results found

FE-P2.4. Search page:
  - `/student/search` — Full-text search across courses
  - API: `GET /api/v1/web/search`

FE-P2.5. Center browsing (unbranded only):
  - `/student/centers` — Center list (only shown for unbranded portal)
  - `/student/centers/[centerId]` — Center detail with categories

**Output:** Students can browse, search, and view courses with their content structure.

---

### Phase FE-P3 — Student Portal: Content Consumption

**Depends on:** Phase FE-P2

**Tasks:**

FE-P3.1. Feature module: `src/features/portal-playback/`
```
portal-playback/
├── services/portal-playback.service.ts
├── hooks/use-playback.ts
└── components/
    ├── VideoPlayer.tsx              # Bunny Stream iframe/embed player
    ├── PlaybackControls.tsx         # Play, progress bar, view count
    ├── ViewLimitBadge.tsx           # "2 of 5 views used"
    └── PlaybackBlockedOverlay.tsx   # Reason: limit reached, device issue, etc.
```

FE-P3.2. Video playback flow:
  1. User clicks video asset → `POST /request_playback` → get signed Bunny URL
  2. Embed Bunny player with signed URL
  3. Periodically report progress → `POST /playback_progress`
  4. On close/navigate away → `POST /close_session`
  5. Token refresh: `POST /refresh_token` before expiry

FE-P3.3. PDF viewer:
  - `GET /pdfs/{pdf}/signed-url` → open in new tab or embedded viewer
  - Respect `pdf_download_permission` setting (hide download button when disabled)

FE-P3.4. Feature module: `src/features/portal-quizzes/`
```
portal-quizzes/
├── services/portal-quizzes.service.ts
├── hooks/use-quiz-attempt.ts
└── components/
    ├── QuizStart.tsx               # Quiz info + start button
    ├── QuizQuestion.tsx            # Question display + answer selection
    ├── QuizNavigation.tsx          # Question numbers, previous/next
    ├── QuizSubmitConfirm.tsx       # "Are you sure?" dialog
    └── QuizResults.tsx             # Score, pass/fail, answer review
```

FE-P3.5. Quiz flow:
  1. Start attempt → `POST /assets/quiz/{quiz}/attempts`
  2. Display questions one at a time or paginated
  3. Save answers → `POST /attempts/{attempt}/answers`
  4. Submit → `POST /attempts/{attempt}/submit`
  5. View results → `GET /attempts/{attempt}/results`

FE-P3.6. Feature module: `src/features/portal-assignments/`
```
portal-assignments/
├── services/portal-assignments.service.ts
├── hooks/use-assignment-submission.ts
└── components/
    ├── AssignmentDetail.tsx         # Instructions, deadline, status
    ├── SubmissionForm.tsx           # File upload + text input
    ├── FileUploader.tsx             # Drag-and-drop file upload
    ├── SubmissionStatus.tsx         # Draft, submitted, graded
    └── AssignmentGroups.tsx         # Group join/leave/create
```

FE-P3.7. Assignment flow:
  1. View assignment details
  2. Create submission draft → `POST /assets/assignment/{id}/submission`
  3. Upload files → `POST /submissions/{id}/files`
  4. Submit → `POST /submissions/{id}/submit`
  5. View feedback when graded

FE-P3.8. Learning asset progress tracking:
  - `POST /assets/{type}/{id}/progress` — called automatically on view/completion
  - Progress bar on each asset in course detail view

FE-P3.9. Requests:
  - Extra view request: modal when view limit reached
  - Video access request: when access approval is required
  - Video code redemption: code input dialog
  - Enrollment request: button on course detail when not enrolled

FE-P3.10. Surveys:
  - `/student/surveys` — List assigned surveys
  - Survey completion form
  - API: `GET/POST /api/v1/web/surveys/*`

**Output:** Full content consumption — videos, PDFs, quizzes, assignments, surveys.

---

### Phase FE-P4 — Student Portal: Profile & Education

**Depends on:** Phase FE-P1

**Tasks:**

FE-P4.1. Feature module: `src/features/portal-profile/`
```
portal-profile/
├── services/portal-profile.service.ts
├── hooks/use-portal-profile.ts
└── components/
    ├── ProfilePage.tsx             # Name, phone, education info
    ├── EditProfileForm.tsx         # Update name
    ├── EducationForm.tsx           # Grade, school/college selection
    └── WeeklyActivityChart.tsx     # Activity visualization
```

FE-P4.2. Profile page:
  - `/student/profile` — View and edit profile
  - Education update: grade/school/college dropdowns from lookup APIs
  - Weekly activity chart (reuse chart library: recharts or apexcharts)

FE-P4.3. Education lookups:
  - `GET /centers/{center}/education` — education config
  - `GET /centers/{center}/grades` — grade list
  - `GET /centers/{center}/schools` — school list
  - `GET /centers/{center}/colleges` — college list

**Output:** Students can manage their profile and education information.

---

### Phase FE-P5 — Parent Portal

**Depends on:** Phase FE-P1

**Tasks:**

FE-P5.1. Feature module: `src/features/portal-parent/`
```
portal-parent/
├── types/parent-portal.ts
├── services/portal-parent.service.ts
├── hooks/
│   ├── use-linked-students.ts
│   ├── use-student-enrollments.ts
│   ├── use-student-progress.ts
│   ├── use-quiz-results.ts
│   ├── use-assignment-status.ts
│   └── use-weekly-activity.ts
└── components/
    ├── StudentSelector.tsx          # Switch between linked students
    ├── StudentDashboard.tsx         # Selected student overview
    ├── EnrollmentList.tsx           # Student's enrolled courses
    ├── CourseProgress.tsx           # Progress per learning asset
    ├── QuizAttemptsList.tsx         # Quiz attempts with scores
    ├── QuizAttemptDetail.tsx        # Full Q&A review
    ├── AssignmentStatusList.tsx     # Assignment submissions
    ├── WeeklyActivityChart.tsx      # Activity visualization
    ├── LinkRequestForm.tsx          # Request link to new student
    └── LinkRequestsList.tsx         # Pending link requests
```

FE-P5.2. Pages:
  - `/parent/` — Dashboard: linked students grid
  - `/parent/students/[studentId]` — Student detail: enrollments overview
  - `/parent/students/[studentId]/courses/[courseId]` — Course progress detail
  - `/parent/students/[studentId]/courses/[courseId]/quizzes` — Quiz attempts
  - `/parent/students/[studentId]/courses/[courseId]/assignments` — Assignment status
  - `/parent/students/[studentId]/activity` — Weekly activity
  - `/parent/links` — Manage link requests

FE-P5.3. Student selector:
  - Dropdown or card selector for linked students
  - Persisted in URL (student ID in path)
  - Show student name + center name

FE-P5.4. Course progress view:
  - Learning assets with completion status (video watched %, quiz score, assignment grade)
  - Progress bars per section

FE-P5.5. Quiz review:
  - Attempt list with scores and pass/fail badges
  - Click attempt → full question/answer review
  - Show correct answers (respects `show_correct_answers` quiz setting)

FE-P5.6. Link management:
  - Request link: enter student phone + select center
  - View pending requests with status
  - Rate limiting UX: show "please wait" if throttled (429)

**Output:** Parents can monitor all linked students' academic progress.

---

### Phase FE-P6 — Polish & Testing

**Depends on:** All FE-P phases

**Tasks:**

FE-P6.1. Responsive design audit:
  - All portal pages work on mobile, tablet, desktop
  - Touch-friendly controls for quiz answers and file uploads

FE-P6.2. Loading states:
  - Skeleton loaders on all pages (using existing `skeleton.tsx`)
  - Optimistic updates where appropriate (quiz answers)

FE-P6.3. Error handling:
  - Portal-specific error codes (WEB_ACCESS_DISABLED, PARENT_PORTAL_DISABLED, etc.)
  - Friendly error pages: "Web access is not enabled for this center"
  - Network error recovery with retry

FE-P6.4. i18n completion:
  - All portal strings in `en.json` and `ar.json`
  - RTL layout verified for all pages

FE-P6.5. Testing:
  - Unit tests for hooks and services (Vitest + MSW)
  - Integration tests for auth flows
  - E2E tests (Playwright): login → browse → play video → take quiz → submit assignment
  - E2E: parent login → view student → check progress → review quiz

FE-P6.6. Accessibility:
  - Keyboard navigation for quiz flow
  - ARIA labels on interactive elements
  - Screen reader support for progress indicators

FE-P6.7. Performance:
  - Code splitting per portal section (dynamic imports)
  - Image optimization for course thumbnails
  - Prefetch next page data on hover

**Output:** Production-ready portal with full test coverage.

---

## Phase Execution Order

```
Stream 1 (Admin):                    Stream 2 (Portal):

FE-0C (Settings verify)
  │
FE-0D (Web portal settings)          FE-P1 (Scaffold & Auth)
  │                                    │
FE-5B (Parent management UI)         FE-P2 (Course Discovery)
                                       │
                                     FE-P3 (Content Consumption)  ←── can parallel with FE-P4/P5
                                       │
                                     FE-P4 (Profile & Education)  ←── can parallel with FE-P5
                                       │
                                     FE-P5 (Parent Portal)
                                       │
                                     FE-P6 (Polish & Testing)
```

**Parallelization opportunities:**
- Stream 1 and Stream 2 are fully independent
- FE-P4 (Profile) and FE-P5 (Parent Portal) can run in parallel (disjoint components)
- FE-P3 (Content) can start while FE-P4/P5 are in progress

---

## Key Dependencies

| Phase | Depends On | Backend API Ready |
|-------|-----------|-------------------|
| FE-0C | Phase 0B PR #78 | Yes (Phase 0A) |
| FE-0D | FE-0C | Yes (Phase 1 catalog entries) |
| FE-5B | FE-0C + backend Phase 5A | Yes (Phase 5A) |
| FE-P1 | Backend Phase 2 | Yes |
| FE-P2 | FE-P1 + backend Phase 3 | Yes |
| FE-P3 | FE-P2 + backend Phase 3 | Yes |
| FE-P4 | FE-P1 + backend Phase 3 | Yes |
| FE-P5 | FE-P1 + backend Phase 4 | Yes |
| FE-P6 | All FE-P phases | Yes |

All backend APIs are complete — no blockers from backend side.

---

## File Count Estimates

| Phase | New Files | Modified Files |
|-------|----------|----------------|
| FE-0C | 0 | 2-3 (verification only) |
| FE-0D | 0 | 2-3 (i18n, verification) |
| FE-5B | ~12 | ~5 (sidebar, capabilities, student detail) |
| FE-P1 | ~15 | ~3 (layout, i18n) |
| FE-P2 | ~12 | ~2 |
| FE-P3 | ~20 | ~2 |
| FE-P4 | ~6 | ~1 |
| FE-P5 | ~14 | ~2 |
| FE-P6 | ~15 (tests) | ~10 (polish) |
| **Total** | **~94** | **~30** |

---

## API Contract Reference

Full endpoint documentation: [`docs/feature/student-parent-web-portal-api.md`](student-parent-web-portal-api.md)
