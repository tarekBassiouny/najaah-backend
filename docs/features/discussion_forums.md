# Discussion Forums System

> Q&A discussions and announcements on courses and videos with full moderation, accepted answers, and rich content support.

## Overview

This feature adds a comprehensive discussion system:
- **Scope**: Discussions on courses and individual videos
- **Q&A**: Questions with threaded replies, upvotes, accepted answers
- **Announcements**: Instructor/admin announcements with notifications
- **Moderation**: Report posts, pre-approval option, ban users, admin controls
- **Content**: Rich text (markdown), images, file attachments
- **Updates**: Polling-based refresh for new content

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DISCUSSION FORUMS SYSTEM                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  DISCUSSION SCOPE                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │   Course ───────────────── Course Discussion Forum                   │   │
│  │      │                     • Q&A Threads                             │   │
│  │      │                     • Announcements                           │   │
│  │      │                                                               │   │
│  │      └── Videos ────────── Video Discussion                          │   │
│  │                            • Q&A Threads                             │   │
│  │                            • Timestamp-linked questions              │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  THREAD STRUCTURE                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌──────────────┐                                                   │   │
│  │  │    Thread    │ ← Question or Announcement                        │   │
│  │  │   (Post)     │                                                   │   │
│  │  └──────┬───────┘                                                   │   │
│  │         │                                                            │   │
│  │         ├── Reply 1 ← Can be marked as "Accepted Answer"            │   │
│  │         │     └── Nested Reply 1.1                                  │   │
│  │         │     └── Nested Reply 1.2                                  │   │
│  │         │                                                            │   │
│  │         ├── Reply 2                                                  │   │
│  │         │     └── Nested Reply 2.1                                  │   │
│  │         │                                                            │   │
│  │         └── Reply 3 ✓ Accepted Answer                               │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  MODERATION FLOW                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  Student Posts → [Pre-approval?] → Published → [Reported?] → Review │   │
│  │                        │                            │                │   │
│  │                        ▼                            ▼                │   │
│  │                    Pending                     Hide/Delete           │   │
│  │                    Queue                       Warn/Ban User         │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### discussion_threads

Main discussion threads (questions or announcements).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Center scope |
| `course_id` | FK → courses | Parent course |
| `discussable_type` | varchar | Polymorphic: 'course', 'video' |
| `discussable_id` | bigint | Course or Video ID |
| `user_id` | FK → users | Author |
| `title` | varchar | Thread title |
| `content` | text | Thread body (markdown) |
| `type` | tinyint | 0=question, 1=announcement |
| `status` | tinyint | 0=pending, 1=published, 2=hidden, 3=closed |
| `is_pinned` | boolean | Pinned to top |
| `is_locked` | boolean | No new replies allowed |
| `video_timestamp` | int | Video timestamp in seconds (for video discussions) |
| `replies_count` | int | Cached reply count |
| `upvotes_count` | int | Cached upvote count |
| `views_count` | int | View count |
| `last_reply_at` | timestamp | Last reply time |
| `last_reply_by` | FK → users | Last replier |
| `accepted_reply_id` | FK → discussion_replies | Accepted answer |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[center_id, course_id, status]`
- `[discussable_type, discussable_id]`
- `[user_id]`
- `[status, is_pinned, last_reply_at]`

### discussion_replies

Replies to threads (supports nesting).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `discussion_thread_id` | FK → discussion_threads | Parent thread |
| `parent_reply_id` | FK → discussion_replies | Parent reply (for nesting) |
| `user_id` | FK → users | Author |
| `content` | text | Reply body (markdown) |
| `status` | tinyint | 0=pending, 1=published, 2=hidden |
| `is_accepted` | boolean | Marked as accepted answer |
| `upvotes_count` | int | Cached upvote count |
| `replies_count` | int | Nested replies count |
| `depth` | tinyint | Nesting depth (0, 1, 2 - max 2) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[discussion_thread_id, status]`
- `[parent_reply_id]`
- `[user_id]`
- `[is_accepted]`

### discussion_upvotes

Upvotes on threads and replies.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | User who upvoted |
| `upvotable_type` | varchar | Polymorphic: 'thread', 'reply' |
| `upvotable_id` | bigint | Thread or Reply ID |
| `created_at` | timestamp | |

**Indexes:**
- `UNIQUE [user_id, upvotable_type, upvotable_id]`
- `[upvotable_type, upvotable_id]`

### discussion_attachments

File attachments on threads and replies.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `attachable_type` | varchar | Polymorphic: 'thread', 'reply' |
| `attachable_id` | bigint | Thread or Reply ID |
| `user_id` | FK → users | Uploader |
| `file_name` | varchar | Original filename |
| `file_path` | varchar | Storage path |
| `file_size_kb` | int | File size |
| `file_type` | varchar | MIME type |
| `is_image` | boolean | Is displayable image |
| `created_at` | timestamp | |

**Indexes:**
- `[attachable_type, attachable_id]`

### discussion_reports

Reports on threads and replies.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `reportable_type` | varchar | Polymorphic: 'thread', 'reply' |
| `reportable_id` | bigint | Thread or Reply ID |
| `reported_by` | FK → users | Reporter |
| `reason` | tinyint | 0=spam, 1=inappropriate, 2=harassment, 3=other |
| `description` | text | Additional details |
| `status` | tinyint | 0=pending, 1=reviewed, 2=dismissed |
| `reviewed_by` | FK → users | Admin who reviewed |
| `reviewed_at` | timestamp | |
| `action_taken` | varchar | Action taken description |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[reportable_type, reportable_id]`
- `[status]`
- `[reported_by]`

### discussion_bans

Banned users from discussions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Banned user |
| `center_id` | FK → centers | Center scope (null = all centers) |
| `course_id` | FK → courses | Course scope (null = all courses in center) |
| `reason` | text | Ban reason |
| `banned_by` | FK → users | Admin who banned |
| `banned_until` | timestamp | Null = permanent |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete (for unban) |

**Indexes:**
- `[user_id, center_id, course_id]`
- `[banned_until]`

### discussion_subscriptions

Thread subscriptions for notifications.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK → users | Subscriber |
| `discussion_thread_id` | FK → discussion_threads | Thread |
| `created_at` | timestamp | |

**Indexes:**
- `UNIQUE [user_id, discussion_thread_id]`
- `[discussion_thread_id]`

### discussion_settings

Course-level discussion settings.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `course_id` | FK → courses | Course |
| `is_enabled` | boolean | Discussions enabled |
| `require_approval` | boolean | Posts require admin approval |
| `allow_anonymous` | boolean | Allow anonymous posts |
| `allow_attachments` | boolean | Allow file attachments |
| `max_attachment_size_mb` | int | Max attachment size |
| `allowed_file_types` | JSON | Allowed file extensions |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `UNIQUE [course_id]`

---

## Enums

### ThreadType

```php
enum ThreadType: int
{
    case Question = 0;
    case Announcement = 1;
}
```

### ThreadStatus

```php
enum ThreadStatus: int
{
    case Pending = 0;    // Awaiting approval
    case Published = 1;  // Visible
    case Hidden = 2;     // Hidden by admin
    case Closed = 3;     // No more replies
}
```

### ReplyStatus

```php
enum ReplyStatus: int
{
    case Pending = 0;
    case Published = 1;
    case Hidden = 2;
}
```

### ReportReason

```php
enum ReportReason: int
{
    case Spam = 0;
    case Inappropriate = 1;
    case Harassment = 2;
    case Other = 3;
}
```

### ReportStatus

```php
enum ReportStatus: int
{
    case Pending = 0;
    case Reviewed = 1;
    case Dismissed = 2;
}
```

---

## Service Layer

### DiscussionThreadService

```php
interface DiscussionThreadServiceInterface
{
    // CRUD
    public function create(User $user, Course $course, array $data): DiscussionThread;
    public function createForVideo(User $user, Video $video, Course $course, array $data): DiscussionThread;
    public function update(DiscussionThread $thread, User $user, array $data): DiscussionThread;
    public function delete(DiscussionThread $thread, User $user): void;

    // Status management
    public function publish(DiscussionThread $thread, User $admin): DiscussionThread;
    public function hide(DiscussionThread $thread, User $admin): DiscussionThread;
    public function close(DiscussionThread $thread, User $admin): DiscussionThread;
    public function pin(DiscussionThread $thread, User $admin): DiscussionThread;
    public function unpin(DiscussionThread $thread, User $admin): DiscussionThread;
    public function lock(DiscussionThread $thread, User $admin): DiscussionThread;
    public function unlock(DiscussionThread $thread, User $admin): DiscussionThread;

    // Queries
    public function getThreadsForCourse(Course $course, User $user, array $filters): LengthAwarePaginator;
    public function getThreadsForVideo(Video $video, Course $course, User $user, array $filters): LengthAwarePaginator;
    public function getThread(DiscussionThread $thread, User $user): DiscussionThread;

    // Accepted answer
    public function acceptAnswer(DiscussionThread $thread, DiscussionReply $reply, User $user): DiscussionThread;
    public function unacceptAnswer(DiscussionThread $thread, User $user): DiscussionThread;

    // Permissions
    public function canPost(User $user, Course $course): bool;
    public function canEdit(User $user, DiscussionThread $thread): bool;
    public function canModerate(User $user, Course $course): bool;
}
```

### DiscussionReplyService

```php
interface DiscussionReplyServiceInterface
{
    // CRUD
    public function create(DiscussionThread $thread, User $user, array $data): DiscussionReply;
    public function createNested(DiscussionReply $parent, User $user, array $data): DiscussionReply;
    public function update(DiscussionReply $reply, User $user, array $data): DiscussionReply;
    public function delete(DiscussionReply $reply, User $user): void;

    // Status
    public function publish(DiscussionReply $reply, User $admin): DiscussionReply;
    public function hide(DiscussionReply $reply, User $admin): DiscussionReply;

    // Queries
    public function getReplies(DiscussionThread $thread, User $user, array $filters): Collection;
}
```

### DiscussionUpvoteService

```php
interface DiscussionUpvoteServiceInterface
{
    // Upvote/remove
    public function upvote(User $user, DiscussionThread|DiscussionReply $target): void;
    public function removeUpvote(User $user, DiscussionThread|DiscussionReply $target): void;
    public function toggleUpvote(User $user, DiscussionThread|DiscussionReply $target): bool;

    // Check
    public function hasUpvoted(User $user, DiscussionThread|DiscussionReply $target): bool;
}
```

### DiscussionAttachmentService

```php
interface DiscussionAttachmentServiceInterface
{
    // Upload
    public function attach(User $user, DiscussionThread|DiscussionReply $target, UploadedFile $file): DiscussionAttachment;
    public function remove(DiscussionAttachment $attachment, User $user): void;

    // Validation
    public function validateFile(UploadedFile $file, Course $course): bool;
}
```

### DiscussionModerationService

```php
interface DiscussionModerationServiceInterface
{
    // Reports
    public function report(User $user, DiscussionThread|DiscussionReply $target, int $reason, ?string $description): DiscussionReport;
    public function reviewReport(DiscussionReport $report, User $admin, string $action): DiscussionReport;
    public function dismissReport(DiscussionReport $report, User $admin): DiscussionReport;
    public function getPendingReports(Center $center): Collection;

    // Bans
    public function banUser(User $admin, User $user, ?Center $center, ?Course $course, ?string $reason, ?Carbon $until): DiscussionBan;
    public function unbanUser(DiscussionBan $ban, User $admin): void;
    public function isBanned(User $user, Course $course): bool;

    // Bulk actions
    public function bulkApprove(array $threadIds, User $admin): int;
    public function bulkHide(array $threadIds, User $admin): int;
}
```

### DiscussionSubscriptionService

```php
interface DiscussionSubscriptionServiceInterface
{
    // Subscribe/unsubscribe
    public function subscribe(User $user, DiscussionThread $thread): void;
    public function unsubscribe(User $user, DiscussionThread $thread): void;
    public function toggleSubscription(User $user, DiscussionThread $thread): bool;

    // Check
    public function isSubscribed(User $user, DiscussionThread $thread): bool;

    // Notify
    public function notifySubscribers(DiscussionThread $thread, DiscussionReply $reply): void;
}
```

---

## API Endpoints

### Mobile - Threads

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/courses/{course}/discussions` | List course threads |
| GET | `/api/v1/centers/{center}/courses/{course}/videos/{video}/discussions` | List video threads |
| POST | `/api/v1/centers/{center}/courses/{course}/discussions` | Create thread |
| POST | `/api/v1/centers/{center}/courses/{course}/videos/{video}/discussions` | Create video thread |
| GET | `/api/v1/centers/{center}/discussions/{thread}` | Get thread with replies |
| PUT | `/api/v1/centers/{center}/discussions/{thread}` | Update thread |
| DELETE | `/api/v1/centers/{center}/discussions/{thread}` | Delete thread |

**List Filters:**
- `type` - question/announcement
- `status` - for user's own pending posts
- `sort` - latest, popular, unanswered
- `search` - search in title/content
- `page`, `per_page`

### Mobile - Replies

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/discussions/{thread}/replies` | List replies |
| POST | `/api/v1/centers/{center}/discussions/{thread}/replies` | Create reply |
| POST | `/api/v1/centers/{center}/replies/{reply}/replies` | Create nested reply |
| PUT | `/api/v1/centers/{center}/replies/{reply}` | Update reply |
| DELETE | `/api/v1/centers/{center}/replies/{reply}` | Delete reply |

### Mobile - Interactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/centers/{center}/discussions/{thread}/upvote` | Toggle thread upvote |
| POST | `/api/v1/centers/{center}/replies/{reply}/upvote` | Toggle reply upvote |
| POST | `/api/v1/centers/{center}/discussions/{thread}/subscribe` | Toggle subscription |
| POST | `/api/v1/centers/{center}/discussions/{thread}/report` | Report thread |
| POST | `/api/v1/centers/{center}/replies/{reply}/report` | Report reply |

### Mobile - Accepted Answer

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/centers/{center}/discussions/{thread}/accept/{reply}` | Accept answer |
| DELETE | `/api/v1/centers/{center}/discussions/{thread}/accept` | Remove accepted |

### Mobile - Attachments

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/centers/{center}/discussions/{thread}/attachments` | Upload to thread |
| POST | `/api/v1/centers/{center}/replies/{reply}/attachments` | Upload to reply |
| DELETE | `/api/v1/centers/{center}/attachments/{attachment}` | Remove attachment |

### Mobile - Polling

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/centers/{center}/discussions/{thread}/updates` | Check for new replies |
| GET | `/api/v1/centers/{center}/courses/{course}/discussions/updates` | Check for new threads |

### Admin - Thread Moderation

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/discussions` | List all threads |
| GET | `/api/v1/admin/centers/{center}/discussions/pending` | List pending approval |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/approve` | Approve thread |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/hide` | Hide thread |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/close` | Close thread |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/pin` | Pin thread |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/unpin` | Unpin thread |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/lock` | Lock thread |
| POST | `/api/v1/admin/centers/{center}/discussions/{thread}/unlock` | Unlock thread |
| POST | `/api/v1/admin/centers/{center}/discussions/bulk-approve` | Bulk approve |
| POST | `/api/v1/admin/centers/{center}/discussions/bulk-hide` | Bulk hide |

### Admin - Reply Moderation

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/admin/centers/{center}/replies/{reply}/approve` | Approve reply |
| POST | `/api/v1/admin/centers/{center}/replies/{reply}/hide` | Hide reply |

### Admin - Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/discussion-reports` | List reports |
| GET | `/api/v1/admin/centers/{center}/discussion-reports/{report}` | Get report |
| POST | `/api/v1/admin/centers/{center}/discussion-reports/{report}/review` | Review report |
| POST | `/api/v1/admin/centers/{center}/discussion-reports/{report}/dismiss` | Dismiss report |

### Admin - Bans

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/discussion-bans` | List bans |
| POST | `/api/v1/admin/centers/{center}/students/{student}/discussion-ban` | Ban user |
| DELETE | `/api/v1/admin/centers/{center}/discussion-bans/{ban}` | Unban user |

### Admin - Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/courses/{course}/discussion-settings` | Get settings |
| PUT | `/api/v1/admin/centers/{center}/courses/{course}/discussion-settings` | Update settings |

### Admin - Announcements

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/admin/centers/{center}/courses/{course}/announcements` | Create announcement |
| PUT | `/api/v1/admin/centers/{center}/announcements/{thread}` | Update announcement |
| DELETE | `/api/v1/admin/centers/{center}/announcements/{thread}` | Delete announcement |

---

## Notification Integration

### Events

| Event | Trigger | Recipients |
|-------|---------|------------|
| `NewReplyPosted` | Reply added to thread | Thread author + subscribers |
| `AnswerAccepted` | Reply marked as accepted | Reply author |
| `ThreadApproved` | Admin approves pending thread | Thread author |
| `ThreadHidden` | Admin hides thread | Thread author |
| `NewAnnouncement` | Instructor posts announcement | All enrolled students |
| `MentionInPost` | User mentioned with @username | Mentioned user |

### Notification Content

```json
{
  "type": "new_reply",
  "thread_id": 123,
  "thread_title": "How to use Laravel?",
  "reply_id": 456,
  "reply_author": "John Doe",
  "reply_preview": "You can start by...",
  "course_id": 1,
  "course_title": "Laravel Basics"
}
```

---

## Polling Implementation

### Check for Updates

```
GET /api/v1/centers/{center}/discussions/{thread}/updates?since=2026-03-03T10:00:00Z

Response:
{
  "has_updates": true,
  "new_replies_count": 3,
  "last_update_at": "2026-03-03T10:15:00Z"
}
```

### Recommended Polling Intervals

| Context | Interval |
|---------|----------|
| Viewing thread | 30 seconds |
| Thread list | 60 seconds |
| Background | 5 minutes |

---

## Rich Content (Markdown)

### Supported Markdown Features

| Feature | Syntax |
|---------|--------|
| Bold | `**text**` |
| Italic | `*text*` |
| Code inline | `` `code` `` |
| Code block | ``` ```language ``` |
| Links | `[text](url)` |
| Images | `![alt](url)` |
| Lists | `- item` or `1. item` |
| Quotes | `> quote` |
| Headers | `## Header` |
| Mentions | `@username` |

### Content Sanitization

- Strip dangerous HTML tags
- Sanitize URLs (no javascript:)
- Limit image sources to allowed domains
- Parse mentions and create links

---

## Implementation Checklist

### Phase 1: Database Architecture (7 migrations)
- [ ] Create `discussion_threads` table
- [ ] Create `discussion_replies` table
- [ ] Create `discussion_upvotes` table
- [ ] Create `discussion_attachments` table
- [ ] Create `discussion_reports` table
- [ ] Create `discussion_bans` table
- [ ] Create `discussion_subscriptions` table
- [ ] Create `discussion_settings` table

### Phase 2: Enums & Models (8 models)
- [ ] Create enums (5 enums)
- [ ] Create `DiscussionThread` model
- [ ] Create `DiscussionReply` model
- [ ] Create `DiscussionUpvote` model
- [ ] Create `DiscussionAttachment` model
- [ ] Create `DiscussionReport` model
- [ ] Create `DiscussionBan` model
- [ ] Create `DiscussionSubscription` model
- [ ] Create `DiscussionSetting` model

### Phase 3: Service Layer (6 services)
- [ ] Create `DiscussionThreadService`
- [ ] Create `DiscussionReplyService`
- [ ] Create `DiscussionUpvoteService`
- [ ] Create `DiscussionAttachmentService`
- [ ] Create `DiscussionModerationService`
- [ ] Create `DiscussionSubscriptionService`

### Phase 4: Mobile API
- [ ] Thread controller
- [ ] Reply controller
- [ ] Upvote controller
- [ ] Subscription controller
- [ ] Attachment controller
- [ ] Report controller
- [ ] Polling controller
- [ ] Form requests
- [ ] Resources

### Phase 5: Admin API
- [ ] Thread moderation controller
- [ ] Reply moderation controller
- [ ] Report management controller
- [ ] Ban management controller
- [ ] Settings controller
- [ ] Announcement controller
- [ ] Form requests
- [ ] Resources

### Phase 6: Notifications
- [ ] Create notification events
- [ ] Create notification listeners
- [ ] Integrate with notification system (if exists)
- [ ] Email templates for digests

### Phase 7: Content Processing
- [ ] Markdown parser setup
- [ ] Content sanitization
- [ ] Mention parsing
- [ ] Image/attachment handling

### Phase 8: Quality & Testing
- [ ] Create factories
- [ ] Feature tests for thread CRUD
- [ ] Feature tests for replies
- [ ] Feature tests for upvotes
- [ ] Feature tests for moderation
- [ ] Feature tests for reports/bans
- [ ] Unit tests for services
- [ ] Run quality checks

---

## File Summary (~70 files)

```
Migrations (8):
- create_discussion_threads_table.php
- create_discussion_replies_table.php
- create_discussion_upvotes_table.php
- create_discussion_attachments_table.php
- create_discussion_reports_table.php
- create_discussion_bans_table.php
- create_discussion_subscriptions_table.php
- create_discussion_settings_table.php

Enums (5):
- ThreadType.php
- ThreadStatus.php
- ReplyStatus.php
- ReportReason.php
- ReportStatus.php

Models (8):
- DiscussionThread.php
- DiscussionReply.php
- DiscussionUpvote.php
- DiscussionAttachment.php
- DiscussionReport.php
- DiscussionBan.php
- DiscussionSubscription.php
- DiscussionSetting.php

Services (12):
- DiscussionThreadServiceInterface.php + DiscussionThreadService.php
- DiscussionReplyServiceInterface.php + DiscussionReplyService.php
- DiscussionUpvoteServiceInterface.php + DiscussionUpvoteService.php
- DiscussionAttachmentServiceInterface.php + DiscussionAttachmentService.php
- DiscussionModerationServiceInterface.php + DiscussionModerationService.php
- DiscussionSubscriptionServiceInterface.php + DiscussionSubscriptionService.php

Controllers (12):
- Mobile/DiscussionThreadController.php
- Mobile/DiscussionReplyController.php
- Mobile/DiscussionUpvoteController.php
- Mobile/DiscussionSubscriptionController.php
- Mobile/DiscussionAttachmentController.php
- Mobile/DiscussionReportController.php
- Admin/DiscussionModerationController.php
- Admin/DiscussionReportController.php
- Admin/DiscussionBanController.php
- Admin/DiscussionSettingController.php
- Admin/AnnouncementController.php

Events (6):
- NewReplyPosted.php
- AnswerAccepted.php
- ThreadApproved.php
- ThreadHidden.php
- NewAnnouncement.php
- UserMentioned.php

Listeners (6)
Form Requests (~15)
Resources (~10)
Routes (2)
Factories (8)
Tests (~12)
```

---

## Testing Plan

```bash
# Run all discussion tests
php artisan test --filter="Discussion"
php artisan test --filter="Forum"
```

### Key Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Create thread on course | Feature | High |
| Create thread on video with timestamp | Feature | High |
| Reply to thread | Feature | High |
| Nested replies (max depth 2) | Feature | High |
| Upvote/remove upvote | Feature | High |
| Accept answer | Feature | High |
| Report content | Feature | High |
| Admin approve pending | Feature | High |
| Admin hide thread | Feature | High |
| Ban user from discussions | Feature | High |
| Banned user cannot post | Feature | High |
| Polling returns updates | Feature | Medium |
| Markdown rendering | Unit | Medium |
| Mention parsing | Unit | Medium |
| Attachment upload | Feature | Medium |
| Subscription notifications | Feature | Medium |
