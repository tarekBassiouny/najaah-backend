# AI_INSTRUCTIONS.md – Najaah LMS (Master System Rules)
### Version 1.0 – Strict Instruction Set for AI Models

These instructions define how the Najaah Learning Management System works and how AI-generated code, schema, or logic must be written.  
All responses MUST follow these rules unless explicitly overridden.

---

# 1. Core System Model

## 1.1 Multi-Center Architecture  
- The platform hosts multiple centers (colleges, universities, training centers).  
- Two center types exist:
  - **Branded centers** → own subdomain, unique students per center.
  - **Unbranded centers** → under Najaah org, shared student identity.
- All centers share the same database using `center_id`.

---

# 2. User & Role Rules

## 2.1 Roles
- **Super Admin** → full access to all centers and global operations.
- **Center Owner** → full access to their center.
- **Center Admin** → manages students, enrollments, devices, view limits, courses.
- **Content Manager** → manages courses, sections, videos, PDFs only.
- **Student** → consumes content via mobile app (Flutter) or limited web.

## 2.2 Identity Rules
- Branded centers → students are **isolated**, separate accounts.
- Unbranded centers → students belong to the central Najaah org and can access all unbranded centers.

---

# 3. Authentication Rules

## 3.1 Admin & Super Admin (Web + Next.js)
- MUST use **Laravel Sanctum SPA authentication**.
- Login via email + password.
- Session cookies must be HttpOnly and secure.

## 3.2 Student (Mobile)
- MUST use **JWT authentication** (access token + refresh token).
- Access tokens are short-lived (15–60 minutes).
- Refresh tokens are long-lived (30–90 days).

## 3.3 OTP
- Students log in using phone + OTP.
- OTP provider is abstract; do not hardcode Twilio, Firebase, etc.

---

# 4. Device Binding Rules (Critical)

- Each student is allowed **one active device**.
- On first login, bind:
  - device_id  
  - model  
  - OS version  
- If student logs into a new device:
  - Block all playback.
  - Require admin approval for device change.
- Previous device must be stored as `REVOKED`, not deleted.

---

# 5. Course Model Rules

## 5.1 Structure
- Strict hierarchy:
  **Course → Sections → Videos/PDFs**
- No "lesson" middle layer.

## 5.2 Course Metadata
- Title  
- Description  
- Instructor  
- College/University  
- Grade year  
- Tags (type/module/etc.)  
- Status (integer enum):
  - 0 DRAFT  
  - 1 UPLOADING  
  - 2 READY  
  - 3 PUBLISHED  
  - 4 ARCHIVED  

## 5.3 Course Cloning
- FULL clone supported (sections, videos, PDFs).
- Admin may optionally choose which sections/videos to include.

---

# 6. Content Rules

## 6.1 Sections
- Fields: title, order_index, description, visible (bool).
- Can be hidden without deletion.

## 6.2 Videos
- MUST be stored in Bunny Stream.
- Najaah LMS stores **metadata only**.
- A video may belong to multiple courses.
- Tags identify types (intro, part 1, Q&A, etc.).
- Lifecycle statuses (integer enum):
  - uploading
  - ready
  - published
  - archived
  - deleted

## 6.3 PDFs
- Stored in backend storage (e.g., S3).
- Download permission controlled by settings.
- Mobile clients request short-lived signed storage URLs via:
  `/api/v1/centers/{center}/courses/{course}/pdfs/{pdf}/signed-url`

---

# 7. Video Playback & Security Rules

## 7.1 Playback Authorization
Before providing video URL, backend MUST verify:
- Device is valid  
- Student identity  
- Enrollment status  
- View limit not exceeded  
- No simultaneous playback  
- Video lifecycle allows playback  

## 7.2 Bunny URL Signing
- App must request: `/api/v1/videos/{id}/play`
- Backend checks rules → generates **short-lived signed Bunny URL**
- Bunny credentials must NEVER be exposed to clients.

## 7.3 PDF Access & Signing
- Backend MUST verify enrollment and permissions before issuing URLs.
- PDFs use storage signed URLs (Spaces/S3), not Bunny.

## 7.4 View Limits
- Default limit: **2 full plays**.  
- A "full play" = **≥ 95% watched**.
- Students may request extra views.
- Admin approves extra plays PER video PER student.
- Final limit = `default|override + extra`.

## 7.5 Concurrency Rules
- Block:
  - Web + mobile simultaneous playback.
  - Two mobile devices.
- Playback concurrency enforced server-side.

---

# 8. Settings Hierarchy

Override priority (strict):

> **Student > Video > Course > Center**

### 8.1 Center Settings
- Default view limit  
- Allow extra view requests (yes/no)  
- PDF download permission  
- Device limit  
- Branding settings (logo, primary color)

### 8.2 Course Settings  
- Overrides center settings (view limit, PDFs)

### 8.3 Video Settings  
- Overrides course settings (special cases)

### 8.4 Student Settings  
- Extra views  
- Device-specific overrides  

---

# 9. Enrollment Rules

- Enrollment is **admin-driven** (no self-enrollment in MVP).
- Status states:
  - ACTIVE
  - DEACTIVATED
  - CANCELLED
- Students can see courses grouped by center.

---

# 10. Database Standards (Critical)

## 10.1 Primary Keys
- All tables MUST use:
  **BIGINT UNSIGNED AUTO_INCREMENT `id`**

## 10.2 Soft Deletes
- MUST be enabled on **all tables**.

## 10.3 Foreign Keys
- Naming style MUST be snake_case:
  - `center_id`
  - `course_id`
  - `student_id`
- MUST be indexed.

## 10.4 Timestamps
- All tables require:
  `created_at`, `updated_at`, `deleted_at`.

## 10.5 Status Columns
- All statuses MUST be stored as **integer codes**.
- Enums/Constants MUST define meanings.

## 10.6 Indexing Rules
- Index ALL foreign keys.
- Index ALL slug fields.
- Index `deleted_at`.

---

# 11. Backend Architecture (Laravel)

## 11.1 Architecture Pattern
Must follow:

```
Controller → Action/Manager → Service → Model
```

### Controllers:
- MUST remain thin.
- MUST NOT contain business logic.

### Form Requests:
- MUST handle all validation.
- MUST enforce strong typing.

### Actions/Managers:
- Orchestration layer for multi-step operations.

### Services:
- Contain business logic only.
- Services talk directly to Eloquent models (no repositories).

### Models:
- Contain **relationships + casts only**.
- NO business logic.

### API Resources:
- MUST be used for API responses.
- Direct arrays are not allowed.

---

# 12. API Standards

## 12.1 Versioning
All endpoints MUST be under:

```
/api/v1/
```

## 12.2 Unified Success Response
```
{
  "success": true,
  "message": "Operation completed",
  "data": {...}
}
```

## 12.3 Unified Error Response
```
{
  "success": false,
  "error": {
    "code": "SOME_CODE",
    "message": "Readable error message"
  }
}
```

## 12.4 Pagination Format
```
{
  "success": true,
  "data": [...],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 120
  }
}
```

---

# 13. Admin Frontend (Next.js + Tailwind)

## 13.1 Stack
- Next.js (App Router)
- React
- Tailwind CSS
- Axios (HTTP)
- React Query (API state)

## 13.2 Hybrid Folder Structure (MANDATORY)
```
/app
/features
/components
/services
/utils
/types
```

## 13.3 Auth
- Must use Sanctum SPA mode.
- No JWT for admin.

---

# 14. Mobile App Rules (Flutter)

- JWT access + refresh token flow.
- Token refresh endpoint must exist.
- Device binding checks must occur before playback.
- No video downloads allowed.
- Only metadata may be cached offline.
- Video playback uses signed short-lived Bunny URL.

---

# 15. Auditing Rules

### MUST log:
- Device change actions
- Extra view approvals
- Video playback events
- Enrollment changes
- Course/section/video create/update/publish
- PDF access
- Authentication events (optional)

Logs must be **permanent** (soft deletable but not physically removed).

---

# 16. Response Expectations for AI

### When generating database schema:
- MUST follow schema rules above.
- MUST include center_id where relevant.
- MUST use BIGINT, timestamps, soft deletes.

### When generating models:
- MUST contain relationships only.

### When generating controllers:
- MUST call Actions/Managers.
- MUST use Form Requests.

### When generating services:
- MUST implement business rules from this file.

### When generating APIs:
- MUST follow unified API rules.
- MUST follow Auth and Video Security rules.

### When generating frontend:
- MUST follow Next.js + Tailwind + React Query patterns.
