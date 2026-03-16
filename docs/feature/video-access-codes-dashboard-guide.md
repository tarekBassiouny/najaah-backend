# Video Access Codes - Admin Dashboard Implementation Guide

> **Audience:** Frontend Dashboard/Admin Panel developers
> **Purpose:** Complete guide to implement video code batch management for admin dashboard

---

## Table of Contents

1. [Overview](#overview)
2. [What Changed](#what-changed)
3. [The Two Access Models](#the-two-access-models)
4. [Dashboard Screens to Add/Modify](#dashboard-screens-to-addmodify)
5. [API Endpoints Reference](#api-endpoints-reference)
6. [Complete Workflows](#complete-workflows)
7. [Error Handling](#error-handling)
8. [UI/UX Guidelines](#uiux-guidelines)
9. [Code Examples](#code-examples)

---

## Overview

We now support **two access models** for courses. Admins need to:

| Access Model | Admin Responsibilities |
|--------------|----------------------|
| `enrollment` | Enroll students, approve video access requests, send codes |
| `video_code` | Create code batches, export codes, close batches for invoicing |

---

## What Changed

### Course Model Changes

Courses now have an `access_model` field:

```json
{
  "id": 123,
  "title": "Mathematics 101",
  "access_model": "enrollment",  // or "video_code"
  // ... other fields
}
```

### New Entities

| Entity | Description |
|--------|-------------|
| `VideoCodeBatch` | A batch of codes for a specific course video |
| `VideoCodeRedemption` | Record of a student redeeming a code |

---

## The Two Access Models

### Enrollment Model (Existing)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ENROLLMENT MODEL - ADMIN FLOW                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  1. Create Course                                                        │
│     └─ access_model: "enrollment" (default)                              │
│                                                                          │
│  2. Add Videos to Course                                                 │
│     └─ Set requires_video_approval if needed                             │
│                                                                          │
│  3. Enroll Students                                                      │
│     └─ Manual enrollment or approve enrollment requests                  │
│                                                                          │
│  4. Video Access (if requires_video_approval: true)                      │
│     └─ Students request access                                           │
│     └─ Admin approves and sends access code                              │
│     └─ Student redeems code                                              │
│                                                                          │
│  No batch management, no invoicing based on codes                        │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Video Code Model (New)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    VIDEO CODE MODEL - ADMIN FLOW                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  1. Create Course                                                        │
│     └─ access_model: "video_code"                                        │
│                                                                          │
│  2. Add Videos to Course                                                 │
│     └─ Each video will need code batches                                 │
│                                                                          │
│  3. Generate Code Batch                                                  │
│     └─ Select video                                                      │
│     └─ Specify quantity (e.g., 100 codes)                                │
│     └─ Specify view limit per code (e.g., 3 views)                       │
│     └─ System generates batch with unique codes                          │
│                                                                          │
│  4. Export Codes                                                         │
│     └─ Download as CSV (for digital distribution)                        │
│     └─ Download as PDF (for printed cards with QR codes)                 │
│                                                                          │
│  5. Sell Codes Offline                                                   │
│     └─ Center sells physical/digital codes to students                   │
│     └─ No tracking in system - happens outside                           │
│                                                                          │
│  6. Students Redeem Codes                                                │
│     └─ System tracks redemptions automatically                           │
│     └─ Admin can view statistics                                         │
│                                                                          │
│  7. Close Batch (for invoicing)                                          │
│     └─ Enter number of codes actually sold                               │
│     └─ System sets sold_limit                                            │
│     └─ Remaining codes become invalid                                    │
│     └─ Invoice generated based on sold count                             │
│                                                                          │
│  8. Expand Batch (optional)                                              │
│     └─ Add more codes to existing open batch                             │
│     └─ No need to close and create new batch                             │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Dashboard Screens to Add/Modify

### 1. Course Create/Edit Form

**Add `access_model` field:**

```tsx
<FormField label="Access Model">
  <Select
    name="access_model"
    options={[
      { value: 'enrollment', label: 'Enrollment Based (Traditional)' },
      { value: 'video_code', label: 'Video Code Based (Offline Sales)' }
    ]}
    defaultValue="enrollment"
    helperText={
      accessModel === 'video_code'
        ? 'Students will need to purchase and redeem codes to access videos'
        : 'Students will be enrolled by admin to access the course'
    }
  />
</FormField>
```

**Conditional UI:**
- If `video_code`: Hide enrollment-related options
- If `video_code`: Show link to "Manage Code Batches"
- Course access model is immutable after creation. Changing `enrollment` to `video_code` or the reverse requires creating a new course.

---

### 2. Video Code Batches List (NEW SCREEN)

**URL:** `/admin/centers/{centerId}/video-code-batches`

**Purpose:** List all video code batches for the center

**Features:**
- Filter by course, video, status
- Show batch statistics
- Quick actions: Export, Close, View Details

**API:** `GET /admin/centers/{centerId}/video-code-batches`

---

### 3. Create Batch Form (NEW SCREEN/MODAL)

**URL:** `/admin/centers/{centerId}/courses/{courseId}/videos/{videoId}/code-batches/create`

**Purpose:** Generate a new batch of codes for a video

**Form Fields:**
- Course (read-only, from route context)
- Video (read-only)
- Quantity (number input, required)
- View Limit Per Code (number input, default: 2)

**API:** `POST /admin/centers/{centerId}/courses/{courseId}/videos/{videoId}/code-batches`

---

### 4. Batch Details (NEW SCREEN)

**URL:** `/admin/centers/{centerId}/code-batches/{batchId}`

**Purpose:** View batch details, statistics, and manage batch

**Sections:**
- Batch Info (code, quantity, status, dates)
- Statistics (redeemed count, remaining, sold limit)
- Actions (Export CSV, Export PDF, Expand, Close)
- Redemptions List (who redeemed, when)

**APIs:**
- `GET /admin/centers/{centerId}/code-batches/{batchId}` - Details
- `GET /admin/centers/{centerId}/code-batches/{batchId}/statistics` - Stats
- `GET /admin/centers/{centerId}/code-batches/{batchId}/redemptions` - Paginated redemption history

---

### 5. Export Codes (ACTION)

**Purpose:** Download codes as CSV or PDF

**APIs:**
- `GET /admin/centers/{centerId}/code-batches/{batchId}/export/csv`
- `GET /admin/centers/{centerId}/code-batches/{batchId}/export/pdf`

---

### 6. Close Batch Modal (NEW)

**Purpose:** Close batch and set sold limit for invoicing

**Form Fields:**
- Sold Count (number input, required)
- Confirmation checkbox

**API:** `POST /admin/centers/{centerId}/code-batches/{batchId}/close`

---

### 7. Expand Batch Modal (NEW)

**Purpose:** Add more codes to existing open batch

**Form Fields:**
- Additional Quantity (number input, required)

**API:** `POST /admin/centers/{centerId}/code-batches/{batchId}/expand`

---

## API Endpoints Reference

### Course Endpoints (Modified)

#### Create Course
```http
POST /api/v1/admin/centers/{centerId}/courses
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "title": { "en": "Physics 101", "ar": "الفيزياء 101" },
  "description": { "en": "Introduction to physics", "ar": "مقدمة في الفيزياء" },
  "category_id": 5,
  "access_model": "video_code",  // NEW FIELD: "enrollment" or "video_code"
  "language": "en",
  "difficulty_level": 1,
  "requires_video_approval": null  // Ignored for video_code model
}

Response (201):
{
  "success": true,
  "message": "Course created successfully",
  "data": {
    "id": 456,
    "title": "Physics 101",
    "access_model": "video_code",
    // ... other fields
  }
}
```

#### Update Course
```http
PUT /api/v1/admin/centers/{centerId}/courses/{courseId}
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "access_model": "video_code"  // Must match the course's original access model
}
```

**Note:** `access_model` is immutable after course creation. If a center needs the other model, create a new course with the correct access model instead of editing the existing one.

---

### Video Code Batch Endpoints (NEW)

**Permission alignment:** All video access batch pages and actions are protected by backend permission `video_access.manage`. The frontend should gate this area with the dedicated `manage_video_access` capability mapped to `video_access.manage`, not the generic `manage_videos` / `video.manage` capability.

**Course picker alignment:** When showing selectable courses for batch creation or filtering, call `GET /api/v1/admin/centers/{centerId}/courses?access_model=video_code` so only video-code courses appear.

**Shared video alignment:** The same video can be attached to multiple courses. Batch uniqueness is scoped to `course_id + video_id`, and student access remains course-scoped even when the underlying video is shared.

#### List Batches
```http
GET /api/v1/admin/centers/{centerId}/video-code-batches
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15)
- course_id: int (optional) - filter by course
- video_id: int (optional) - filter by video
- status: string (optional) - "open" or "closed"
- search: string (optional) - matches batch_code, course title, or video title

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "batch_code": "VB001",
      "video_id": 101,
      "video_title": "Introduction to Physics",
      "course_id": 456,
      "course_title": "Physics 101",
      "quantity": 100,
      "sold_limit": null,
      "redeemed_count": 25,
      "view_limit_per_code": 3,
      "status": "open",
      "status_label": "Open",
      "generated_by": {
        "id": 1,
        "name": "Admin User"
      },
      "generated_at": "2026-03-14T10:00:00Z",
      "closed_at": null,
      "closed_by": null,
      "created_at": "2026-03-14T10:00:00Z"
    },
    {
      "id": 2,
      "batch_code": "VB002",
      "video_id": 102,
      "video_title": "Newton's Laws",
      "course_id": 456,
      "course_title": "Physics 101",
      "quantity": 50,
      "sold_limit": 40,
      "redeemed_count": 38,
      "view_limit_per_code": 2,
      "status": "closed",
      "status_label": "Closed",
      "generated_by": {
        "id": 1,
        "name": "Admin User"
      },
      "generated_at": "2026-03-10T10:00:00Z",
      "closed_at": "2026-03-13T15:00:00Z",
      "closed_by": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2026-03-10T10:00:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
}
```

#### Create Batch
```http
POST /api/v1/admin/centers/{centerId}/courses/{courseId}/videos/{videoId}/code-batches
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "quantity": 100,
  "view_limit_per_code": 3  // Optional, default: 2
}

Response (201):
{
  "success": true,
  "message": "Batch created successfully",
  "data": {
    "id": 3,
    "batch_code": "VB003",
    "video_id": 103,
    "video_title": "Thermodynamics",
    "course_id": 456,
    "course_title": "Physics 101",
    "center_id": 1,
    "quantity": 100,
    "sold_limit": null,
    "redeemed_count": 0,
    "view_limit_per_code": 3,
    "status": "open",
    "status_label": "Open",
    "generated_by": {
      "id": 1,
      "name": "Admin User"
    },
    "generated_at": "2026-03-14T15:30:00Z",
    "closed_at": null,
    "closed_by": null,
    "metadata": {
      "exports": []
    },
    "created_at": "2026-03-14T15:30:00Z",
    "updated_at": "2026-03-14T15:30:00Z"
  }
}

Errors:
- 400: Video does not belong to a video_code access model course
- 400: Course video already has an open batch (must close first)
- 404: Video not found
```

#### Get Batch Details
```http
GET /api/v1/admin/centers/{centerId}/code-batches/{batchId}
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "batch_code": "VB001",
    "video_id": 101,
    "video_title": "Introduction to Physics",
    "course_id": 456,
    "course_title": "Physics 101",
    "center_id": 1,
    "quantity": 100,
    "sold_limit": null,
    "redeemed_count": 25,
    "view_limit_per_code": 3,
    "status": "open",
    "status_label": "Open",
    "generated_by": {
      "id": 1,
      "name": "Admin User"
    },
    "generated_at": "2026-03-14T10:00:00Z",
    "closed_at": null,
    "closed_by": null,
    "metadata": {
      "exports": [
        {
          "id": "8d29dcb1-37b4-48bc-bf28-bad5f613d087",
          "type": "download",
          "format": "csv",
          "delivery_channel": "download",
          "status": "completed",
          "exported_at": "2026-03-14T11:00:00Z",
          "completed_at": null,
          "exported_by": {
            "id": 1,
            "name": "Admin User"
          },
          "destination_masked": null,
          "code_range": "1-100",
          "start_sequence": 1,
          "end_sequence": 100,
          "count": 100,
          "file_name": "introduction-to-physics-VB001.csv",
          "error": null
        }
      ]
    },
    "created_at": "2026-03-14T10:00:00Z",
    "updated_at": "2026-03-14T11:00:00Z",

    // Computed fields
    "available_codes": 75,  // quantity - redeemed_count
    "redemption_rate": 25.0,  // percentage
    "can_expand": true,  // only if status is open
    "can_close": true   // open batches, or closed batches that can still increase sold_limit
  }
}
```

#### Get Batch Statistics
```http
GET /api/v1/admin/centers/{centerId}/code-batches/{batchId}/statistics
Authorization: Bearer {token}

Response (200):
{
  "success": true,
  "data": {
    "batch_id": 1,
    "batch_code": "VB001",
    "total_codes": 100,
    "redeemed_count": 25,
    "available_count": 75,
    "sold_limit": null,
    "redemption_rate": 25.0,
    "status": "open",

    // Time-based stats
    "first_redemption_at": "2026-03-14T12:00:00Z",
    "last_redemption_at": "2026-03-14T14:30:00Z",

    // Export history
    "exports": [
      {
        "id": "8d29dcb1-37b4-48bc-bf28-bad5f613d087",
        "type": "download",
        "format": "csv",
        "delivery_channel": "download",
        "status": "completed",
        "exported_at": "2026-03-14T11:00:00Z",
        "completed_at": null,
        "exported_by": {
          "id": 1,
          "name": "Admin User"
        },
        "destination_masked": null,
        "code_range": "1-100",
        "start_sequence": 1,
        "end_sequence": 100,
        "count": 100,
        "file_name": "introduction-to-physics-VB001.csv",
        "error": null
      },
      {
        "id": "e4281b8f-6aa7-47e5-bd9b-c6e4a4f2943d",
        "type": "whatsapp_csv",
        "format": "csv",
        "delivery_channel": "whatsapp",
        "status": "sent",
        "exported_at": "2026-03-14T11:05:00Z",
        "completed_at": "2026-03-14T11:05:04Z",
        "exported_by": {
          "id": 1,
          "name": "Admin User"
        },
        "destination_masked": "+20100****567",
        "code_range": "1-100",
        "start_sequence": 1,
        "end_sequence": 100,
        "count": 100,
        "file_name": "introduction-to-physics-VB001.csv",
        "error": null
      }
    ],

    // Recent redemptions
    "recent_redemptions": [
      {
        "id": 100,
        "sequence_number": 42,
        "code": "ABCD-EFGH-JKLM",
        "user": {
          "id": 500,
          "name": "John Doe",
          "phone": "+1234567890"
        },
        "redeemed_at": "2026-03-14T14:30:00Z"
      }
    ]
  }
}
```

#### List Batch Redemptions
```http
GET /api/v1/admin/centers/{centerId}/code-batches/{batchId}/redemptions
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15)
- search: string (optional) - matches student name, phone, sequence number, or full generated code

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 100,
      "sequence_number": 42,
      "code": "ABCD-EFGH-JKLM",
      "user": {
        "id": 500,
        "name": "John Doe",
        "phone": "+1234567890"
      },
      "redeemed_at": "2026-03-14T14:30:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

**Frontend flow guidance:**
- Use `statistics.recent_redemptions` for the compact summary card on the details screen.
- Use `/redemptions` for the full table, pagination, and search.
- Keep the close/update action visible whenever `batch.can_close === true`, including closed batches that still need a sold-limit increase.

#### Expand Batch
```http
POST /api/v1/admin/centers/{centerId}/code-batches/{batchId}/expand
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "additional_quantity": 50
}

Response (200):
{
  "success": true,
  "message": "Batch expanded successfully",
  "data": {
    "id": 1,
    "batch_code": "VB001",
    "quantity": 150,  // Was 100, now 150
    "redeemed_count": 25,
    "available_codes": 125,
    // ... other fields
  }
}

Errors:
- 400: Batch is closed, cannot expand
- 400: additional_quantity must be positive
```

#### Close Batch
```http
POST /api/v1/admin/centers/{centerId}/code-batches/{batchId}/close
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "sold_limit": 60  // Number of codes actually sold
}

Response (200):
{
  "success": true,
  "message": "Batch closed successfully",
  "data": {
    "id": 1,
    "batch_code": "VB001",
    "quantity": 100,
    "sold_limit": 60,
    "redeemed_count": 25,
    "status": "closed",
    "status_label": "Closed",
    "closed_at": "2026-03-14T16:00:00Z",
    "closed_by": {
      "id": 1,
      "name": "Admin User"
    },
    // Invoice info
    "invoice_amount_codes": 60,
    "remaining_redemptions": 35  // sold_limit - redeemed_count
  }
}

If the batch is already closed and support needs to honor extra offline sales, call the same endpoint again with a higher `sold_limit`. The batch stays closed and only the limit is increased.

Errors:
- 400: sold_limit cannot be less than redeemed_count (25)
- 400: sold_limit cannot exceed quantity (100)
- 400: closed batch sold_limit can only be increased
```

#### Export as CSV
```http
GET /api/v1/admin/centers/{centerId}/code-batches/{batchId}/export/csv
Authorization: Bearer {token}

Query Parameters:
- start_sequence: int (optional) - Start from this sequence number
- end_sequence: int (optional) - End at this sequence number

Response: File download (text/csv)

Content-Disposition: attachment; filename="VB001_codes.csv"

CSV Content:
sequence,code,video_title,course_title,view_limit
1,ABCD-AAAA-JKLM,Introduction to Physics,Physics 101,3
2,ABCD-AAAB-MNPQ,Introduction to Physics,Physics 101,3
3,ABCD-AAAC-RSTU,Introduction to Physics,Physics 101,3
...
```

#### Export as PDF
```http
GET /api/v1/admin/centers/{centerId}/code-batches/{batchId}/export/pdf
Authorization: Bearer {token}

Query Parameters:
- start_sequence: int (optional)
- end_sequence: int (optional)
- cards_per_page: int (optional, default: 8, allowed: 4 | 6 | 8)

Response: File download (application/pdf)

Content-Disposition: attachment; filename="VB001_codes.pdf"

PDF contains cards with:
- QR Code (encoding the full code)
- Code text (ABCD-EFGH-JKLM)
- Video title
- Course title
- View limit
- Center branding (optional)
- Use PDF for printable card sheets; use CSV for larger bulk exports
```

#### Send CSV to WhatsApp
```http
POST /api/v1/admin/centers/{centerId}/code-batches/{batchId}/send-whatsapp-csv
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_number": "+201001234567",
  "start_sequence": 1,
  "end_sequence": 100
}

Response (200 or 202-style success payload):
{
  "success": true,
  "message": "WhatsApp CSV send started.",
  "data": {
    "id": "e4281b8f-6aa7-47e5-bd9b-c6e4a4f2943d",
    "type": "whatsapp_csv",
    "format": "csv",
    "delivery_channel": "whatsapp",
    "status": "processing",
    "exported_at": "2026-03-14T11:05:00Z",
    "exported_by": {
      "id": 1,
      "name": "Admin User"
    },
    "destination_masked": "+20100****567",
    "code_range": "1-100",
    "start_sequence": 1,
    "end_sequence": 100,
    "count": 100,
    "file_name": "introduction-to-physics-VB001.csv",
    "error": null
  }
}
```

Notes:
- The backend uses the existing batch export history as the audit trail.
- `status` may be `processing`, `sent`, or `failed` depending on the queue mode and current job progress.
- Only CSV is supported for WhatsApp delivery in v1.

---

## Complete Workflows

### Workflow 1: Create Video Code Course

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Step 1: Create Course                                                   │
│  POST /admin/centers/{c}/courses                                         │
│  Body: { "title": "...", "access_model": "video_code" }                  │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 2: Add Videos                                                      │
│  POST /admin/centers/{c}/courses/{co}/videos                             │
│  (Standard video upload flow)                                            │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 3: Generate Code Batch for Video                                   │
│  POST /admin/centers/{c}/courses/{co}/videos/{v}/code-batches            │
│  Body: { "quantity": 100, "view_limit_per_code": 3 }                     │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 4: Export Codes                                                    │
│  GET /admin/centers/{c}/code-batches/{b}/export/csv                      │
│  GET /admin/centers/{c}/code-batches/{b}/export/pdf                      │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 5: Distribute Codes (Outside System)                               │
│  - Print PDF cards                                                       │
│  - Or send CSV codes digitally                                           │
│  - Sell to students                                                      │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 6: Monitor Redemptions                                             │
│  GET /admin/centers/{c}/code-batches/{b}/statistics                      │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 7: Close Batch (When Ready to Invoice)                             │
│  POST /admin/centers/{c}/code-batches/{b}/close                          │
│  Body: { "sold_limit": 60 }                                              │
│  → Invoice for 60 codes                                                  │
└─────────────────────────────────────────────────────────────────────────┘
```

### Workflow 2: Expand Existing Batch

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Scenario: Need more codes for a video                                   │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 1: Check Current Batch                                             │
│  GET /admin/centers/{c}/code-batches/{b}                                 │
│  Response: { "quantity": 100, "status": "open" }                         │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 2: Expand Batch                                                    │
│  POST /admin/centers/{c}/code-batches/{b}/expand                         │
│  Body: { "additional_quantity": 50 }                                     │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 3: Export New Codes Only                                           │
│  GET /admin/centers/{c}/code-batches/{b}/export/csv?start_sequence=101   │
│  → Downloads codes 101-150 only                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Workflow 3: Close Batch for Invoicing

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Scenario: Month end, need to invoice client                             │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 1: Review Statistics                                               │
│  GET /admin/centers/{c}/code-batches/{b}/statistics                      │
│  Response: {                                                             │
│    "total_codes": 100,                                                   │
│    "redeemed_count": 25,                                                 │
│    "available_count": 75                                                 │
│  }                                                                       │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 2: Confirm Sold Count with Client                                  │
│  (Outside system - phone call, email, etc.)                              │
│  Client confirms: "We sold 60 codes"                                     │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 3: Close Batch                                                     │
│  POST /admin/centers/{c}/code-batches/{b}/close                          │
│  Body: { "sold_limit": 60 }                                              │
│                                                                          │
│  Result:                                                                 │
│  - 25 codes already redeemed (still valid)                               │
│  - 35 more codes can be redeemed (60 - 25)                               │
│  - 40 codes become invalid (100 - 60)                                    │
│  - Invoice client for 60 codes                                           │
├─────────────────────────────────────────────────────────────────────────┤
│  Step 4: Generate Invoice (Future Feature)                               │
│  System generates invoice based on sold_limit                            │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message"
  }
}
```

### Batch Creation Errors

| HTTP | Error Code | Message | Cause |
|------|------------|---------|-------|
| 400 | `COURSE_NOT_VIDEO_CODE_MODEL` | "Course does not use video code access model" | Trying to create batch for enrollment course |
| 400 | `VIDEO_HAS_OPEN_BATCH` | "An open batch already exists for this course video" | Must close or expand the existing batch for that same course/video pair |
| 400 | `VALIDATION_ERROR` | "The quantity field is required" | Missing required field |
| 404 | `VIDEO_NOT_FOUND` | "Video not found" | Invalid video ID |
| 403 | `UNAUTHORIZED` | "You don't have permission" | Not admin of this center |

### Batch Expansion Errors

| HTTP | Error Code | Message | Cause |
|------|------------|---------|-------|
| 400 | `BATCH_IS_CLOSED` | "Cannot expand a closed batch" | Batch already closed |
| 400 | `VALIDATION_ERROR` | "Additional quantity must be positive" | Invalid input |

### Batch Close Errors

| HTTP | Error Code | Message | Cause |
|------|------------|---------|-------|
| 400 | `SOLD_LIMIT_DECREASE_NOT_ALLOWED` | "Closed batch sold limit can only be increased" | Attempted to reduce a closed batch's sold limit |
| 400 | `SOLD_LIMIT_TOO_LOW` | "Sold limit cannot be less than redeemed count (25)" | Would invalidate used codes |
| 400 | `SOLD_LIMIT_TOO_HIGH` | "Sold limit cannot exceed quantity (100)" | Invalid limit |

### Export Errors

| HTTP | Error Code | Message | Cause |
|------|------------|---------|-------|
| 400 | `INVALID_SEQUENCE_RANGE` | "End sequence must be greater than start" | Bad range |
| 400 | `SEQUENCE_OUT_OF_RANGE` | "Sequence number exceeds batch quantity" | Out of bounds |

---

## UI/UX Guidelines

### Course Form

```
┌─────────────────────────────────────────────────────────────────────────┐
│  CREATE COURSE                                                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Title (English): [________________________]                             │
│  Title (Arabic):  [________________________]                             │
│                                                                          │
│  Access Model:    [▼ Video Code Based (Offline Sales)  ]                │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ ℹ️  Video Code Model                                            │    │
│  │                                                                  │    │
│  │ Students will purchase codes offline and redeem them in the     │    │
│  │ app to access individual videos. You'll generate code batches   │    │
│  │ and invoice based on sold codes.                                 │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  Category:        [▼ Physics                           ]                │
│  Language:        [▼ English                           ]                │
│                                                                          │
│                                            [Cancel]  [Create Course]    │
└─────────────────────────────────────────────────────────────────────────┘
```

### Batch List View

```
┌─────────────────────────────────────────────────────────────────────────┐
│  VIDEO CODE BATCHES                              [+ Create Batch]        │
├─────────────────────────────────────────────────────────────────────────┤
│  Filters: [All Courses ▼] [All Videos ▼] [All Status ▼]  [Search...]   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ VB001                                              🟢 Open       │    │
│  │ Introduction to Physics • Physics 101                           │    │
│  │                                                                  │    │
│  │ Codes: 100    Redeemed: 25 (25%)    View Limit: 3               │    │
│  │ Created: Mar 14, 2026 by Admin User                              │    │
│  │                                                                  │    │
│  │ [📥 Export CSV] [📄 Export PDF] [➕ Expand] [🔒 Close]           │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ VB002                                              🔴 Closed     │    │
│  │ Newton's Laws • Physics 101                                      │    │
│  │                                                                  │    │
│  │ Codes: 50    Sold: 40    Redeemed: 38 (95%)    View Limit: 2    │    │
│  │ Created: Mar 10, 2026 • Closed: Mar 13, 2026                     │    │
│  │                                                                  │    │
│  │ [📥 Export CSV] [📄 Export PDF] [📊 Statistics]                  │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Batch Details View

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ← Back to Batches                                                       │
│                                                                          │
│  BATCH VB001                                          🟢 Open            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌──────────────────────────┐  ┌──────────────────────────┐             │
│  │ VIDEO                     │  │ COURSE                   │             │
│  │ Introduction to Physics   │  │ Physics 101              │             │
│  └──────────────────────────┘  └──────────────────────────┘             │
│                                                                          │
│  STATISTICS                                                              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐                   │
│  │   100    │ │    25    │ │    75    │ │    3     │                   │
│  │  Total   │ │ Redeemed │ │Available │ │View Limit│                   │
│  │  Codes   │ │  (25%)   │ │          │ │ Per Code │                   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘                   │
│                                                                          │
│  ACTIONS                                                                 │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ [📥 Export CSV]  [📄 Export PDF]  [➕ Expand Batch]  [🔒 Close] │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  EXPORT HISTORY                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ Mar 14, 2026 10:30 AM • CSV • Codes 1-100 • Admin User          │    │
│  │ Mar 14, 2026 11:00 AM • PDF • Codes 1-50  • Admin User          │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  RECENT REDEMPTIONS                                                      │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ Code           │ Student        │ Redeemed At                   │    │
│  │────────────────│────────────────│───────────────────────────────│    │
│  │ VB001-00042    │ John Doe       │ Mar 14, 2026 2:30 PM          │    │
│  │ VB001-00037    │ Jane Smith     │ Mar 14, 2026 1:15 PM          │    │
│  │ VB001-00028    │ Ahmed Hassan   │ Mar 14, 2026 12:45 PM         │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Close Batch Modal

```
┌─────────────────────────────────────────────────────────────────────────┐
│  CLOSE BATCH VB001                                              [X]     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ⚠️  Closing a batch is final and cannot be undone.                     │
│                                                                          │
│  Current Status:                                                         │
│  • Total Codes: 100                                                      │
│  • Already Redeemed: 25                                                  │
│  • Available: 75                                                         │
│                                                                          │
│  How many codes were actually sold?                                      │
│                                                                          │
│  Sold Count: [60_______]                                                │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │ Result Preview:                                                  │    │
│  │ • 25 codes already redeemed → Remain valid                      │    │
│  │ • 35 more codes can be redeemed (60 - 25)                       │    │
│  │ • 40 codes will become invalid (100 - 60)                       │    │
│  │ • Invoice amount: 60 codes                                       │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  [✓] I confirm the sold count is correct                                │
│                                                                          │
│                                    [Cancel]  [Close Batch & Invoice]    │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Code Examples

### TypeScript Interfaces

```typescript
// Enums
type CourseAccessModel = 'enrollment' | 'video_code';
type BatchStatus = 'open' | 'closed';

// Batch
interface VideoCodeBatch {
  id: number;
  batch_code: string;
  video_id: number;
  video_title: string;
  course_id: number;
  course_title: string;
  center_id: number;
  quantity: number;
  sold_limit: number | null;
  redeemed_count: number;
  view_limit_per_code: number;
  status: BatchStatus;
  status_label: string;
  generated_by: User;
  generated_at: string;
  closed_at: string | null;
  closed_by: User | null;
  metadata: {
    exports: ExportRecord[];
  };
  created_at: string;
  updated_at: string;

  // Computed
  available_codes: number;
  redemption_rate: number;
  can_expand: boolean;
  can_close: boolean;
  remaining_redemptions: number | null;
  invoice_amount_codes: number | null;
}

interface ExportRecord {
  id: string | null;
  type: 'download' | 'whatsapp_csv';
  format: 'csv' | 'pdf';
  delivery_channel: 'download' | 'whatsapp';
  status: 'processing' | 'completed' | 'sent' | 'failed';
  exported_at: string;
  completed_at: string | null;
  exported_by: {
    id: number;
    name: string;
  } | null;
  destination_masked: string | null;
  code_range: string | null;
  start_sequence: number | null;
  end_sequence: number | null;
  count: number | null;
  file_name: string | null;
  error: string | null;
}

interface BatchStatistics {
  batch_id: number;
  batch_code: string;
  video_id: number;
  course_id: number;
  quantity: number;
  total_codes: number;
  redeemed_count: number;
  available_count: number;
  sold_limit: number | null;
  remaining: number | null;
  redemption_rate: number;
  status: BatchStatus;
  is_open: boolean;
  first_redemption_at: string | null;
  last_redemption_at: string | null;
  exports: ExportRecord[];
  recent_redemptions: Redemption[];
}

interface Redemption {
  id: number;
  sequence_number: number;
  code: string;
  user: {
    id: number;
    name: string;
    phone: string;
  } | null;
  redeemed_at: string;
}

interface ListVideoCodeBatchRedemptionsParams {
  page?: number;
  per_page?: number;
  search?: string;
}

// API Request/Response types
interface CreateBatchRequest {
  quantity: number;
  view_limit_per_code?: number;
}

interface ExpandBatchRequest {
  additional_quantity: number;
}

interface CloseBatchRequest {
  sold_limit: number;
}
```

### React Component - Batch List

```tsx
import React, { useState, useEffect } from 'react';
import { Table, Button, Tag, Space, Modal, message } from 'antd';
import { DownloadOutlined, PlusOutlined, LockOutlined } from '@ant-design/icons';

interface BatchListProps {
  centerId: number;
}

export function BatchList({ centerId }: BatchListProps) {
  const [batches, setBatches] = useState<VideoCodeBatch[]>([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    courseId: null,
    videoId: null,
    status: null,
  });

  useEffect(() => {
    fetchBatches();
  }, [centerId, filters]);

  async function fetchBatches() {
    setLoading(true);
    try {
      const response = await api.get(`/admin/centers/${centerId}/video-code-batches`, {
        params: filters,
      });
      setBatches(response.data.data);
    } catch (error) {
      message.error('Failed to load batches');
    } finally {
      setLoading(false);
    }
  }

  async function handleExport(batchId: number, format: 'csv' | 'pdf') {
    try {
      const response = await api.get(
        `/admin/centers/${centerId}/code-batches/${batchId}/export/${format}`,
        { responseType: 'blob' }
      );

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `batch_${batchId}.${format}`);
      document.body.appendChild(link);
      link.click();
      link.remove();

      message.success(`${format.toUpperCase()} exported successfully`);
    } catch (error) {
      message.error('Export failed');
    }
  }

  function showCloseModal(batch: VideoCodeBatch) {
    Modal.confirm({
      title: `Close Batch ${batch.batch_code}`,
      content: (
        <CloseBatchForm
          batch={batch}
          onClose={async (soldLimit) => {
            await api.post(
              `/admin/centers/${centerId}/code-batches/${batch.id}/close`,
              { sold_limit: soldLimit }
            );
            fetchBatches();
          }}
        />
      ),
    });
  }

  const columns = [
    {
      title: 'Batch',
      dataIndex: 'batch_code',
      render: (code: string, batch: VideoCodeBatch) => (
        <div>
          <strong>{code}</strong>
          <br />
          <small>{batch.video_title}</small>
        </div>
      ),
    },
    {
      title: 'Course',
      dataIndex: 'course_title',
    },
    {
      title: 'Codes',
      render: (batch: VideoCodeBatch) => (
        <div>
          <div>Total: {batch.quantity}</div>
          <div>Redeemed: {batch.redeemed_count} ({batch.redemption_rate}%)</div>
          {batch.sold_limit && <div>Sold Limit: {batch.sold_limit}</div>}
        </div>
      ),
    },
    {
      title: 'View Limit',
      dataIndex: 'view_limit_per_code',
    },
    {
      title: 'Status',
      dataIndex: 'status',
      render: (status: BatchStatus) => (
        <Tag color={status === 'open' ? 'green' : 'red'}>
          {status.toUpperCase()}
        </Tag>
      ),
    },
    {
      title: 'Actions',
      render: (batch: VideoCodeBatch) => (
        <Space>
          <Button
            icon={<DownloadOutlined />}
            onClick={() => handleExport(batch.id, 'csv')}
          >
            CSV
          </Button>
          <Button
            icon={<DownloadOutlined />}
            onClick={() => handleExport(batch.id, 'pdf')}
          >
            PDF
          </Button>
          {batch.can_expand && (
            <Button
              icon={<PlusOutlined />}
              onClick={() => showExpandModal(batch)}
            >
              Expand
            </Button>
          )}
          {batch.can_close && (
            <Button
              icon={<LockOutlined />}
              danger
              onClick={() => showCloseModal(batch)}
            >
              Close
            </Button>
          )}
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div className="header">
        <h1>Video Code Batches</h1>
        <Button type="primary" onClick={() => navigate('create')}>
          Create Batch
        </Button>
      </div>

      <Table
        columns={columns}
        dataSource={batches}
        loading={loading}
        rowKey="id"
      />
    </div>
  );
}
```

### React Component - Create Batch Form

```tsx
import React from 'react';
import { Form, InputNumber, Button, Select, message } from 'antd';

interface CreateBatchFormProps {
  centerId: number;
  videos: Array<Video & { course_id: number }>;  // Videos from video_code courses
  onSuccess: () => void;
}

export function CreateBatchForm({ centerId, videos, onSuccess }: CreateBatchFormProps) {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  async function handleSubmit(values: any) {
    setLoading(true);
    try {
      const selectedVideo = videos.find(v => v.id === values.video_id);
      if (!selectedVideo) {
        throw new Error('Please select a valid video');
      }

      await api.post(
        `/admin/centers/${centerId}/courses/${selectedVideo.course_id}/videos/${selectedVideo.id}/code-batches`,
        {
          quantity: values.quantity,
          view_limit_per_code: values.view_limit_per_code,
        }
      );
      message.success('Batch created successfully');
      onSuccess();
    } catch (error) {
      const errorMessage = error.response?.data?.error?.message || 'Failed to create batch';
      message.error(errorMessage);
    } finally {
      setLoading(false);
    }
  }

  return (
    <Form
      form={form}
      layout="vertical"
      onFinish={handleSubmit}
      initialValues={{
        view_limit_per_code: 2,
      }}
    >
      <Form.Item
        name="video_id"
        label="Video"
        rules={[{ required: true, message: 'Please select a video' }]}
      >
        <Select
          placeholder="Select video"
          options={videos.map(v => ({
            value: v.id,
            label: `${v.title} (${v.course_title})`,
          }))}
        />
      </Form.Item>

      <Form.Item
        name="quantity"
        label="Number of Codes"
        rules={[
          { required: true, message: 'Please enter quantity' },
          { type: 'number', min: 1, max: 10000, message: 'Must be between 1 and 10000' },
        ]}
      >
        <InputNumber
          min={1}
          max={10000}
          placeholder="e.g., 100"
          style={{ width: '100%' }}
        />
      </Form.Item>

      <Form.Item
        name="view_limit_per_code"
        label="View Limit Per Code"
        tooltip="How many times can each code holder watch the video?"
        rules={[
          { required: true, message: 'Please enter view limit' },
          { type: 'number', min: 1, max: 100, message: 'Must be between 1 and 100' },
        ]}
      >
        <InputNumber
          min={1}
          max={100}
          placeholder="e.g., 3"
          style={{ width: '100%' }}
        />
      </Form.Item>

      <Form.Item>
        <Button type="primary" htmlType="submit" loading={loading} block>
          Create Batch
        </Button>
      </Form.Item>
    </Form>
  );
}
```

### React Component - Close Batch Modal

```tsx
import React, { useState } from 'react';
import { Modal, Form, InputNumber, Checkbox, Alert, Space } from 'antd';

interface CloseBatchModalProps {
  visible: boolean;
  batch: VideoCodeBatch;
  onClose: () => void;
  onConfirm: (soldLimit: number) => Promise<void>;
}

export function CloseBatchModal({ visible, batch, onClose, onConfirm }: CloseBatchModalProps) {
  const [soldLimit, setSoldLimit] = useState<number>(batch.redeemed_count);
  const [confirmed, setConfirmed] = useState(false);
  const [loading, setLoading] = useState(false);

  const remainingRedemptions = soldLimit - batch.redeemed_count;
  const invalidatedCodes = batch.quantity - soldLimit;

  async function handleConfirm() {
    if (!confirmed) return;

    setLoading(true);
    try {
      await onConfirm(soldLimit);
      onClose();
    } catch (error) {
      // Error handled by parent
    } finally {
      setLoading(false);
    }
  }

  return (
    <Modal
      title={`Close Batch ${batch.batch_code}`}
      open={visible}
      onCancel={onClose}
      onOk={handleConfirm}
      okText="Close Batch & Invoice"
      okButtonProps={{ danger: true, disabled: !confirmed, loading }}
    >
      <Alert
        type="warning"
        message="Closing a batch is final and cannot be undone."
        style={{ marginBottom: 16 }}
      />

      <div style={{ marginBottom: 16 }}>
        <strong>Current Status:</strong>
        <ul>
          <li>Total Codes: {batch.quantity}</li>
          <li>Already Redeemed: {batch.redeemed_count}</li>
          <li>Available: {batch.quantity - batch.redeemed_count}</li>
        </ul>
      </div>

      <Form layout="vertical">
        <Form.Item
          label="How many codes were actually sold?"
          required
        >
          <InputNumber
            min={batch.redeemed_count}
            max={batch.quantity}
            value={soldLimit}
            onChange={(value) => setSoldLimit(value || batch.redeemed_count)}
            style={{ width: '100%' }}
          />
        </Form.Item>
      </Form>

      <Alert
        type="info"
        message="Result Preview"
        description={
          <ul style={{ margin: 0, paddingLeft: 20 }}>
            <li>{batch.redeemed_count} codes already redeemed → Remain valid</li>
            <li>{remainingRedemptions} more codes can be redeemed</li>
            <li>{invalidatedCodes} codes will become invalid</li>
            <li><strong>Invoice amount: {soldLimit} codes</strong></li>
          </ul>
        }
        style={{ marginBottom: 16 }}
      />

      <Checkbox
        checked={confirmed}
        onChange={(e) => setConfirmed(e.target.checked)}
      >
        I confirm the sold count is correct
      </Checkbox>
    </Modal>
  );
}
```

### API Service

```typescript
// services/videoCodeBatchService.ts

import { api } from './client';

export const videoCodeBatchService = {
  // List batches
  async list(centerId: number, params?: {
    page?: number;
    per_page?: number;
    course_id?: number;
    video_id?: number;
    status?: 'open' | 'closed';
  }) {
    const response = await api.get(`/admin/centers/${centerId}/video-code-batches`, { params });
    return response.data;
  },

  // Get batch details
  async get(centerId: number, batchId: number) {
    const response = await api.get(`/admin/centers/${centerId}/code-batches/${batchId}`);
    return response.data;
  },

  // Get batch statistics
  async getStatistics(centerId: number, batchId: number) {
    const response = await api.get(`/admin/centers/${centerId}/code-batches/${batchId}/statistics`);
    return response.data;
  },

  // Create batch
  async create(centerId: number, courseId: number, videoId: number, data: {
    quantity: number;
    view_limit_per_code?: number;
  }) {
    const response = await api.post(
      `/admin/centers/${centerId}/courses/${courseId}/videos/${videoId}/code-batches`,
      data
    );
    return response.data;
  },

  // Expand batch
  async expand(centerId: number, batchId: number, additionalQuantity: number) {
    const response = await api.post(
      `/admin/centers/${centerId}/code-batches/${batchId}/expand`,
      { additional_quantity: additionalQuantity }
    );
    return response.data;
  },

  // Close batch
  async close(centerId: number, batchId: number, soldLimit: number) {
    const response = await api.post(
      `/admin/centers/${centerId}/code-batches/${batchId}/close`,
      { sold_limit: soldLimit }
    );
    return response.data;
  },

  // Export CSV
  async exportCsv(centerId: number, batchId: number, options?: {
    start_sequence?: number;
    end_sequence?: number;
  }) {
    const response = await api.get(
      `/admin/centers/${centerId}/code-batches/${batchId}/export/csv`,
      {
        params: options,
        responseType: 'blob',
      }
    );
    return response.data;
  },

  // Export PDF
  async exportPdf(centerId: number, batchId: number, options?: {
    start_sequence?: number;
    end_sequence?: number;
    cards_per_page?: number;
  }) {
    const response = await api.get(
      `/admin/centers/${centerId}/code-batches/${batchId}/export/pdf`,
      {
        params: options,
        responseType: 'blob',
      }
    );
    return response.data;
  },
};
```

---

## Summary: Dashboard Changes Required

### New Screens

| Screen | Priority | Description |
|--------|----------|-------------|
| Batch List | High | List all batches with filters |
| Create Batch | High | Form to create new batch |
| Batch Details | High | View batch info and stats |
| Close Batch Modal | High | Confirm sold count and close |
| Expand Batch Modal | Medium | Add more codes |

### Modified Screens

| Screen | Change |
|--------|--------|
| Course Create/Edit | Add `access_model` field |
| Course Details | Show "Manage Batches" link for video_code courses |
| Video List (in course) | Show batch info for video_code courses |

### Navigation

```
Dashboard
├── Courses
│   ├── [Course with access_model: video_code]
│   │   └── Manage Code Batches → Batch List
│   └── [Course with access_model: enrollment]
│       └── (existing flow)
└── Video Code Batches (new menu item)
    ├── List All Batches
    ├── Create Batch
    └── Batch Details
        ├── Statistics
        ├── Export CSV/PDF
        ├── Expand
        └── Close
```

---

## Questions?

Contact backend team for:
- API clarifications
- New feature requests
- Error code additions

Document version: 1.0
Last updated: 2026-03-14
