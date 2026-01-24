# Database Schema

> Complete schema documentation for playback, device, and settings tables.

## Playback Sessions

### Table: `playback_sessions`

Tracks video viewing sessions with embed token management.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `user_id` | bigint (FK) | No | References `users.id` |
| `video_id` | bigint (FK) | No | References `videos.id` |
| `course_id` | bigint (FK) | Yes | References `courses.id` |
| `enrollment_id` | bigint (FK) | Yes | References `enrollments.id` |
| `device_id` | bigint (FK) | No | References `user_devices.id` |
| `embed_token` | text | Yes | Bunny embed token hash |
| `embed_token_expires_at` | timestamp | Yes | Token expiration time |
| `started_at` | timestamp | No | Session start time |
| `ended_at` | timestamp | Yes | Session end time (null = active) |
| `expires_at` | timestamp | Yes | Session expiration (for heartbeat) |
| `last_activity_at` | timestamp | Yes | Last progress update time |
| `progress_percent` | unsigned int | No | Progress 0-100, default 0 |
| `is_full_play` | boolean | No | True if reached 80%, default false |
| `auto_closed` | boolean | No | True if closed by timeout/max_views |
| `is_locked` | boolean | No | True when no remaining views |
| `watch_duration` | unsigned int | No | Total seconds watched, default 0 |
| `close_reason` | varchar(20) | Yes | user/timeout/max_views |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Indexes:**
- `(user_id, video_id)` - Lookup sessions by user/video
- `(course_id, user_id)` - Course-scoped queries
- `embed_token_expires_at` - Token refresh queries
- `ended_at` - Active session queries
- `expires_at` - Expiration queries
- `(ended_at, last_activity_at)` - Cleanup job index

**Model:** `App\Models\PlaybackSession`

**Migrations:**
- `2025_11_29_063518_create_playback_sessions_table.php`
- `2025_12_27_000000_add_expires_at_to_playback_sessions_table.php`
- `2026_01_20_024547_add_embed_token_to_playback_sessions_table.php`
- `2026_01_21_234644_add_session_management_to_playback_sessions_table.php`

---

## User Devices

### Table: `user_devices`

Tracks registered mobile devices per student.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `user_id` | bigint (FK) | No | References `users.id` |
| `device_id` | varchar | No | Device UUID from mobile app |
| `model` | varchar | No | Device model (e.g., "iPhone 14 Pro") |
| `os_version` | varchar | No | OS version (e.g., "iOS 17.2") |
| `status` | tinyint | No | 0=active, 1=revoked, 2=pending |
| `approved_at` | timestamp | Yes | When device was approved |
| `last_used_at` | timestamp | Yes | Last activity timestamp |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Unique Constraints:**
- `(user_id, device_id)` - One record per device per user

**Status Values:**
```php
const STATUS_ACTIVE = 0;   // Device can be used
const STATUS_REVOKED = 1;  // Device deactivated
const STATUS_PENDING = 2;  // Awaiting approval
```

**Model:** `App\Models\UserDevice`

**Migration:** `2025_11_29_054523_create_user_devices_table.php`

---

## Device Change Requests

### Table: `device_change_requests`

Workflow for students requesting device changes.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `user_id` | bigint (FK) | No | References `users.id` |
| `center_id` | bigint (FK) | Yes | References `centers.id` |
| `current_device_id` | varchar | No | UUID of current device |
| `new_device_id` | varchar | No | UUID of requested device |
| `new_model` | varchar | No | New device model |
| `new_os_version` | varchar | No | New device OS |
| `status` | varchar | No | PENDING/APPROVED/REJECTED |
| `reason` | text | Yes | Student's reason for request |
| `decision_reason` | text | Yes | Admin's decision reason |
| `decided_by` | bigint (FK) | Yes | References `users.id` (admin) |
| `decided_at` | timestamp | Yes | Decision timestamp |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Indexes:**
- `(user_id, status)` - Find pending requests per user

**Status Values:**
```php
const STATUS_PENDING = 'PENDING';
const STATUS_APPROVED = 'APPROVED';
const STATUS_REJECTED = 'REJECTED';
```

**Model:** `App\Models\DeviceChangeRequest`

**Migration:** `2025_12_21_000000_create_device_change_requests_table.php`

---

## Extra View Requests

### Table: `extra_view_requests`

Workflow for students requesting additional video views.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `user_id` | bigint (FK) | No | References `users.id` |
| `video_id` | bigint (FK) | No | References `videos.id` |
| `course_id` | bigint (FK) | No | References `courses.id` |
| `center_id` | bigint (FK) | No | References `centers.id` |
| `status` | varchar | No | PENDING/APPROVED/REJECTED |
| `reason` | text | Yes | Student's reason for request |
| `granted_views` | unsigned int | Yes | Views granted (only on approval) |
| `decision_reason` | text | Yes | Admin's decision reason |
| `decided_by` | bigint (FK) | Yes | References `users.id` (admin) |
| `decided_at` | timestamp | Yes | Decision timestamp |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Indexes:**
- `(user_id, video_id, status)` - Find requests per user/video
- `(center_id, status)` - Admin queries by center

**Status Values:**
```php
const STATUS_PENDING = 'PENDING';
const STATUS_APPROVED = 'APPROVED';
const STATUS_REJECTED = 'REJECTED';
```

**Model:** `App\Models\ExtraViewRequest`

**Migration:** `2025_12_20_000000_create_extra_view_requests_table.php`

---

## Course-Video Pivot

### Table: `course_video`

Links videos to courses with view limit overrides.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `course_id` | bigint (FK) | No | References `courses.id` |
| `video_id` | bigint (FK) | No | References `videos.id` |
| `section_id` | bigint (FK) | Yes | References `sections.id` |
| `order_index` | unsigned int | No | Display order, default 0 |
| `visible` | boolean | No | Whether video is visible, default true |
| `view_limit_override` | unsigned int | Yes | Override center/course limit |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Unique Constraints:**
- `(course_id, video_id, section_id)` - One attachment per context

**Model:** `App\Models\Pivots\CourseVideo`

**Migration:** `2025_11_29_061723_create_course_video_table.php`

---

## Settings Tables

All settings tables follow a similar JSON-based structure.

### Table: `system_settings`

Global system-wide settings (key-value store).

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `key` | varchar | No | Setting key (unique) |
| `value` | json | Yes | Setting value |
| `is_public` | boolean | No | Exposed to frontend, default false |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Migration:** `2025_11_29_064133_create_system_settings_table.php`

---

### Table: `center_settings`

Per-center settings (JSON blob).

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `center_id` | bigint (FK) | No | References `centers.id` (unique) |
| `settings` | json | No | Settings JSON object |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Note:** Center also has direct columns for defaults:
- `default_view_limit` (int)
- `allow_extra_view_requests` (bool)
- `pdf_download_permission` (bool)
- `device_limit` (int)

**Migration:** `2025_11_29_064135_create_center_settings_table.php`

---

### Table: `course_settings`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `course_id` | bigint (FK) | No | References `courses.id` |
| `settings` | json | No | Settings JSON object |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Migration:** `2025_11_29_064136_create_course_settings_table.php`

---

### Table: `video_settings`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `video_id` | bigint (FK) | No | References `videos.id` |
| `settings` | json | No | Settings JSON object |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Migration:** `2025_11_29_064137_create_video_settings_table.php`

---

### Table: `student_settings`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | bigint (PK) | No | Primary key |
| `user_id` | bigint (FK) | No | References `users.id` |
| `settings` | json | No | Settings JSON object |
| `created_at` | timestamp | No | Laravel timestamp |
| `updated_at` | timestamp | No | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete |

**Special keys in student settings:**
- `extra_views` - Object mapping `video_id` to extra view count

**Migration:** `2025_11_29_064138_create_student_settings_table.php`

---

## Entity Relationship Diagram

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     users       │     │     centers     │     │    courses      │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │     │ id              │     │ id              │
│ center_id (FK)──┼────►│ default_view_   │     │ center_id (FK)──┼──►
│ is_student      │     │   limit         │     │ status          │
└────────┬────────┘     │ device_limit    │     └────────┬────────┘
         │              └────────┬────────┘              │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  user_devices   │     │ center_settings │     │ course_settings │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │     │ id              │     │ id              │
│ user_id (FK)    │     │ center_id (FK)  │     │ course_id (FK)  │
│ device_id       │     │ settings (JSON) │     │ settings (JSON) │
│ status          │     └─────────────────┘     └─────────────────┘
└────────┬────────┘
         │
         │
         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│playback_sessions│     │  course_video   │     │     videos      │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │     │ id              │     │ id              │
│ user_id (FK)    │     │ course_id (FK)  │     │ source_id       │
│ video_id (FK)───┼────►│ video_id (FK)───┼────►│ encoding_status │
│ course_id (FK)  │     │ view_limit_     │     │ lifecycle_status│
│ device_id (FK)  │     │   override      │     └────────┬────────┘
│ embed_token     │     │ visible         │              │
│ progress_percent│     └─────────────────┘              ▼
│ is_full_play    │                            ┌─────────────────┐
└─────────────────┘                            │ video_settings  │
                                               ├─────────────────┤
┌─────────────────┐     ┌─────────────────┐    │ id              │
│extra_view_      │     │device_change_   │    │ video_id (FK)   │
│  requests       │     │  requests       │    │ settings (JSON) │
├─────────────────┤     ├─────────────────┤    └─────────────────┘
│ id              │     │ id              │
│ user_id (FK)    │     │ user_id (FK)    │
│ video_id (FK)   │     │ current_device_ │
│ course_id (FK)  │     │   id            │
│ status          │     │ new_device_id   │
│ granted_views   │     │ status          │
└─────────────────┘     └─────────────────┘
```

---

## Common Queries

### Find active session for user
```sql
SELECT * FROM playback_sessions
WHERE user_id = ?
  AND ended_at IS NULL
  AND deleted_at IS NULL
  AND expires_at > NOW()
```

### Count full plays for view limit
```sql
SELECT COUNT(*) FROM playback_sessions
WHERE user_id = ?
  AND video_id = ?
  AND is_full_play = 1
  AND deleted_at IS NULL
```

### Get approved extra views
```sql
SELECT SUM(granted_views) FROM extra_view_requests
WHERE user_id = ?
  AND video_id = ?
  AND status = 'APPROVED'
  AND deleted_at IS NULL
```

### Find active device
```sql
SELECT * FROM user_devices
WHERE user_id = ?
  AND status = 0
  AND deleted_at IS NULL
LIMIT 1
```
