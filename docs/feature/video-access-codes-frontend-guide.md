# Video Access Codes - Frontend Implementation Guide

> **Audience:** Frontend/Mobile developers
> **Purpose:** Complete guide to implement video code access flow while supporting existing enrollment flow

---

## Table of Contents

1. [Overview](#overview)
2. [What Changed](#what-changed)
3. [API Response Changes](#api-response-changes)
4. [The Two Access Flows](#the-two-access-flows)
5. [Screen-by-Screen Implementation](#screen-by-screen-implementation)
6. [API Endpoints Reference](#api-endpoints-reference)
7. [Error Handling](#error-handling)
8. [Complete Code Examples](#complete-code-examples)

---

## Overview

We now support **two access models** for courses:

| Access Model | How Student Gets Access | Use Case |
|--------------|------------------------|----------|
| `enrollment` | Admin enrolls student | Traditional LMS flow |
| `video_code` | Student redeems offline-purchased code | Offline sales model |

**Good news:** Backend handles complexity. Frontend needs minimal changes.

---

## What Changed

### Fields Added to Course Responses

```json
{
  "id": 123,
  "title": "Mathematics 101",
  "access_model": "video_code",    // NEW: "enrollment" or "video_code"
  "is_enrolled": true,             // UPDATED: Now works for both models
  "has_access": true,              // NEW: Alias for is_enrolled
  // ... other existing fields
}
```

### Key Behavior Change

| Field | Old Behavior | New Behavior |
|-------|--------------|--------------|
| `is_enrolled` | True only if enrolled | True if has ANY access (enrollment OR video code) |

**This means:** You can keep using `is_enrolled` - it just works!

---

## API Response Changes

### Course List Response (Explore/Search/MyCourses)

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Mathematics 101",
      "description": "Learn basic math",
      "thumbnail": "https://...",
      "access_model": "enrollment",
      "is_enrolled": true,
      "has_access": true,
      "requires_video_approval": true,
      "status": "published",
      "category": { "id": 1, "name": "Math" },
      "center": { "id": 1, "name": "ABC Center" },
      "instructors": [{ "id": 1, "name": "Dr. Smith" }]
    },
    {
      "id": 456,
      "title": "Physics Basics",
      "description": "Introduction to physics",
      "thumbnail": "https://...",
      "access_model": "video_code",
      "is_enrolled": false,
      "has_access": false,
      "requires_video_approval": true,
      "status": "published",
      "category": { "id": 2, "name": "Science" },
      "center": { "id": 1, "name": "ABC Center" },
      "instructors": [{ "id": 2, "name": "Dr. Johnson" }]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

### Course Details Response

```json
{
  "success": true,
  "data": {
    "id": 456,
    "title": "Physics Basics",
    "description": "Introduction to physics",
    "thumbnail": "https://...",
    "access_model": "video_code",
    "is_enrolled": true,
    "has_access": true,
    "enrollment_status": null,
    "requires_video_approval": true,
    "status": "published",
    "duration_minutes": 120,
    "center": { "id": 1, "name": "ABC Center" },
    "category": { "id": 2, "name": "Science" },
    "instructors": [{ "id": 2, "name": "Dr. Johnson" }],
    "sections": [
      {
        "id": 1,
        "title": "Introduction",
        "order_index": 0,
        "videos": [
          {
            "id": 101,
            "title": "What is Physics?",
            "duration_seconds": 600,
            "thumbnail": "https://...",
            "requires_redemption": true,
            "has_redeemed": true,
            "is_locked": false,
            "access_status": "granted",
            "pending_request_id": null,
            "view_limit": 3,
            "remaining_views": 2,
            "full_plays": 1,
            "watch_duration_seconds": 450
          },
          {
            "id": 102,
            "title": "Newton's Laws",
            "duration_seconds": 900,
            "thumbnail": "https://...",
            "requires_redemption": true,
            "has_redeemed": false,
            "is_locked": true,
            "access_status": "locked",
            "pending_request_id": null,
            "view_limit": null,
            "remaining_views": null,
            "full_plays": 0,
            "watch_duration_seconds": 0
          }
        ],
        "pdfs": []
      }
    ],
    "videos": [],
    "pdfs": []
  }
}
```

### Video Object Fields Explained

| Field | Type | Description |
|-------|------|-------------|
| `requires_redemption` | boolean | Does this video need code/approval to access? |
| `has_redeemed` | boolean | Has student gained access to this video? |
| `is_locked` | boolean | Is video currently locked? (considers view limit too) |
| `access_status` | string | Current access state (see below) |
| `pending_request_id` | int\|null | ID of pending request (enrollment model only) |
| `view_limit` | int\|null | Max views allowed |
| `remaining_views` | int\|null | Views remaining |

### Access Status Values

| access_status | Enrollment Model | Video Code Model |
|---------------|------------------|------------------|
| `null` | No approval required, free access | N/A |
| `"locked"` | No access, can request | No code redeemed yet |
| `"pending"` | Request submitted, waiting | N/A (not used) |
| `"approved"` | Code sent by admin, can redeem | N/A (not used) |
| `"rejected"` | Request was rejected | N/A (not used) |
| `"granted"` | Access granted, can play | Code redeemed, can play |

---

## The Two Access Flows

### Flow 1: Enrollment Model (Existing)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         ENROLLMENT FLOW                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  STEP 1: Browse Course                                                   │
│  ────────────────────                                                    │
│  GET /mobile/courses/explore                                             │
│  Response: { access_model: "enrollment", is_enrolled: false }            │
│  UI: Show "Enroll" button                                                │
│                                                                          │
│  STEP 2: Request Enrollment                                              │
│  ─────────────────────────                                               │
│  POST /mobile/centers/{center}/courses/{course}/enroll-request           │
│  Response: { success: true, message: "Enrollment request submitted" }    │
│  UI: Show "Pending Enrollment" state                                     │
│                                                                          │
│  STEP 3: Admin Approves (happens outside app)                            │
│  ────────────────────────────────────────────                            │
│  Next time student opens course:                                         │
│  Response: { is_enrolled: true }                                         │
│  UI: Show course content                                                 │
│                                                                          │
│  STEP 4: Access Videos (if requires_video_approval: true)                │
│  ────────────────────────────────────────────────                        │
│  Video shows: { access_status: "locked" }                                │
│  UI: Show "Request Access" button                                        │
│                                                                          │
│  STEP 5: Request Video Access                                            │
│  ───────────────────────────                                             │
│  POST /mobile/centers/{c}/courses/{co}/videos/{v}/access-request         │
│  Response: { access_status: "pending", pending_request_id: 123 }         │
│  UI: Show "Pending" state                                                │
│                                                                          │
│  STEP 6: Admin Sends Code                                                │
│  ───────────────────────                                                 │
│  Next time: { access_status: "approved" }                                │
│  UI: Show "Enter Code" button                                            │
│                                                                          │
│  STEP 7: Redeem Code                                                     │
│  ───────────────────                                                     │
│  POST /mobile/video-access-codes/redeem                                  │
│  Body: { "code": "ABC123" }                                              │
│  Response: { access_status: "granted" }                                  │
│  UI: Show Play button                                                    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Flow 2: Video Code Model (New)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         VIDEO CODE FLOW                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  STEP 1: Browse Course                                                   │
│  ────────────────────                                                    │
│  GET /mobile/courses/explore                                             │
│  Response: { access_model: "video_code", is_enrolled: false }            │
│  UI: Show "Enter Code" button (not "Enroll")                             │
│                                                                          │
│  STEP 2: Student Buys Code Offline                                       │
│  ─────────────────────────────────                                       │
│  - Student buys physical card or gets code from center                   │
│  - Code format: ABCD-EFGH-JKLM                                           │
│  - This happens OUTSIDE the app                                          │
│                                                                          │
│  STEP 3: Enter Code (Course Level - Optional)                            │
│  ─────────────────────────────────────────────                           │
│  Show code entry screen when tapping course                              │
│  Or navigate to course and show "Enter Code" on locked videos            │
│                                                                          │
│  STEP 4: Redeem Code                                                     │
│  ───────────────────                                                     │
│  POST /mobile/video-codes/redeem                                         │
│  Body: { "code": "ABCD-EFGH-JKLM" }                                      │
│                                                                          │
│  Success Response:                                                       │
│  {                                                                       │
│    "success": true,                                                      │
│    "message": "Code redeemed successfully",                              │
│    "data": {                                                             │
│      "video_id": 102,                                                    │
│      "video_title": "Newton's Laws",                                     │
│      "course_id": 456,                                                   │
│      "course_title": "Physics Basics",                                   │
│      "center_id": 1,                                                     │
│      "view_limit": 3,                                                    │
│      "batch_code": "VB001"                                               │
│    }                                                                     │
│  }                                                                       │
│  UI: Show success, navigate to video or refresh course                   │
│                                                                          │
│  STEP 5: After Redemption                                                │
│  ────────────────────────                                                │
│  Course: { is_enrolled: true } (now has access!)                         │
│  Video 102: { access_status: "granted", is_locked: false }               │
│  Video 101: { access_status: "locked" } (needs separate code)            │
│  UI: Can play video 102, show "Enter Code" for video 101                 │
│                                                                          │
│  STEP 6: Multiple Codes (Optional)                                       │
│  ─────────────────────────────────                                       │
│  Student can redeem multiple codes for same video                        │
│  View limits STACK: 3 + 3 = 6 total views                                │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Screen-by-Screen Implementation

### 1. Course List Screen (Explore/Search)

```typescript
// Determine what to show based on access_model and is_enrolled
function getCourseCardAction(course: Course): CourseAction {
  if (course.is_enrolled || course.has_access) {
    return {
      label: course.access_model === 'video_code' ? 'Accessed' : 'Enrolled',
      style: 'success',
      action: 'navigate_to_course'
    };
  }

  return {
    label: course.access_model === 'video_code' ? 'Enter Code' : 'Enroll',
    style: 'primary',
    action: course.access_model === 'video_code'
      ? 'show_code_entry'
      : 'navigate_to_course'
  };
}
```

**UI States:**

| access_model | is_enrolled | Badge/Button |
|--------------|-------------|--------------|
| enrollment | false | "Enroll" button |
| enrollment | true | "Enrolled" badge |
| video_code | false | "Enter Code" button |
| video_code | true | "Accessed" badge |

### 2. Course Details Screen

```typescript
function renderCourseHeader(course: Course) {
  // Show appropriate action based on access model
  if (!course.is_enrolled) {
    if (course.access_model === 'video_code') {
      return <Button onPress={showCodeEntryModal}>Enter Code</Button>;
    } else {
      return <Button onPress={requestEnrollment}>Request Enrollment</Button>;
    }
  }

  // Has access - show course content
  return <Badge type="success">
    {course.access_model === 'video_code' ? 'Accessed' : 'Enrolled'}
  </Badge>;
}
```

### 3. Video List (Inside Course)

```typescript
function renderVideoItem(video: Video, course: Course) {
  // Can play?
  if (!video.is_locked && video.access_status === 'granted') {
    return (
      <VideoCard
        video={video}
        rightAction={<PlayButton />}
        subtitle={`${video.remaining_views}/${video.view_limit} views remaining`}
      />
    );
  }

  // Locked - show appropriate action
  return (
    <VideoCard
      video={video}
      rightAction={renderLockedAction(video, course)}
      locked={true}
    />
  );
}

function renderLockedAction(video: Video, course: Course) {
  // Video Code Model - always show "Enter Code"
  if (course.access_model === 'video_code') {
    return <Button onPress={() => showCodeEntry(video)}>Enter Code</Button>;
  }

  // Enrollment Model - show based on access_status
  switch (video.access_status) {
    case 'locked':
      return <Button onPress={() => requestAccess(video)}>Request Access</Button>;

    case 'pending':
      return <Badge type="warning">Pending Approval</Badge>;

    case 'approved':
      return <Button onPress={() => showCodeEntry(video)}>Enter Code</Button>;

    case 'rejected':
      return <Badge type="error">Access Denied</Badge>;

    default:
      return <LockIcon />;
  }
}
```

### 4. Code Entry Modal/Screen

```typescript
interface CodeEntryProps {
  course: Course;
  video?: Video;  // Optional - if entering from video level
  onSuccess: (result: RedemptionResult) => void;
  onError: (error: ApiError) => void;
}

function CodeEntryScreen({ course, video, onSuccess, onError }: CodeEntryProps) {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit() {
    setLoading(true);

    try {
      // Different endpoint based on access model
      const endpoint = course.access_model === 'video_code'
        ? '/mobile/video-codes/redeem'           // New batch code endpoint
        : '/mobile/video-access-codes/redeem';   // Old approval code endpoint

      const response = await api.post(endpoint, { code });
      onSuccess(response.data);

    } catch (error) {
      onError(error);
    } finally {
      setLoading(false);
    }
  }

  return (
    <Modal>
      <Title>Enter Access Code</Title>

      <TextInput
        value={code}
        onChangeText={setCode}
        placeholder="e.g., ABCD-EFGH-JKLM"
        autoCapitalize="characters"
        autoCorrect={false}
      />

      <HelpText>
        {course.access_model === 'video_code'
          ? 'Enter the code from your purchased card'
          : 'Enter the code sent by your instructor'
        }
      </HelpText>

      <Button onPress={handleSubmit} loading={loading}>
        Redeem Code
      </Button>
    </Modal>
  );
}
```

### 5. My Courses Screen

No changes needed! The `/mobile/courses/enrolled` endpoint now returns:
- Courses with active enrollment (enrollment model)
- Courses with any video code redemption (video_code model)

Just use `is_enrolled` as before.

---

## API Endpoints Reference

### Existing Endpoints (No Changes)

#### Browse Courses
```http
GET /api/v1/mobile/courses/explore
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15)
- category_id: int (optional)
- instructor_id: int (optional)
- is_enrolled: boolean (optional) - filter by enrollment status
```

#### Course Details
```http
GET /api/v1/mobile/centers/{center_id}/courses/{course_id}
Authorization: Bearer {token}
```

#### My Courses
```http
GET /api/v1/mobile/courses/enrolled
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15)
- category_id: int (optional)
- instructor_id: int (optional)
```

#### Request Enrollment (Enrollment Model)
```http
POST /api/v1/mobile/centers/{center_id}/courses/{course_id}/enroll-request
Authorization: Bearer {token}

Response (Success):
{
  "success": true,
  "message": "Enrollment request submitted successfully"
}
```

#### Request Video Access (Enrollment Model)
```http
POST /api/v1/mobile/centers/{center_id}/courses/{course_id}/videos/{video_id}/access-request
Authorization: Bearer {token}

Response (Success):
{
  "success": true,
  "message": "Access request submitted",
  "data": {
    "request_id": 123,
    "status": "pending"
  }
}
```

#### Redeem Approval Code (Enrollment Model - Existing)
```http
POST /api/v1/mobile/video-access-codes/redeem
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "code": "ABC123"
}

Response (Success):
{
  "success": true,
  "message": "Code redeemed successfully",
  "data": {
    "video_id": 101,
    "course_id": 123,
    "access_granted": true
  }
}
```

---

### New Endpoints (Video Code Model)

#### Redeem Video Code
```http
POST /api/v1/mobile/video-codes/redeem
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "code": "ABCD-EFGH-JKLM"
}

Response (Success - 200):
{
  "success": true,
  "message": "Code redeemed successfully",
  "data": {
    "redemption_id": 456,
    "video_id": 102,
    "video_title": "Newton's Laws",
    "course_id": 456,
    "course_title": "Physics Basics",
    "center_id": 1,
    "view_limit": 3,
    "batch_code": "VB001",
    "sequence_number": 42,
    "redeemed_at": "2026-03-14T15:30:00Z"
  }
}
```

#### Validate Code (Without Redeeming)
```http
POST /api/v1/mobile/video-codes/validate
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "code": "ABCD-EFGH-JKLM"
}

Response (Valid - 200):
{
  "success": true,
  "message": "Code is valid",
  "data": {
    "valid": true,
    "video_id": 102,
    "video_title": "Newton's Laws",
    "course_id": 456,
    "course_title": "Physics Basics",
    "center_id": 1,
    "view_limit": 3,
    "can_redeem": true
  }
}

Response (Invalid - 200):
{
  "success": true,
  "data": {
    "valid": false,
    "reason": "Code has already been redeemed"
  }
}
```

#### My Redemptions
```http
GET /api/v1/mobile/video-codes/my-redemptions
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15)
- course_id: int (optional) - filter by course

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 456,
      "code": "ABCD-EFGH-JKLM",
      "video_id": 102,
      "video_title": "Newton's Laws",
      "course_id": 456,
      "course_title": "Physics Basics",
      "view_limit": 3,
      "redeemed_at": "2026-03-14T15:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
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

### Video Code Errors

| HTTP Status | Error Code | Message | User Action |
|-------------|------------|---------|-------------|
| 400 | `VALIDATION_ERROR` | "The code field is required" | Show validation error |
| 404 | `VIDEO_CODE_INVALID` | "Invalid code or code not found" | Show "Invalid code" message |
| 409 | `VIDEO_CODE_ALREADY_REDEEMED` | "This code has already been redeemed" | Show "Already used" message |
| 409 | `VIDEO_CODE_ALREADY_REDEEMED_BY_YOU` | "You have already redeemed this code" | Navigate to video |
| 403 | `VIDEO_CODE_BATCH_CLOSED` | "This code batch is no longer active" | Show "Code expired" message |
| 403 | `VIDEO_CODE_BATCH_LIMIT_REACHED` | "Code redemption limit reached" | Show "Contact center" message |
| 401 | `UNAUTHENTICATED` | "Please login to redeem code" | Navigate to login |

### Error Handling Example

```typescript
async function redeemCode(code: string) {
  try {
    const response = await api.post('/mobile/video-codes/redeem', { code });
    return { success: true, data: response.data };

  } catch (error) {
    const errorCode = error.response?.data?.error?.code;
    const message = error.response?.data?.error?.message;

    switch (errorCode) {
      case 'VIDEO_CODE_INVALID':
        showToast('Invalid code. Please check and try again.');
        break;

      case 'VIDEO_CODE_ALREADY_REDEEMED':
        showToast('This code has already been used by someone else.');
        break;

      case 'VIDEO_CODE_ALREADY_REDEEMED_BY_YOU':
        showToast('You already have access to this video!');
        navigateToVideo(error.response?.data?.data?.video_id);
        break;

      case 'VIDEO_CODE_BATCH_LIMIT_REACHED':
        showAlert({
          title: 'Code Limit Reached',
          message: 'Please contact your center for assistance.',
          buttons: [{ text: 'OK' }]
        });
        break;

      default:
        showToast(message || 'Something went wrong. Please try again.');
    }

    return { success: false, error: errorCode };
  }
}
```

---

## Complete Code Examples

### TypeScript Interfaces

```typescript
// Course types
interface Course {
  id: number;
  title: string;
  description: string;
  thumbnail: string | null;
  access_model: 'enrollment' | 'video_code';
  is_enrolled: boolean;
  has_access: boolean;
  requires_video_approval: boolean;
  enrollment_status: string | null;
  status: string;
  duration_minutes: number | null;
  center: Center;
  category: Category | null;
  instructors: Instructor[];
  sections?: Section[];
  videos?: Video[];
  pdfs?: Pdf[];
}

// Video types
interface Video {
  id: number;
  title: string;
  duration_seconds: number;
  thumbnail: string | null;
  requires_redemption: boolean;
  has_redeemed: boolean;
  is_locked: boolean;
  access_status: 'locked' | 'pending' | 'approved' | 'rejected' | 'granted' | null;
  pending_request_id: number | null;
  view_limit: number | null;
  remaining_views: number | null;
  full_plays: number;
  watch_duration_seconds: number;
}

// Redemption result
interface RedemptionResult {
  redemption_id: number;
  video_id: number;
  video_title: string;
  course_id: number;
  course_title: string;
  center_id: number;
  view_limit: number;
  batch_code: string;
  sequence_number: number;
  redeemed_at: string;
}

// API Error
interface ApiError {
  code: string;
  message: string;
}
```

### React Native Example - Course Card

```tsx
import React from 'react';
import { View, Text, TouchableOpacity, Image, StyleSheet } from 'react-native';

interface CourseCardProps {
  course: Course;
  onPress: () => void;
  onEnterCode: () => void;
  onEnroll: () => void;
}

export function CourseCard({ course, onPress, onEnterCode, onEnroll }: CourseCardProps) {
  const renderAccessBadge = () => {
    if (course.is_enrolled) {
      return (
        <View style={[styles.badge, styles.badgeSuccess]}>
          <Text style={styles.badgeText}>
            {course.access_model === 'video_code' ? 'Accessed' : 'Enrolled'}
          </Text>
        </View>
      );
    }
    return null;
  };

  const renderActionButton = () => {
    if (course.is_enrolled) {
      return (
        <TouchableOpacity style={styles.buttonSecondary} onPress={onPress}>
          <Text style={styles.buttonSecondaryText}>View Course</Text>
        </TouchableOpacity>
      );
    }

    if (course.access_model === 'video_code') {
      return (
        <TouchableOpacity style={styles.buttonPrimary} onPress={onEnterCode}>
          <Text style={styles.buttonPrimaryText}>Enter Code</Text>
        </TouchableOpacity>
      );
    }

    return (
      <TouchableOpacity style={styles.buttonPrimary} onPress={onEnroll}>
        <Text style={styles.buttonPrimaryText}>Enroll Now</Text>
      </TouchableOpacity>
    );
  };

  return (
    <TouchableOpacity style={styles.card} onPress={onPress}>
      <Image source={{ uri: course.thumbnail }} style={styles.thumbnail} />

      <View style={styles.content}>
        <View style={styles.header}>
          <Text style={styles.title} numberOfLines={2}>{course.title}</Text>
          {renderAccessBadge()}
        </View>

        <Text style={styles.instructor}>
          {course.instructors[0]?.name || 'Unknown Instructor'}
        </Text>

        {/* Show access model indicator for video_code courses */}
        {course.access_model === 'video_code' && !course.is_enrolled && (
          <View style={styles.codeIndicator}>
            <Text style={styles.codeIndicatorText}>Code Required</Text>
          </View>
        )}

        {renderActionButton()}
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  thumbnail: {
    width: '100%',
    height: 160,
    borderTopLeftRadius: 12,
    borderTopRightRadius: 12,
  },
  content: {
    padding: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  title: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
    color: '#1a1a1a',
    marginRight: 8,
  },
  instructor: {
    fontSize: 14,
    color: '#666',
    marginTop: 4,
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  badgeSuccess: {
    backgroundColor: '#e6f4ea',
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#1e7e34',
  },
  codeIndicator: {
    marginTop: 8,
    paddingHorizontal: 8,
    paddingVertical: 4,
    backgroundColor: '#fff3cd',
    borderRadius: 4,
    alignSelf: 'flex-start',
  },
  codeIndicatorText: {
    fontSize: 12,
    color: '#856404',
  },
  buttonPrimary: {
    marginTop: 12,
    backgroundColor: '#007bff',
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonPrimaryText: {
    color: '#fff',
    fontWeight: '600',
  },
  buttonSecondary: {
    marginTop: 12,
    backgroundColor: '#f0f0f0',
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonSecondaryText: {
    color: '#333',
    fontWeight: '600',
  },
});
```

### React Native Example - Video Item

```tsx
import React from 'react';
import { View, Text, TouchableOpacity, Image, StyleSheet } from 'react-native';
import { PlayIcon, LockIcon, ClockIcon } from './Icons';

interface VideoItemProps {
  video: Video;
  course: Course;
  onPlay: () => void;
  onEnterCode: () => void;
  onRequestAccess: () => void;
}

export function VideoItem({ video, course, onPlay, onEnterCode, onRequestAccess }: VideoItemProps) {

  const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const renderRightAction = () => {
    // Can play - show play button
    if (!video.is_locked && video.has_redeemed) {
      return (
        <TouchableOpacity style={styles.playButton} onPress={onPlay}>
          <PlayIcon size={24} color="#fff" />
        </TouchableOpacity>
      );
    }

    // Video Code Model - always show "Enter Code" for locked videos
    if (course.access_model === 'video_code') {
      return (
        <TouchableOpacity style={styles.codeButton} onPress={onEnterCode}>
          <Text style={styles.codeButtonText}>Enter Code</Text>
        </TouchableOpacity>
      );
    }

    // Enrollment Model - show based on access_status
    switch (video.access_status) {
      case 'locked':
        return (
          <TouchableOpacity style={styles.requestButton} onPress={onRequestAccess}>
            <Text style={styles.requestButtonText}>Request</Text>
          </TouchableOpacity>
        );

      case 'pending':
        return (
          <View style={styles.pendingBadge}>
            <ClockIcon size={14} color="#856404" />
            <Text style={styles.pendingText}>Pending</Text>
          </View>
        );

      case 'approved':
        return (
          <TouchableOpacity style={styles.codeButton} onPress={onEnterCode}>
            <Text style={styles.codeButtonText}>Enter Code</Text>
          </TouchableOpacity>
        );

      case 'rejected':
        return (
          <View style={styles.rejectedBadge}>
            <Text style={styles.rejectedText}>Denied</Text>
          </View>
        );

      default:
        return <LockIcon size={20} color="#999" />;
    }
  };

  const renderViewsInfo = () => {
    if (video.view_limit && video.remaining_views !== null) {
      const isLow = video.remaining_views <= 1;
      return (
        <Text style={[styles.viewsText, isLow && styles.viewsTextWarning]}>
          {video.remaining_views}/{video.view_limit} views left
        </Text>
      );
    }
    return null;
  };

  return (
    <View style={[styles.container, video.is_locked && styles.containerLocked]}>
      <View style={styles.thumbnailContainer}>
        <Image source={{ uri: video.thumbnail }} style={styles.thumbnail} />
        {video.is_locked && (
          <View style={styles.lockOverlay}>
            <LockIcon size={24} color="#fff" />
          </View>
        )}
        <View style={styles.durationBadge}>
          <Text style={styles.durationText}>{formatDuration(video.duration_seconds)}</Text>
        </View>
      </View>

      <View style={styles.info}>
        <Text style={styles.title} numberOfLines={2}>{video.title}</Text>
        {renderViewsInfo()}
      </View>

      <View style={styles.action}>
        {renderRightAction()}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    padding: 12,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },
  containerLocked: {
    opacity: 0.8,
  },
  thumbnailContainer: {
    position: 'relative',
    width: 120,
    height: 68,
    borderRadius: 8,
    overflow: 'hidden',
  },
  thumbnail: {
    width: '100%',
    height: '100%',
  },
  lockOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  durationBadge: {
    position: 'absolute',
    bottom: 4,
    right: 4,
    backgroundColor: 'rgba(0,0,0,0.7)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  durationText: {
    color: '#fff',
    fontSize: 11,
    fontWeight: '500',
  },
  info: {
    flex: 1,
    marginLeft: 12,
    justifyContent: 'center',
  },
  title: {
    fontSize: 14,
    fontWeight: '500',
    color: '#1a1a1a',
  },
  viewsText: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  viewsTextWarning: {
    color: '#dc3545',
  },
  action: {
    justifyContent: 'center',
    marginLeft: 12,
  },
  playButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#007bff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  codeButton: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: '#28a745',
    borderRadius: 6,
  },
  codeButtonText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  requestButton: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: '#007bff',
    borderRadius: 6,
  },
  requestButtonText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  pendingBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 6,
    backgroundColor: '#fff3cd',
    borderRadius: 6,
  },
  pendingText: {
    color: '#856404',
    fontSize: 12,
    fontWeight: '500',
    marginLeft: 4,
  },
  rejectedBadge: {
    paddingHorizontal: 8,
    paddingVertical: 6,
    backgroundColor: '#f8d7da',
    borderRadius: 6,
  },
  rejectedText: {
    color: '#721c24',
    fontSize: 12,
    fontWeight: '500',
  },
});
```

### API Service Example

```typescript
// api/videoCodeService.ts

import { api } from './client';

interface RedeemCodeRequest {
  code: string;
}

interface RedeemCodeResponse {
  success: boolean;
  message: string;
  data: {
    redemption_id: number;
    video_id: number;
    video_title: string;
    course_id: number;
    course_title: string;
    center_id: number;
    view_limit: number;
    batch_code: string;
    sequence_number: number;
    redeemed_at: string;
  };
}

interface ValidateCodeResponse {
  success: boolean;
  data: {
    valid: boolean;
    video_id?: number;
    video_title?: string;
    course_id?: number;
    course_title?: string;
    view_limit?: number;
    can_redeem?: boolean;
    reason?: string;
  };
}

export const videoCodeService = {
  /**
   * Redeem a video access code (for video_code access model)
   */
  async redeemCode(code: string): Promise<RedeemCodeResponse> {
    const response = await api.post('/mobile/video-codes/redeem', { code });
    return response.data;
  },

  /**
   * Validate a code without redeeming it
   */
  async validateCode(code: string): Promise<ValidateCodeResponse> {
    const response = await api.post('/mobile/video-codes/validate', { code });
    return response.data;
  },

  /**
   * Get list of codes redeemed by current user
   */
  async getMyRedemptions(params?: { page?: number; course_id?: number }) {
    const response = await api.get('/mobile/video-codes/my-redemptions', { params });
    return response.data;
  },

  /**
   * Redeem approval code (for enrollment access model)
   * This is the existing endpoint for admin-sent codes
   */
  async redeemApprovalCode(code: string) {
    const response = await api.post('/mobile/video-access-codes/redeem', { code });
    return response.data;
  },
};
```

---

## Summary: What Frontend Needs to Do

### Minimal Changes (Must Do)

1. **Add code entry screen/modal** for redeeming video codes
2. **Call new endpoint** `POST /mobile/video-codes/redeem` for video_code courses
3. **Handle new error codes** for video code redemption

### Recommended Changes (Better UX)

1. **Use `access_model`** to show appropriate labels:
   - "Enter Code" vs "Enroll"
   - "Accessed" vs "Enrolled"

2. **Differentiate video states** based on access model:
   - Video code: Only "locked" or "granted"
   - Enrollment: Full flow (locked → pending → approved → granted)

### No Changes Needed

- Course list logic (use `is_enrolled` as before)
- My Courses screen (backend handles both models)
- Playback logic (same as before)
- View limit display (same as before)

---

## Questions?

Contact backend team for:
- API clarifications
- Error code additions
- New feature requests

Document version: 1.0
Last updated: 2026-03-14
