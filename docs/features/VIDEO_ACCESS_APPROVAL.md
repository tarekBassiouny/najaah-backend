# Video Access Approval System

> Per-video access control where students need a code to watch individual videos after enrollment. All access is tracked via codes for billing purposes.

## Overview

The video access approval system provides granular control over which videos students can access within enrolled courses:
- Configurable at center level via `requires_video_approval` setting
- **Unified code-based access**: ALL access requires code redemption
- Two paths: Student request (admin approves → code generated) or Admin generates code directly
- QR-scannable codes for physical classroom scenarios
- **Billing**: Generated codes = billable units (count × price per view)

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│ UNIFIED FLOW (when requires_video_approval = true)              │
│ ALL ACCESS REQUIRES CODE REDEMPTION                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Student enrolls → Videos LOCKED → 2 paths to UNLOCK:           │
│                                                                 │
│  ┌──────────────────────────────┐  ┌──────────────────────────┐ │
│  │ PATH A: Student-Initiated    │  │ PATH B: Admin-Initiated  │ │
│  ├──────────────────────────────┤  ├──────────────────────────┤ │
│  │ 1. Student requests access   │  │ 1. Admin generates code  │ │
│  │           ↓                  │  │           ↓              │ │
│  │ 2. Admin approves request    │  │ 2. (Optional) Send via   │ │
│  │           ↓                  │  │    WhatsApp              │ │
│  │ 3. Code AUTO-GENERATED       │  │           ↓              │ │
│  │           ↓                  │  │ 3. Student redeems code  │ │
│  │ 4. (Optional) Send via       │  │           ↓              │ │
│  │    WhatsApp                  │  │ 4. VideoAccess created   │ │
│  │           ↓                  │  │                          │ │
│  │ 5. Student redeems code      │  │                          │ │
│  │           ↓                  │  │                          │ │
│  │ 6. VideoAccess created       │  │                          │ │
│  └──────────────────────────────┘  └──────────────────────────┘ │
│                                                                 │
│  BILLING: Invoice = COUNT(redeemed codes) × price_per_view      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ EXISTING FLOW (when requires_video_approval = false/unset)      │
├─────────────────────────────────────────────────────────────────┤
│  Student enrolls → ALL videos UNLOCKED (current behavior)       │
└─────────────────────────────────────────────────────────────────┘
```

### Key Principle: Code = Billable Unit

Every video access MUST have an associated code because:
1. **Billing accuracy**: Codes are the single source of truth for invoicing
2. **Audit trail**: Every access is traceable to a specific code
3. **Consistency**: Same redemption flow for all access types

---

## UI/UX Design

### Mobile App (Student View)

#### Course Videos List
When `requires_video_approval` is enabled for a center:

```
┌─────────────────────────────────────────────┐
│ Course: Mathematics 101                     │
├─────────────────────────────────────────────┤
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ ▶ Lecture 1: Introduction      [UNLOCKED]│ │
│ │   Duration: 45 min                       │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ 🔒 Lecture 2: Basic Concepts   [LOCKED] │ │
│ │   Duration: 50 min                       │ │
│ │   ┌─────────────────────────────────┐   │ │
│ │   │ [Request Access] [Enter Code]   │   │ │
│ │   └─────────────────────────────────┘   │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ ⏳ Lecture 3: Advanced Topics  [PENDING]│ │
│ │   Duration: 55 min                       │ │
│ │   Request submitted • Awaiting approval  │ │
│ └─────────────────────────────────────────┘ │
│                                             │
└─────────────────────────────────────────────┘
```

**Video States:**
| State | Icon | Actions Available |
|-------|------|-------------------|
| Unlocked | ▶ | Play video |
| Locked | 🔒 | "Request Access" button, "Enter Code" button |
| Pending | ⏳ | View request status, Cancel request (optional) |
| Approved | ✅ | "Enter Code" button (code was generated, awaiting redemption) |
| Rejected | ❌ | "Request Again" button (after cooldown?) |

**Note:** "Approved" state means admin approved the request and a code was generated. Student must redeem the code to unlock.

#### Request Access Modal
```
┌─────────────────────────────────────────────┐
│          Request Video Access               │
├─────────────────────────────────────────────┤
│                                             │
│  Video: Lecture 2: Basic Concepts           │
│                                             │
│  Reason (optional):                         │
│  ┌─────────────────────────────────────┐   │
│  │ I need to complete assignment #3    │   │
│  │                                     │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Cancel    │  │  Submit Request ➤   │  │
│  └─────────────┘  └─────────────────────┘  │
│                                             │
└─────────────────────────────────────────────┘
```

#### Enter Code Modal
```
┌─────────────────────────────────────────────┐
│           Enter Access Code                 │
├─────────────────────────────────────────────┤
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │         A 1 B 2 C 3 D 4             │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │         [📷 Scan QR Code]           │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │          Unlock Video ➤             │   │
│  └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

#### QR Scanner View
```
┌─────────────────────────────────────────────┐
│              Scan QR Code                   │
├─────────────────────────────────────────────┤
│                                             │
│    ┌───────────────────────────────┐       │
│    │                               │       │
│    │      ┌─────────────┐         │       │
│    │      │  QR Target  │         │       │
│    │      │    Area     │         │       │
│    │      └─────────────┘         │       │
│    │                               │       │
│    └───────────────────────────────┘       │
│                                             │
│  Point camera at the QR code provided       │
│  by your instructor                         │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │      [Enter Code Manually]          │   │
│  └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

---

### Admin Dashboard

#### Video Access Requests List
```
┌──────────────────────────────────────────────────────────────────────────┐
│ Video Access Requests                                    [Filter ▼]      │
├──────────────────────────────────────────────────────────────────────────┤
│ Status: [All ▼]  Course: [All ▼]  Date: [Last 7 days ▼]   [🔍 Search]   │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│ ☐ │ Student        │ Video              │ Course      │ Status │ Actions│
│───┼────────────────┼────────────────────┼─────────────┼────────┼────────│
│ ☐ │ Ahmed Hassan   │ Lecture 3          │ Math 101    │ ⏳ Pending│ [✓][✗]│
│ ☐ │ Sara Ali       │ Lecture 5          │ Physics 201 │ ⏳ Pending│ [✓][✗]│
│ ☐ │ Mohamed Khaled │ Lab Tutorial       │ Chemistry   │ ✓ Approved│ View │
│ ☐ │ Fatma Ahmed    │ Lecture 2          │ Math 101    │ ✗ Rejected│ View │
│                                                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ Selected: 2    [✓ Bulk Approve]  [✗ Bulk Reject]                        │
└──────────────────────────────────────────────────────────────────────────┘
```

#### Approve Request Modal (Code Auto-Generated)
```
┌─────────────────────────────────────────────┐
│         Approve Access Request              │
├─────────────────────────────────────────────┤
│                                             │
│  Student: Ahmed Hassan                      │
│  Phone: +20 100 123 4567                    │
│  Video: Lecture 3: Advanced Topics          │
│  Course: Mathematics 101                    │
│  Requested: 2 hours ago                     │
│                                             │
│  Student's reason:                          │
│  "I need to complete assignment #3"         │
│                                             │
│  Decision reason (optional):                │
│  ┌─────────────────────────────────────┐   │
│  │                                     │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ☑ Send code via WhatsApp                   │
│    Format: ○ QR Code  ● Text Code           │
│                                             │
│  ⓘ A code will be auto-generated upon      │
│    approval. Student must redeem code.      │
│                                             │
│  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Cancel    │  │ ✓ Approve & Generate │  │
│  └─────────────┘  └─────────────────────┘  │
│                                             │
└─────────────────────────────────────────────┘
```

#### Approval Success Modal
```
┌─────────────────────────────────────────────┐
│       ✓ Request Approved                    │
├─────────────────────────────────────────────┤
│                                             │
│  Code generated for Ahmed Hassan:           │
│                                             │
│        ┌─────────────────────┐             │
│        │                     │             │
│        │     [QR CODE]       │             │
│        │                     │             │
│        └─────────────────────┘             │
│                                             │
│              A1B2C3D4                       │
│                                             │
│  ✓ WhatsApp sent successfully               │
│                                             │
│  Student must redeem this code to           │
│  unlock the video.                          │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │               Done                   │   │
│  └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

#### Video Access Codes Management
```
┌──────────────────────────────────────────────────────────────────────────┐
│ Video Access Codes                              [+ Generate Codes]       │
├──────────────────────────────────────────────────────────────────────────┤
│ Status: [All ▼]  Course: [All ▼]  Student: [Search...]   [🔍]           │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│ ☐ │ Code     │ Student      │ Video        │ Status   │ Expires │Actions│
│───┼──────────┼──────────────┼──────────────┼──────────┼─────────┼───────│
│ ☐ │ A1B2C3D4 │ Ahmed Hassan │ Lecture 3    │ 🟢 Active │ 7 days  │[📱][🔄]│
│ ☐ │ X9Y8Z7W6 │ Sara Ali     │ Lecture 5    │ ✓ Used   │ -       │ View  │
│ ☐ │ P4Q5R6S7 │ Mohamed K.   │ Lab Tutorial │ 🔴 Revoked│ -       │ View  │
│                                                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ Selected: 1    [📱 Send via WhatsApp]  [🔄 Regenerate]  [🗑 Revoke]      │
└──────────────────────────────────────────────────────────────────────────┘

Legend: [📱] Send WhatsApp  [🔄] Regenerate  [🗑] Revoke
```

#### Generate Code Modal
```
┌─────────────────────────────────────────────┐
│         Generate Access Code                │
├─────────────────────────────────────────────┤
│                                             │
│  Course: [Mathematics 101 ▼]                │
│                                             │
│  Video: [Lecture 3: Advanced Topics ▼]      │
│                                             │
│  Student: [Search student... ▼]             │
│           Ahmed Hassan                      │
│           ✓ Enrolled in course              │
│                                             │
│  ☐ Send immediately via WhatsApp            │
│     Format: ○ QR Code  ○ Text Code          │
│                                             │
│  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Cancel    │  │  Generate Code ➤    │  │
│  └─────────────┘  └─────────────────────┘  │
│                                             │
└─────────────────────────────────────────────┘
```

#### Code Generated Success Modal
```
┌─────────────────────────────────────────────┐
│         ✓ Code Generated                    │
├─────────────────────────────────────────────┤
│                                             │
│        ┌─────────────────────┐             │
│        │                     │             │
│        │     [QR CODE]       │             │
│        │                     │             │
│        └─────────────────────┘             │
│                                             │
│              A1B2C3D4                       │
│                                             │
│  Student: Ahmed Hassan                      │
│  Video: Lecture 3                           │
│  Expires: April 1, 2026                     │
│                                             │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ │
│  │ 📋 Copy   │ │ 📥 Download│ │📱WhatsApp │ │
│  └───────────┘ └───────────┘ └───────────┘ │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │              Done                    │   │
│  └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

#### Send WhatsApp Modal
```
┌─────────────────────────────────────────────┐
│         Send Code via WhatsApp              │
├─────────────────────────────────────────────┤
│                                             │
│  Student: Ahmed Hassan                      │
│  Phone: +20 100 123 4567                    │
│                                             │
│  Code: A1B2C3D4                             │
│  Video: Lecture 3: Advanced Topics          │
│                                             │
│  Send format:                               │
│  ┌─────────────────────────────────────┐   │
│  │ ○ QR Code Image                      │   │
│  │   Sends QR image with caption        │   │
│  ├─────────────────────────────────────┤   │
│  │ ● Text Code                          │   │
│  │   Sends code as text message         │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Cancel    │  │  📱 Send WhatsApp   │  │
│  └─────────────┘  └─────────────────────┘  │
│                                             │
└─────────────────────────────────────────────┘
```

#### Bulk Generate Codes
```
┌─────────────────────────────────────────────┐
│       Bulk Generate Access Codes            │
├─────────────────────────────────────────────┤
│                                             │
│  Course: [Mathematics 101 ▼]                │
│                                             │
│  Video: [Lecture 3: Advanced Topics ▼]      │
│                                             │
│  Students:                                  │
│  ┌─────────────────────────────────────┐   │
│  │ ☑ Ahmed Hassan      +20 100 123 456 │   │
│  │ ☑ Sara Ali          +20 100 789 012 │   │
│  │ ☑ Mohamed Khaled    +20 100 345 678 │   │
│  │ ☐ Fatma Ahmed       (no phone)      │   │
│  └─────────────────────────────────────┘   │
│  Selected: 3 of 4 enrolled students         │
│                                             │
│  ☑ Send immediately via WhatsApp            │
│     Format: ○ QR Code  ● Text Code          │
│                                             │
│  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Cancel    │  │  Generate 3 Codes ➤ │  │
│  └─────────────┘  └─────────────────────┘  │
│                                             │
└─────────────────────────────────────────────┘
```

---

**Note:** Direct grant without code has been removed. All access requires code generation + redemption for billing purposes.

### Notifications

| Event | Recipient | Channel |
|-------|-----------|---------|
| Access request submitted | Admin | Push + In-app |
| Access approved | Student | Push + In-app |
| Access rejected | Student | Push + In-app |
| Code generated | Student (if WhatsApp sent) | WhatsApp |
| Code expiring soon | Student | Push (optional) |

---

## Database Schema

### video_access_requests

Stores student requests for video access.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Requesting student |
| `video_id` | FK → videos | Video requested |
| `course_id` | FK → courses | Course context |
| `center_id` | FK → centers | Center for scoping |
| `enrollment_id` | FK → enrollments | Student's enrollment |
| `status` | tinyint | 0=Pending, 1=Approved, 2=Rejected |
| `reason` | text | Student's reason (optional) |
| `decision_reason` | text | Admin's decision reason |
| `decided_by` | FK → users | Admin who decided |
| `decided_at` | timestamp | When decision was made |
| `created_at` | timestamp | Request creation time |
| `updated_at` | timestamp | Last update |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[user_id, video_id, status]` - Finding pending requests
- `[center_id, status]` - Admin listing
- `[enrollment_id]` - Enrollment-based queries

### video_accesses

Stores granted video access records. **Created only when student redeems a code.**

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Student with access |
| `video_id` | FK → videos | Accessible video |
| `course_id` | FK → courses | Course context |
| `center_id` | FK → centers | Center for scoping |
| `enrollment_id` | FK → enrollments | Student's enrollment |
| `video_access_request_id` | FK → video_access_requests | Source request (nullable - only if from request flow) |
| `video_access_code_id` | FK → video_access_codes | **REQUIRED** - code that was redeemed |
| `granted_at` | timestamp | When code was redeemed |
| `revoked_at` | timestamp | When access was revoked |
| `revoked_by` | FK → users | Admin who revoked |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update |
| `deleted_at` | timestamp | Soft delete |

**Key constraint:** `video_access_code_id` is REQUIRED - all access comes from code redemption.

**Indexes:**
- `UNIQUE [user_id, video_id, course_id]` WHERE deleted_at IS NULL
- `UNIQUE [video_access_code_id]` - One access per code
- `[enrollment_id]` - Enrollment queries
- `[center_id]` - Center-scoped listing

### video_access_codes

Stores unique codes for video access.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Student the code is for |
| `video_id` | FK → videos | Video to unlock |
| `course_id` | FK → courses | Course context |
| `center_id` | FK → centers | Center for scoping |
| `enrollment_id` | FK → enrollments | Student's enrollment |
| `code` | varchar(16) | Unique uppercase alphanumeric |
| `status` | tinyint | 0=Active, 1=Used, 2=Revoked, 3=Expired |
| `generated_by` | FK → users | Admin who generated |
| `generated_at` | timestamp | When code was created |
| `used_at` | timestamp | When code was redeemed |
| `expires_at` | timestamp | Optional expiration |
| `revoked_at` | timestamp | When code was revoked |
| `revoked_by` | FK → users | Admin who revoked |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [code]` - Code lookup
- `[user_id, video_id, status]` - Student-video queries
- `[center_id, status]` - Admin listing

---

## Enums

### VideoAccessRequestStatus

```php
enum VideoAccessRequestStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;
}
```

### VideoAccessCodeStatus

**Note:** `VideoAccessSource` enum removed - all access is via code redemption.

```php
enum VideoAccessCodeStatus: int
{
    case Active = 0;
    case Used = 1;
    case Revoked = 2;
    case Expired = 3;
}
```

### WhatsAppCodeFormat

```php
enum WhatsAppCodeFormat: string
{
    case QrCode = 'qr_code';
    case TextCode = 'text_code';
}
```

---

## Service Layer

### VideoAccessService

**Location:** `app/Services/VideoAccess/VideoAccessService.php`

#### `hasAccess(User $student, Video $video, Course $course): bool`

Checks if student has access to video.

```php
public function hasAccess(User $student, Video $video, Course $course): bool
{
    return VideoAccess::query()
        ->forUserAndVideo($student->id, $video->id)
        ->where('course_id', $course->id)
        ->active()
        ->exists();
}
```

#### `assertAccess(User $student, Video $video, Course $course): void`

Throws exception if student doesn't have access.

```php
public function assertAccess(User $student, Video $video, Course $course): void
{
    if (!$this->hasAccess($student, $video, $course)) {
        throw new DomainException(
            'Video access denied.',
            ErrorCodes::VIDEO_ACCESS_DENIED,
            403
        );
    }
}
```

#### `requiresApproval(Center $center): bool`

Checks if center has video approval enabled.

```php
public function requiresApproval(Center $center): bool
{
    $settings = $center->centerSetting?->settings ?? [];
    return (bool) ($settings['requires_video_approval'] ?? false);
}
```

#### `grant(User $admin, User $student, Video $video, Course $course, Enrollment $enrollment): VideoAccess`

Admin directly grants access to student.

#### `grantAllVideos(User $admin, User $student, Course $course, Enrollment $enrollment): array`

Grants access to all videos in course.

#### `revoke(User $admin, VideoAccess $access): VideoAccess`

Revokes previously granted access.

---

### VideoAccessRequestService

**Location:** `app/Services/VideoAccess/VideoAccessRequestService.php`

#### `create(User $student, Video $video, Course $course, ?string $reason): VideoAccessRequest`

Creates a new access request.

**Validations:**
- User must be a student
- Must have active enrollment
- Video must be in course
- No pending request exists for this video
- Center must have `requires_video_approval` enabled

#### `approve(User $admin, VideoAccessRequest $request, ?string $decisionReason, bool $sendWhatsApp, ?WhatsAppCodeFormat $format): ApprovalResult`

Approves request and **auto-generates code**.

**Flow:**
1. Validate request is PENDING
2. Update request status to APPROVED
3. **Generate VideoAccessCode** (via VideoAccessCodeService)
4. Link code to request
5. If `sendWhatsApp` = true, send code via WhatsApp
6. Return ApprovalResult with request + generated code

**Returns:**
```php
class ApprovalResult {
    public VideoAccessRequest $request;
    public VideoAccessCode $generatedCode;
    public bool $whatsAppSent;
    public ?string $whatsAppError;
}
```

#### `reject(User $admin, VideoAccessRequest $request, ?string $decisionReason): VideoAccessRequest`

Rejects the request.

#### `bulkApprove(User $admin, array $requestIds, ?string $decisionReason): array`

Bulk approve multiple requests.

#### `bulkReject(User $admin, array $requestIds, ?string $decisionReason): array`

Bulk reject multiple requests.

---

### VideoAccessCodeService

**Location:** `app/Services/VideoAccess/VideoAccessCodeService.php`

#### `generate(User $admin, User $student, Video $video, Course $course, Enrollment $enrollment): VideoAccessCode`

Generates a unique code for student-video combination.

```php
public function generate(/* params */): VideoAccessCode
{
    $code = $this->generateUniqueCode(); // 8-char alphanumeric

    return VideoAccessCode::create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'enrollment_id' => $enrollment->id,
        'code' => $code,
        'status' => VideoAccessCodeStatus::Active,
        'generated_by' => $admin->id,
        'generated_at' => now(),
        'expires_at' => $this->calculateExpiry($course->center),
    ]);
}
```

#### `redeem(User $student, string $code): VideoAccess`

Student redeems code to unlock video.

**Validations:**
- Code exists and is Active
- Code belongs to this student
- Code is not expired
- Student has active enrollment

**Flow:**
1. Validate code
2. Mark code as Used
3. Create VideoAccess with source = CodeRedemption
4. Return VideoAccess

#### `validate(string $code): ?VideoAccessCode`

Validates code without redeeming.

#### `regenerate(User $admin, VideoAccessCode $code): VideoAccessCode`

Revokes old code and creates new one.

#### `revoke(User $admin, VideoAccessCode $code): VideoAccessCode`

Revokes an active code.

#### `getQrCodeDataUrl(VideoAccessCode $code): string`

Returns base64 QR code image for the code.

#### `sendViaWhatsApp(VideoAccessCode $code, WhatsAppCodeFormat $format): void`

Sends code to student via WhatsApp.

```php
public function sendViaWhatsApp(VideoAccessCode $code, WhatsAppCodeFormat $format): void
{
    $student = $code->user;
    $phone = $student->phone;

    if (!$phone) {
        throw new DomainException(
            'Student has no phone number.',
            ErrorCodes::STUDENT_NO_PHONE,
            422
        );
    }

    $video = $code->video;

    if ($format === WhatsAppCodeFormat::QrCode) {
        $qrImage = $this->getQrCodeDataUrl($code);
        $this->whatsAppService->sendImage(
            phone: $phone,
            image: $qrImage,
            caption: "Your access code for '{$video->title}': {$code->code}"
        );
    } else {
        $this->whatsAppService->sendText(
            phone: $phone,
            message: "Your access code for '{$video->title}' is: {$code->code}\n\nEnter this code in the app to unlock the video."
        );
    }
}
```

#### `bulkSendViaWhatsApp(array $codeIds, WhatsAppCodeFormat $format): array`

Bulk send codes via WhatsApp. Returns results with success/failure per code.

---

## Playback Integration

### PlaybackAuthorizationService Modification

**Location:** `app/Services/Playback/PlaybackAuthorizationService.php`

Add video access check after enrollment check:

```php
public function assertCanStartPlayback(User $student, Video $video, Course $course): void
{
    // Existing checks...
    $this->assertActiveEnrollment($student, $course);

    // NEW: Video access check
    $center = $course->center;
    if ($this->videoAccessService->requiresApproval($center)) {
        $this->videoAccessService->assertAccess($student, $video, $course);
    }

    // Continue with existing checks...
    $this->assertWithinViewLimit($student, $video, $course);
}
```

---

## API Endpoints

### Admin - Video Access Requests

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/video-access-requests` | List requests with filters |
| POST | `/api/v1/admin/centers/{center}/video-access-requests/{request}/approve` | Approve request → **auto-generates code** |
| POST | `/api/v1/admin/centers/{center}/video-access-requests/{request}/reject` | Reject request |
| POST | `/api/v1/admin/centers/{center}/video-access-requests/bulk-approve` | Bulk approve → **auto-generates codes** |
| POST | `/api/v1/admin/centers/{center}/video-access-requests/bulk-reject` | Bulk reject |

**List Filters:**
- `status` - Filter by status (pending/approved/rejected)
- `user_id` - Filter by student
- `video_id` - Filter by video
- `course_id` - Filter by course
- `date_from`, `date_to` - Date range
- `page`, `per_page` - Pagination

**Approve Request:**
```json
{
    "decision_reason": "Approved for assignment completion",
    "send_whatsapp": true,
    "whatsapp_format": "text_code"
}
```

**Approve Response (code auto-generated):**
```json
{
    "success": true,
    "data": {
        "request": {
            "id": 123,
            "status": "approved",
            "decided_at": "2026-03-05T10:00:00Z"
        },
        "generated_code": {
            "id": 456,
            "code": "A1B2C3D4",
            "qr_code_url": "data:image/png;base64,...",
            "expires_at": "2026-04-05T00:00:00Z",
            "whatsapp_sent": true
        }
    }
}
```

**Bulk Approve Response:**
```json
{
    "success": true,
    "data": {
        "approved": 5,
        "codes_generated": 5,
        "whatsapp_sent": 4,
        "whatsapp_failed": 1,
        "results": [
            { "request_id": 1, "code": "A1B2C3D4", "whatsapp_sent": true },
            { "request_id": 2, "code": "X9Y8Z7W6", "whatsapp_sent": false, "error": "No phone" }
        ]
    }
}
```

### Admin - Video Access (Revoke Only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| DELETE | `/api/v1/admin/centers/{center}/video-accesses/{access}` | Revoke access |

**Note:** Direct grant endpoints removed. All access is via code generation + redemption.

### Admin - Video Access Codes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/video-access-codes` | List codes with filters |
| POST | `/api/v1/admin/centers/{center}/students/{student}/video-access-codes` | Generate code |
| POST | `/api/v1/admin/centers/{center}/video-access-codes/bulk` | Bulk generate |
| GET | `/api/v1/admin/centers/{center}/video-access-codes/{code}` | Get code details + QR |
| POST | `/api/v1/admin/centers/{center}/video-access-codes/{code}/regenerate` | Regenerate code |
| POST | `/api/v1/admin/centers/{center}/video-access-codes/{code}/revoke` | Revoke code |
| POST | `/api/v1/admin/centers/{center}/video-access-codes/bulk-revoke` | Bulk revoke |
| POST | `/api/v1/admin/centers/{center}/video-access-codes/{code}/send-whatsapp` | Send code via WhatsApp |
| POST | `/api/v1/admin/centers/{center}/video-access-codes/bulk-send-whatsapp` | Bulk send via WhatsApp |

**Generate Code Request:**
```json
{
    "video_id": 123,
    "course_id": 456
}
```

**Generate Code Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "code": "A1B2C3D4",
        "qr_code_url": "data:image/png;base64,...",
        "expires_at": "2026-04-01T00:00:00Z",
        "student": { "id": 1, "name": "John" },
        "video": { "id": 123, "title": "Lecture 1" }
    }
}
```

**Send WhatsApp Request:**
```json
{
    "format": "qr_code"
}
```
Options: `qr_code` (sends QR image) or `text_code` (sends text message)

**Send WhatsApp Response:**
```json
{
    "success": true,
    "message": "Code sent to student via WhatsApp"
}
```

**Bulk Send WhatsApp Request:**
```json
{
    "code_ids": [1, 2, 3, 4, 5],
    "format": "text_code"
}
```

**Bulk Send WhatsApp Response:**
```json
{
    "success": true,
    "data": {
        "sent": 4,
        "failed": 1,
        "results": [
            { "code_id": 1, "success": true },
            { "code_id": 2, "success": true },
            { "code_id": 3, "success": false, "error": "Student has no phone number" },
            { "code_id": 4, "success": true },
            { "code_id": 5, "success": true }
        ]
    }
}
```

### Mobile - Student Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/centers/{center}/courses/{course}/videos/{video}/access-request` | Create request |
| GET | `/api/v1/centers/{center}/courses/{course}/videos/{video}/access-status` | Check status |
| POST | `/api/v1/video-access-codes/redeem` | Redeem code |

**Create Request:**
```json
{
    "reason": "I need access to complete assignment"
}
```

**Access Status Response:**
```json
{
    "success": true,
    "data": {
        "has_access": false,
        "status": "pending",
        "pending_request_id": 123,
        "can_request": false
    }
}
```

**Redeem Code Request:**
```json
{
    "code": "A1B2C3D4"
}
```

---

## Error Codes

| Code | HTTP | Cause |
|------|------|-------|
| `VIDEO_ACCESS_DENIED` | 403 | No access to video |
| `VIDEO_ACCESS_REQUEST_EXISTS` | 422 | Pending request already exists |
| `VIDEO_ACCESS_ALREADY_GRANTED` | 422 | Access already granted |
| `VIDEO_CODE_INVALID` | 404 | Code not found |
| `VIDEO_CODE_EXPIRED` | 410 | Code has expired |
| `VIDEO_CODE_USED` | 410 | Code already used |
| `VIDEO_CODE_REVOKED` | 410 | Code was revoked |
| `VIDEO_CODE_WRONG_USER` | 403 | Code belongs to different student |
| `STUDENT_NO_PHONE` | 422 | Student has no phone number for WhatsApp |
| `WHATSAPP_SEND_FAILED` | 500 | Failed to send WhatsApp message |

---

## Billing Integration

### Concept: Codes = Billable Units

Every video access requires a code. Redeemed codes are the single source of truth for billing.

```
Monthly Invoice = COUNT(redeemed codes in period) × price_per_view
```

### Billing Query

```php
// Get billable views for a center in a given period
$billableViews = VideoAccessCode::query()
    ->where('center_id', $center->id)
    ->where('status', VideoAccessCodeStatus::Used)
    ->whereBetween('used_at', [$startOfMonth, $endOfMonth])
    ->count();

$invoice = $billableViews * $center->price_per_view;
```

### Billing Report Data

```php
// Detailed billing report
$billingData = VideoAccessCode::query()
    ->where('center_id', $center->id)
    ->where('status', VideoAccessCodeStatus::Used)
    ->whereBetween('used_at', [$startOfMonth, $endOfMonth])
    ->with(['user', 'video', 'course'])
    ->get()
    ->groupBy('course_id')
    ->map(fn($codes) => [
        'course' => $codes->first()->course->title,
        'views' => $codes->count(),
        'students' => $codes->pluck('user_id')->unique()->count(),
        'videos' => $codes->pluck('video_id')->unique()->count(),
    ]);
```

### Potential Admin Endpoints (Future)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/billing/summary` | Monthly summary |
| GET | `/api/v1/admin/centers/{center}/billing/report` | Detailed report |
| GET | `/api/v1/admin/centers/{center}/billing/export` | Export for invoicing |

---

## Settings: Center + Course Level

### Override Logic

```php
// Course setting overrides Center setting
$requiresRedemption = $course->requires_video_approval
    ?? $center->settings['requires_video_approval']
    ?? false;
```

### Center Settings (Default)

Add to center_settings JSON:

```json
{
    "requires_video_approval": true,
    "video_code_expiry_days": 30
}
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `requires_video_approval` | boolean | false | Default for all courses in center |
| `video_code_expiry_days` | int/null | null | Days until codes expire (null = no expiry) |

### Course Setting (Override)

Add column to `courses` table:

```php
// Migration
$table->boolean('requires_video_approval')->nullable()->default(null);
```

| Value | Behavior |
|-------|----------|
| `null` | Inherit from center setting |
| `true` | Force enable for this course |
| `false` | Force disable for this course |

### Examples

| Center Setting | Course Setting | Result |
|----------------|----------------|--------|
| `true` | `null` | Requires redemption |
| `true` | `false` | No redemption needed |
| `false` | `null` | No redemption needed |
| `false` | `true` | Requires redemption |

---

## Implementation Checklist

### Phase 1: Database
- [ ] Migration: create `video_access_requests` table
- [ ] Migration: create `video_accesses` table
- [ ] Migration: create `video_access_codes` table
- [ ] Migration: add `requires_video_approval` to `courses` table
- [ ] Migration: create `bulk_whatsapp_jobs` table
- [ ] Migration: create `bulk_whatsapp_job_items` table

### Phase 2: Models & Enums
- [ ] Enum: `VideoAccessRequestStatus`
- [ ] Enum: `VideoAccessCodeStatus`
- [ ] Enum: `WhatsAppCodeFormat`
- [ ] Enum: `BulkJobStatus`
- [ ] Enum: `BulkItemStatus`
- [ ] Model: `VideoAccessRequest`
- [ ] Model: `VideoAccess`
- [ ] Model: `VideoAccessCode`
- [ ] Model: `BulkWhatsAppJob`
- [ ] Model: `BulkWhatsAppJobItem`
- [ ] Modify: `Course` model (add `requires_video_approval`)

### Phase 3: Services & Jobs
- [ ] Interface + Service: `VideoApprovalService`
- [ ] Interface + Service: `VideoApprovalRequestService`
- [ ] Interface + Service: `VideoApprovalCodeService`
- [ ] Interface + Service: `BulkWhatsAppService`
- [ ] Query Service: `VideoAccessRequestQueryService`
- [ ] Query Service: `VideoAccessCodeQueryService`
- [ ] Query Service: `BulkWhatsAppJobQueryService`
- [ ] Job: `ProcessBulkWhatsAppJob`
- [ ] Job: `SendSingleWhatsAppCodeJob`
- [ ] Modify: `PlaybackAuthorizationService`
- [ ] Modify: `EvolutionApiClient` (add `sendMedia`)
- [ ] Register services in `AppServiceProvider`

### Phase 4: API Layer
- [ ] Form Requests (~18 files - includes WhatsApp + bulk operations)
- [ ] Controllers (6 files - includes BulkWhatsAppJobController)
- [ ] Resources (7 files - includes bulk job resources)
- [ ] Routes (5 files - includes bulk WhatsApp routes)

### Phase 5: Testing
- [ ] Factories (3 files)
- [ ] Feature tests (5 files)
- [ ] Unit tests (3 files)
- [ ] Run quality checks

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Services/VideoAccess/VideoAccessService.php` | Access check logic |
| `app/Services/VideoAccess/VideoAccessRequestService.php` | Request workflow |
| `app/Services/VideoAccess/VideoAccessCodeService.php` | Code management |
| `app/Services/Playback/PlaybackAuthorizationService.php` | Playback integration |
| `app/Models/VideoAccessRequest.php` | Request model |
| `app/Models/VideoAccess.php` | Access record model |
| `app/Models/VideoAccessCode.php` | Code model |
| `app/Http/Controllers/Admin/VideoAccessRequestController.php` | Admin request endpoints (approve/reject) |
| `app/Http/Controllers/Admin/VideoAccessController.php` | Admin access endpoints (revoke only) |
| `app/Http/Controllers/Admin/VideoAccessCodeController.php` | Admin code endpoints (generate/send) |
| `app/Http/Controllers/Mobile/VideoAccessRequestController.php` | Student request endpoint |
| `app/Http/Controllers/Mobile/VideoAccessCodeController.php` | Student code redemption |

---

## Testing

```bash
# Run all video access tests
php artisan test --filter="VideoAccess"

# Specific test suites
php artisan test --filter="VideoAccessRequestTest"
php artisan test --filter="VideoAccessCodeTest"

# With coverage
php artisan test --filter="VideoAccess" --coverage
```

### Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Student requests access - happy path | Feature | High |
| Admin approves request - code auto-generated | Feature | High |
| Admin approves + sends WhatsApp | Feature | High |
| Admin rejects request | Feature | High |
| Admin generates code directly | Feature | High |
| Student redeems code - access granted | Feature | High |
| Wrong student tries to redeem code | Feature | High |
| Expired code rejection | Feature | High |
| Playback blocked without access | Feature | High |
| Bulk approve with WhatsApp send | Feature | Medium |
| Bulk code generation | Feature | Medium |
| Billing: count redeemed codes | Feature | High |
| QR code generation | Unit | Medium |

---

## Implementation Reference

### Existing Patterns to Follow

The `ExtraViewRequest` feature is the closest pattern to follow for `VideoAccessRequest`:

| Reference File | Use For |
|----------------|---------|
| `app/Models/ExtraViewRequest.php` | Model structure, scopes, relationships |
| `app/Services/Playback/ExtraViewRequestService.php` | Service pattern, validation, transactions |
| `app/Services/Admin/ExtraViewRequestQueryService.php` | Admin listing/filtering |
| `app/Http/Controllers/Admin/ExtraViewRequestController.php` | Admin controller pattern |
| `app/Http/Controllers/Mobile/ExtraViewRequestController.php` | Mobile controller pattern |
| `app/Enums/ExtraViewRequestStatus.php` | Enum pattern |
| `app/Filters/Admin/ExtraViewRequestFilters.php` | Filter pattern |
| `tests/Feature/Admin/ExtraViewRequestApprovalTest.php` | Test patterns |

### WhatsApp Integration (Evolution API)

**Existing services to reuse/extend:**

| File | Purpose |
|------|---------|
| `app/Services/Evolution/EvolutionApiClient.php` | Base API client |
| `app/Services/Auth/Senders/EvolutionOtpSender.php` | Pattern for sending messages |
| `config/evolution.php` | Configuration pattern |

**Current `EvolutionApiClient` methods:**
- `sendText(string $instanceName, array $payload)` - Send text message ✅
- Need to add: `sendMedia()` or `sendImage()` for QR code images

**Payload format for text:**
```php
$this->client->sendText($instanceName, [
    'number' => $this->normalizeDestination($phone),
    'text' => $message,
]);
```

**Note:** Evolution API supports `sendMedia` endpoint for images. Need to add method:
```php
// Add to EvolutionApiClient.php
public function sendMedia(string $instanceName, array $payload): array
{
    return $this->request()
        ->post('/message/sendMedia/'.$instanceName, $payload)
        ->throw()
        ->json();
}
```

### QR Code Generation

**Recommended package:** `simplesoftwareio/simple-qrcode` or `bacon/bacon-qr-code`

```bash
composer require simplesoftwareio/simple-qrcode
```

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qrDataUrl = 'data:image/png;base64,' . base64_encode(
    QrCode::format('png')->size(300)->generate($code->code)
);
```

### Key Services to Inject

Based on `ExtraViewRequestService`, inject these services:

```php
public function __construct(
    private readonly CenterScopeService $centerScopeService,
    private readonly StudentAccessService $studentAccessService,
    private readonly EnrollmentAccessService $enrollmentAccessService,
    private readonly CourseAccessService $courseAccessService,
    private readonly SettingsResolverServiceInterface $settingsResolver,
    private readonly AuditLogService $auditLogService,
    private readonly AdminNotificationDispatcher $notificationDispatcher,
    private readonly EvolutionApiClient $evolutionApiClient, // For WhatsApp
) {}
```

### Access Services Location

| Service | File |
|---------|------|
| `CenterScopeService` | `app/Services/Centers/CenterScopeService.php` |
| `StudentAccessService` | `app/Services/Access/StudentAccessService.php` |
| `EnrollmentAccessService` | `app/Services/Access/EnrollmentAccessService.php` |
| `CourseAccessService` | `app/Services/Access/CourseAccessService.php` |
| `AuditLogService` | `app/Services/Audit/AuditLogService.php` |
| `AdminNotificationDispatcher` | `app/Services/AdminNotifications/AdminNotificationDispatcher.php` |

### PlaybackAuthorizationService Integration

**File:** `app/Services/Playback/PlaybackAuthorizationService.php`

Insert video access check after enrollment check in `assertCanStartPlayback()` method.

---

## Critical Implementation Notes

### 1. Naming Conflict: VideoAccessService

**IMPORTANT:** `VideoAccessService` already exists at `app/Services/Access/VideoAccessService.php`

Current purpose: Checks if video is ready for playback (encoding status, upload status)

**Solution:** Create our new service with a different name:
- `VideoApprovalService` - For checking/granting per-video access approval
- Keep existing `VideoAccessService` unchanged

**Updated service names:**
| Planned Name | New Name |
|--------------|----------|
| `VideoAccessService` | `VideoApprovalService` |
| `VideoAccessRequestService` | `VideoApprovalRequestService` |
| `VideoAccessCodeService` | `VideoApprovalCodeService` |

### 2. Course Model Modification

**File:** `app/Models/Course.php`

Add to `$fillable`:
```php
'requires_video_approval',
```

Add to `$casts`:
```php
'requires_video_approval' => 'boolean',
```

**Migration:**
```php
Schema::table('courses', function (Blueprint $table) {
    $table->boolean('requires_video_approval')->nullable()->default(null)->after('is_published');
});
```

### 3. PlaybackAuthorizationService Integration Point

**Location:** `app/Services/Playback/PlaybackAuthorizationService.php:60`

```php
// After line 60: $this->enrollmentAccessService->assertActiveEnrollment($student, $course);
// Add video approval check here:
$this->videoApprovalService->assertApprovalAccess($student, $center, $course, $video);
```

**Constructor already injects:**
- `ViewLimitService`
- `StudentAccessService`
- `CourseAccessService`
- `EnrollmentAccessService`
- `VideoAccessService` (for readiness checks)

**Need to add:** `VideoApprovalService` (new)

### 4. AdminNotificationType Enum

**File:** `app/Enums/AdminNotificationType.php`

Add new case:
```php
case VIDEO_ACCESS_REQUEST = 7;
```

Add to `label()`:
```php
self::VIDEO_ACCESS_REQUEST => 'Video Access Request',
```

Add to `icon()`:
```php
self::VIDEO_ACCESS_REQUEST => 'play-circle',
```

Add to `labelTranslations()`:
```php
self::VIDEO_ACCESS_REQUEST => ['en' => 'Video Access Request', 'ar' => 'طلب الوصول للفيديو'],
```

### 5. CenterSetting JSON Structure

**File:** `app/Models/CenterSetting.php`

Settings stored as JSON array. Just add to existing settings:
```php
$centerSetting->settings = array_merge($centerSetting->settings, [
    'requires_video_approval' => true,
    'video_code_expiry_days' => 30,
]);
```

### 6. ErrorCodes to Add

**File:** `app/Support/ErrorCodes.php`

```php
public const VIDEO_ACCESS_DENIED = 'VIDEO_ACCESS_DENIED';
public const VIDEO_ACCESS_REQUEST_EXISTS = 'VIDEO_ACCESS_REQUEST_EXISTS';
public const VIDEO_ACCESS_ALREADY_GRANTED = 'VIDEO_ACCESS_ALREADY_GRANTED';
public const VIDEO_CODE_INVALID = 'VIDEO_CODE_INVALID';
public const VIDEO_CODE_EXPIRED = 'VIDEO_CODE_EXPIRED';
public const VIDEO_CODE_USED = 'VIDEO_CODE_USED';
public const VIDEO_CODE_REVOKED = 'VIDEO_CODE_REVOKED';
public const VIDEO_CODE_WRONG_USER = 'VIDEO_CODE_WRONG_USER';
public const STUDENT_NO_PHONE = 'STUDENT_NO_PHONE';
public const WHATSAPP_SEND_FAILED = 'WHATSAPP_SEND_FAILED';
```

### 7. AdminNotificationDispatcher Method

**File:** `app/Services/AdminNotifications/AdminNotificationDispatcher.php`

Add method following `dispatchExtraViewRequest` pattern:
```php
public function dispatchVideoAccessRequest(VideoAccessRequest $request): AdminNotification
{
    // Follow dispatchExtraViewRequest pattern
}
```

### 8. Mobile Video List - Response Fields

**File:** `app/Http/Resources/Mobile/CourseVideoResource.php`

#### Response Structure

```json
{
    "id": 1,
    "title": "Lecture 1",
    "duration_seconds": 2700,
    "thumbnail_url": "...",

    "requires_redemption": true,
    "has_redeemed": false,
    "is_locked": true,

    "access_status": "locked",
    "pending_request_id": null
}
```

#### Field Definitions

| Field | Type | Description |
|-------|------|-------------|
| `requires_redemption` | bool | Does this video require code redemption? (from course/center setting) |
| `has_redeemed` | bool | Has student redeemed a code? (`true` if feature disabled) |
| `is_locked` | bool | Overall locked status (combines redemption + view limit) |
| `access_status` | string/null | Detailed status for UI (null if feature disabled) |
| `pending_request_id` | int/null | ID of pending request (for cancel/view) |

#### Derived Field (Mobile)

```dart
// Mobile derives can_request_access from access_status
bool canRequestAccess = (access_status == 'locked' || access_status == 'rejected');
```

#### Scenario Matrix

| Scenario | `requires_redemption` | `has_redeemed` | `is_locked` | `access_status` | `pending_request_id` |
|----------|----------------------|----------------|-------------|-----------------|---------------------|
| Feature disabled, within limit | `false` | `true` | `false` | `null` | `null` |
| Feature disabled, limit reached | `false` | `true` | `true` | `null` | `null` |
| Needs code, not requested | `true` | `false` | `true` | `locked` | `null` |
| Needs code, pending request | `true` | `false` | `true` | `pending` | `123` |
| Needs code, approved (code ready) | `true` | `false` | `true` | `approved` | `null` |
| Needs code, rejected | `true` | `false` | `true` | `rejected` | `null` |
| Redeemed, within limit | `true` | `true` | `false` | `granted` | `null` |
| Redeemed, limit reached | `true` | `true` | `true` | `granted` | `null` |

#### Mobile UI Logic

```dart
// Simplified mobile logic
if (requires_redemption && !has_redeemed) {
    // Show redemption UI based on access_status
    switch (access_status) {
        case 'locked':
            showButtons(['Request Access', 'Enter Code']);
            break;
        case 'pending':
            showStatus('Awaiting approval', requestId: pending_request_id);
            break;
        case 'approved':
            showButtons(['Enter Code']); // Code is ready
            break;
        case 'rejected':
            showButtons(['Request Again', 'Enter Code']);
            break;
    }
} else if (is_locked) {
    // has_redeemed = true but locked = view limit reached
    showButtons(['Request Extra Views']);
} else {
    // Can play
    showPlayButton();
}

// Derived helper
bool canRequestAccess() => access_status == 'locked' || access_status == 'rejected';
```

#### Access Status Values

| Status | Meaning | Mobile Actions |
|--------|---------|----------------|
| `null` | Feature disabled | Normal play/view limit flow |
| `locked` | Needs code, no request yet | "Request Access" + "Enter Code" |
| `pending` | Request awaiting approval | Show status (use `pending_request_id`) |
| `approved` | Code generated, not redeemed | "Enter Code" |
| `rejected` | Request was rejected | "Request Again" + "Enter Code" |
| `granted` | Code redeemed | Play (unless view limit reached) |

**Mobile derives:** `canRequestAccess = (access_status == 'locked' || access_status == 'rejected')`

**Implementation in `CourseVideoResource`:**

```php
public function toArray(Request $request): array
{
    /** @var Video $video */
    $video = $this->resource;
    /** @var CourseVideo|null $pivot */
    $pivot = $video->pivot instanceof CourseVideo ? $video->pivot : null;
    /** @var User|null $user */
    $user = $request->user();
    $course = $pivot?->course;

    // Resolve redemption requirement (course overrides center)
    $requiresRedemption = $this->requiresRedemption($course);

    // Resolve redemption and access status
    $redemptionData = $this->resolveRedemptionStatus($user, $video, $course, $requiresRedemption);

    // Resolve view limit lock
    $isViewLimitLocked = $this->isViewLimitExceeded($request, $video, $pivot);

    // Overall is_locked: not redeemed OR view limit reached
    $isLocked = !$redemptionData['has_redeemed'] || $isViewLimitLocked;

    // Also check visibility
    if (!(bool) ($pivot?->visible ?? true)) {
        $isLocked = true;
    }

    return [
        'id' => $video->id,
        'title' => $video->translate('title'),
        'duration_seconds' => $video->duration_seconds,
        'thumbnail_url' => $video->thumbnail_url,

        // Redemption fields
        'requires_redemption' => $requiresRedemption,
        'has_redeemed' => $redemptionData['has_redeemed'],
        'is_locked' => $isLocked,

        // Detailed access status
        'access_status' => $redemptionData['access_status'],
        'pending_request_id' => $redemptionData['pending_request_id'],

        'updated_at' => $video->updated_at,
    ];
}

private function requiresRedemption(?Course $course): bool
{
    if ($course === null) {
        return false;
    }

    // Course setting overrides center setting
    if ($course->requires_video_approval !== null) {
        return (bool) $course->requires_video_approval;
    }

    // Fall back to center setting
    $center = $course->center;
    $settings = $center?->centerSetting?->settings ?? [];

    return (bool) ($settings['requires_video_approval'] ?? false);
}

private function resolveRedemptionStatus(
    ?User $user,
    Video $video,
    ?Course $course,
    bool $requiresRedemption
): array {
    // If feature disabled, treat as already redeemed
    if (!$requiresRedemption || $user === null || $course === null) {
        return [
            'has_redeemed' => true,
            'access_status' => null,
            'pending_request_id' => null,
        ];
    }

    // Check if student has redeemed (has VideoAccess record)
    $hasRedeemed = VideoAccess::query()
        ->forUserAndVideo($user->id, $video->id)
        ->where('course_id', $course->id)
        ->active()
        ->exists();

    if ($hasRedeemed) {
        return [
            'has_redeemed' => true,
            'access_status' => 'granted',
            'pending_request_id' => null,
        ];
    }

    // Not redeemed - check request status

    // Check for pending request
    $pendingRequest = VideoAccessRequest::query()
        ->pendingForUserAndVideo($user, $video)
        ->where('course_id', $course->id)
        ->first();

    if ($pendingRequest) {
        return [
            'has_redeemed' => false,
            'access_status' => 'pending',
            'pending_request_id' => $pendingRequest->id,
        ];
    }

    // Check for approved request with unredeemed code
    $hasUnredeemedCode = VideoAccessCode::query()
        ->where('user_id', $user->id)
        ->where('video_id', $video->id)
        ->where('course_id', $course->id)
        ->where('status', VideoAccessCodeStatus::Active)
        ->exists();

    if ($hasUnredeemedCode) {
        return [
            'has_redeemed' => false,
            'access_status' => 'approved',
            'pending_request_id' => null,
        ];
    }

    // Check for recent rejection
    $wasRejected = VideoAccessRequest::query()
        ->forUser($user)
        ->forVideo($video)
        ->where('course_id', $course->id)
        ->rejected()
        ->exists();

    if ($wasRejected) {
        return [
            'has_redeemed' => false,
            'access_status' => 'rejected',
            'pending_request_id' => null,
        ];
    }

    // Default: locked
    return [
        'has_redeemed' => false,
        'access_status' => 'locked',
        'pending_request_id' => null,
    ];
}
```

### 9. Evolution API - Add sendMedia Method

**File:** `app/Services/Evolution/EvolutionApiClient.php`

Add method for QR code images:
```php
public function sendMedia(string $instanceName, array $payload): array
{
    $request = $this->request();
    $instanceToken = (string) config('evolution.otp_instance_token', '');

    if ($instanceToken !== '') {
        $request = $request->replaceHeaders(['apikey' => $instanceToken]);
    }

    return $request
        ->post('/message/sendMedia/'.$instanceName, $payload)
        ->throw()
        ->json();
}
```

### 10. Bulk WhatsApp Sending System

**Problem:** Sending 400-500+ messages at once can trigger WhatsApp rate limiting/bans.

**Solution:** Queue-based gradual sending with progress tracking, pause/resume, and failure handling.

---

#### Database Tables

**`bulk_whatsapp_jobs`** - Tracks bulk send operations

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK | Center |
| `total_codes` | int | Total to send |
| `sent_count` | int | Successfully sent |
| `failed_count` | int | Failed to send |
| `status` | enum | `pending`, `processing`, `completed`, `paused`, `failed` |
| `format` | enum | `qr_code`, `text_code` |
| `started_at` | timestamp | When started |
| `completed_at` | timestamp | When finished |
| `created_by` | FK | Admin who initiated |
| `settings` | json | Rate limit settings |
| `timestamps` | | |

**`bulk_whatsapp_job_items`** - Individual send items

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `bulk_job_id` | FK | Parent job |
| `video_access_code_id` | FK | Code to send |
| `status` | enum | `pending`, `sent`, `failed` |
| `error` | text | Error message if failed |
| `sent_at` | timestamp | When sent |
| `attempts` | int | Retry count |

---

#### Configuration (Center Settings)

```json
{
    "whatsapp_bulk_settings": {
        "delay_seconds": 3,
        "batch_size": 50,
        "batch_pause_seconds": 60,
        "max_retries": 2,
        "max_failures_before_pause": 10
    }
}
```

| Setting | Default | Description |
|---------|---------|-------------|
| `delay_seconds` | 3 | Delay between each message |
| `batch_size` | 50 | Messages per batch |
| `batch_pause_seconds` | 60 | Pause between batches |
| `max_retries` | 2 | Retry failed messages |
| `max_failures_before_pause` | 10 | Auto-pause if too many failures |

---

#### Service Implementation

```php
// BulkWhatsAppService.php

public function initiate(array $codeIds, WhatsAppCodeFormat $format, User $admin): BulkWhatsAppJob
{
    $job = BulkWhatsAppJob::create([
        'center_id' => $admin->center_id,
        'total_codes' => count($codeIds),
        'format' => $format,
        'status' => BulkJobStatus::Pending,
        'created_by' => $admin->id,
        'settings' => $this->getSettings($admin->center),
    ]);

    // Create items
    foreach ($codeIds as $codeId) {
        BulkWhatsAppJobItem::create([
            'bulk_job_id' => $job->id,
            'video_access_code_id' => $codeId,
            'status' => BulkItemStatus::Pending,
        ]);
    }

    // Dispatch processing job
    ProcessBulkWhatsAppJob::dispatch($job);

    return $job;
}

public function pause(BulkWhatsAppJob $job): void
{
    $job->update(['status' => BulkJobStatus::Paused]);
}

public function resume(BulkWhatsAppJob $job): void
{
    $job->update(['status' => BulkJobStatus::Processing]);
    ProcessBulkWhatsAppJob::dispatch($job);
}

public function retryFailed(BulkWhatsAppJob $job): void
{
    $job->items()->failed()->update(['status' => BulkItemStatus::Pending, 'attempts' => 0]);
    $job->update(['status' => BulkJobStatus::Processing]);
    ProcessBulkWhatsAppJob::dispatch($job);
}
```

---

#### Job Classes

```php
// ProcessBulkWhatsAppJob.php

public function handle(): void
{
    $job = $this->bulkJob;
    $settings = $job->settings;

    $job->update(['status' => BulkJobStatus::Processing, 'started_at' => now()]);

    $items = $job->items()->pending()->get();
    $batchSize = $settings['batch_size'] ?? 50;
    $delaySeconds = $settings['delay_seconds'] ?? 3;
    $batchPause = $settings['batch_pause_seconds'] ?? 60;

    $batches = $items->chunk($batchSize);
    $totalDelay = 0;

    foreach ($batches as $batchIndex => $batch) {
        foreach ($batch as $itemIndex => $item) {
            $itemDelay = $totalDelay + ($itemIndex * $delaySeconds);

            SendSingleWhatsAppCodeJob::dispatch($item)
                ->delay(now()->addSeconds($itemDelay));
        }

        // Add batch pause after each batch
        $totalDelay += ($batch->count() * $delaySeconds) + $batchPause;
    }
}
```

```php
// SendSingleWhatsAppCodeJob.php

public function handle(): void
{
    $item = $this->item;
    $bulkJob = $item->bulkJob;

    // Check if job was paused/cancelled
    if ($bulkJob->status === BulkJobStatus::Paused) {
        return; // Will be retried on resume
    }

    try {
        $code = $item->videoAccessCode;
        $this->codeService->sendViaWhatsApp($code, $bulkJob->format);

        $item->update([
            'status' => BulkItemStatus::Sent,
            'sent_at' => now(),
        ]);

        $bulkJob->increment('sent_count');

    } catch (\Throwable $e) {
        $item->increment('attempts');
        $item->update([
            'status' => BulkItemStatus::Failed,
            'error' => Str::limit($e->getMessage(), 500),
        ]);

        $bulkJob->increment('failed_count');

        // Auto-pause if too many failures
        $maxFailures = $bulkJob->settings['max_failures_before_pause'] ?? 10;
        if ($bulkJob->failed_count >= $maxFailures) {
            $bulkJob->update(['status' => BulkJobStatus::Paused]);
            // TODO: Notify admin of auto-pause
        }
    }

    // Check if completed
    $this->checkCompletion($bulkJob);
}

private function checkCompletion(BulkWhatsAppJob $job): void
{
    $pending = $job->items()->pending()->count();

    if ($pending === 0 && $job->status === BulkJobStatus::Processing) {
        $job->update([
            'status' => BulkJobStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
```

---

#### Admin API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `.../video-access-codes/bulk-send-whatsapp` | Initiate bulk send |
| GET | `.../bulk-whatsapp-jobs` | List bulk jobs |
| GET | `.../bulk-whatsapp-jobs/{job}` | Job status + progress |
| POST | `.../bulk-whatsapp-jobs/{job}/pause` | Pause job |
| POST | `.../bulk-whatsapp-jobs/{job}/resume` | Resume job |
| POST | `.../bulk-whatsapp-jobs/{job}/retry-failed` | Retry failed items |
| DELETE | `.../bulk-whatsapp-jobs/{job}` | Cancel job |

**Initiate Response:**
```json
{
    "success": true,
    "data": {
        "job_id": 123,
        "status": "processing",
        "total_codes": 500,
        "estimated_minutes": 35
    }
}
```

**Progress Response:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "status": "processing",
        "total_codes": 500,
        "sent_count": 234,
        "failed_count": 4,
        "pending_count": 262,
        "progress_percent": 47,
        "started_at": "2026-03-05T10:00:00Z",
        "estimated_completion": "2026-03-05T10:35:00Z"
    }
}
```

---

#### Admin UI: Progress View

```
┌──────────────────────────────────────────────────────────────┐
│ Bulk WhatsApp Send                                           │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  Status: ● Processing                                        │
│                                                              │
│  ████████████████░░░░░░░░░░░░░░  234 / 500  (47%)           │
│                                                              │
│  ✓ Sent: 230                                                 │
│  ✗ Failed: 4                                                 │
│  ◷ Pending: 266                                              │
│                                                              │
│  Estimated time remaining: ~15 minutes                       │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌─────────────────┐            │
│  │  Pause   │  │  Cancel  │  │  View Failed    │            │
│  └──────────┘  └──────────┘  └─────────────────┘            │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

#### Timing Examples

| Messages | Batch Size | Delay | Batch Pause | Total Time |
|----------|------------|-------|-------------|------------|
| 100 | 50 | 3 sec | 60 sec | ~6 min |
| 500 | 50 | 3 sec | 60 sec | ~35 min |
| 1000 | 50 | 3 sec | 60 sec | ~70 min |

---

#### Enums

```php
enum BulkJobStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Completed = 2;
    case Paused = 3;
    case Failed = 4;
    case Cancelled = 5;
}

enum BulkItemStatus: int
{
    case Pending = 0;
    case Sent = 1;
    case Failed = 2;
}
```
