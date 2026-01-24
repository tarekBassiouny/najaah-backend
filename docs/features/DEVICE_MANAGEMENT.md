# Device Management

> Device registration, validation, and change request workflow.

## Overview

The device management system ensures:
- One active device per student
- Device binding during authentication
- Controlled device change workflow
- Playback session device tracking

---

## Database Schema

### Table: `user_devices`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK | Device owner |
| `device_id` | varchar | UUID from mobile app |
| `model` | varchar | Device model name |
| `os_version` | varchar | Operating system version |
| `status` | tinyint | 0=active, 1=revoked, 2=pending |
| `approved_at` | timestamp | Approval timestamp |
| `last_used_at` | timestamp | Last activity |

**Unique constraint:** `(user_id, device_id)`

### Status Values

```php
// App\Models\UserDevice
const STATUS_ACTIVE = 0;   // Device can be used for auth/playback
const STATUS_REVOKED = 1;  // Device deactivated (e.g., after change)
const STATUS_PENDING = 2;  // Awaiting approval (future use)
```

### Table: `device_change_requests`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK | Requesting student |
| `center_id` | FK | Student's center |
| `current_device_id` | varchar | Current device UUID |
| `new_device_id` | varchar | Requested device UUID |
| `new_model` | varchar | New device model |
| `new_os_version` | varchar | New device OS |
| `status` | varchar | PENDING/APPROVED/REJECTED |
| `reason` | text | Student's reason |
| `decision_reason` | text | Admin's reason |
| `decided_by` | FK | Admin who decided |
| `decided_at` | timestamp | Decision timestamp |

---

## Device Lifecycle

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Device Registration Flow                          │
└─────────────────────────────────────────────────────────────────────┘

    FIRST LOGIN (no device exists)
    ┌────────────────────────────────────────────────┐
    │ 1. Student logs in with device_id              │
    │ 2. DeviceService.register() creates device     │
    │ 3. Device set to STATUS_ACTIVE                 │
    │ 4. Other devices revoked (if any)              │
    │ 5. JWT token bound to device_id                │
    └────────────────────────────────────────────────┘

    SUBSEQUENT LOGIN (same device)
    ┌────────────────────────────────────────────────┐
    │ 1. Student logs in with same device_id         │
    │ 2. DeviceService validates device is active    │
    │ 3. last_used_at updated                        │
    │ 4. Login succeeds                              │
    └────────────────────────────────────────────────┘

    LOGIN WITH DIFFERENT DEVICE
    ┌────────────────────────────────────────────────┐
    │ 1. Student logs in with new device_id          │
    │ 2. DeviceService detects active device exists  │
    │ 3. DEVICE_MISMATCH error returned              │
    │ 4. Student must request device change          │
    └────────────────────────────────────────────────┘
```

---

## Service Layer

### DeviceService

**Location:** `app/Services/Devices/DeviceService.php`

#### `register(User, string $uuid, array $meta): UserDevice`

Registers or updates a device during login.

**Parameters:**
- `$uuid` - Device UUID from mobile app
- `$meta` - Device metadata: `device_type`, `device_name`, `device_os`

**Flow:**
1. Check for existing active device
2. If active device exists with different UUID → `DEVICE_MISMATCH`
3. Find or create device record
4. Set to `STATUS_ACTIVE`
5. Revoke all other devices for user
6. Return device

**Code Reference:** Lines 19-64

#### `assertActiveDevice(User, string $uuid): UserDevice`

Validates device during authenticated requests.

**Flow:**
1. Find active device for user
2. If no active device or UUID mismatch → `DEVICE_MISMATCH`
3. Return active device

**Code Reference:** Lines 67-81

---

### DeviceChangeService

**Location:** `app/Services/Devices/DeviceChangeService.php`

#### `create(User $student, string $newDeviceId, string $model, string $osVersion, ?string $reason): DeviceChangeRequest`

Creates a device change request.

**Validations:**
- User must be a student
- User must have an active device
- No pending request exists

**Code Reference:** Lines 21-63

#### `approve(User $admin, DeviceChangeRequest $request): DeviceChangeRequest`

Approves a device change request.

**Flow:**
1. Validate admin scope (same center)
2. Validate request is PENDING
3. In transaction:
   - Revoke current device
   - Create/update new device as ACTIVE
   - Revoke all other devices
   - Update request status
4. Create audit log

**Code Reference:** Lines 65-116

#### `reject(User $admin, DeviceChangeRequest $request, ?string $reason): DeviceChangeRequest`

Rejects a device change request.

**Code Reference:** Lines 118-140

---

## JWT Middleware Validation

**Location:** `app/Http/Middleware/JwtMobileMiddleware.php`

The middleware validates device on every authenticated request:

```php
// Lines 91-98
if ($record->device_id !== null) {
    $device = UserDevice::find($record->device_id);

    if ($device === null || $device->status !== UserDevice::STATUS_ACTIVE) {
        return $this->deny('DEVICE_MISMATCH', 'Device is not authorized for this user.');
    }
}
```

---

## API Endpoints

### POST `/api/v1/settings/device-change`

Creates a device change request.

**Request Body:**
```json
{
    "reason": "Lost my phone and need to register a new device."
}
```

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 10,
        "current_device_id": "old-uuid",
        "new_device_id": "new-uuid",
        "status": "PENDING",
        "reason": "Lost my phone..."
    }
}
```

**Note:** `new_device_id`, `new_model`, `new_os_version` are captured from the current request headers/metadata.

### Admin Endpoints

| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/api/v1/admin/device-change-requests` | List requests |
| POST | `/api/v1/admin/device-change-requests/{id}/approve` | Approve |
| POST | `/api/v1/admin/device-change-requests/{id}/reject` | Reject |

---

## Device Change Workflow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Device Change Request Flow                        │
└─────────────────────────────────────────────────────────────────────┘

Student Side:
┌─────────────────────────────────────────────────────────────────────┐
│ 1. Student tries to login with new device                           │
│ 2. Gets DEVICE_MISMATCH error                                       │
│ 3. Student submits device change request with reason                │
│ 4. Request created with status = PENDING                            │
│ 5. Student waits for admin approval                                 │
└─────────────────────────────────────────────────────────────────────┘

Admin Side:
┌─────────────────────────────────────────────────────────────────────┐
│ 1. Admin sees pending request in dashboard                          │
│ 2. Reviews student info and reason                                  │
│ 3. Approves or rejects with decision reason                         │
│                                                                      │
│ On APPROVE:                                                          │
│   - Current device → STATUS_REVOKED                                  │
│   - New device → STATUS_ACTIVE                                       │
│   - Student can now login with new device                           │
│                                                                      │
│ On REJECT:                                                          │
│   - Request marked REJECTED                                          │
│   - Student must try again or contact support                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Device Policy

### Current Implementation

| Rule | Status |
|------|--------|
| One active device per student | ✅ Implemented |
| Device registered on first login | ✅ Implemented |
| Different device blocked | ✅ Implemented |
| Change requires admin approval | ✅ Implemented |
| JWT tokens bound to device | ✅ Implemented |
| Playback sessions track device | ✅ Implemented |

### Known Gaps

| Gap | Description |
|-----|-------------|
| **Device limit setting** | `device_limit` exists in center settings but not enforced |
| **Reinstall detection** | If app is reinstalled, new device_id is generated; no fingerprint matching |
| **Device fingerprinting** | No hardware fingerprint to detect same physical device |

---

## Device Data Captured

The mobile app sends device metadata:

| Field | Source | Example |
|-------|--------|---------|
| `device_id` | App-generated UUID | `"550e8400-e29b-41d4-a716-446655440000"` |
| `device_type` / `device_name` | OS API | `"iPhone 14 Pro"` |
| `device_os` | OS API | `"iOS 17.2"` |

**Not captured:**
- Screen size
- Hardware fingerprint
- IP address (in device record)

---

## Error Codes

| Code | HTTP | Cause |
|------|------|-------|
| `DEVICE_MISMATCH` | 403 | Attempting to use non-active device |
| `NO_ACTIVE_DEVICE` | 422 | User has no registered device |
| `PENDING_REQUEST_EXISTS` | 422 | Already has pending device change |
| `INVALID_STATE` | 409 | Trying to approve/reject non-pending request |

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Services/Devices/DeviceService.php` | Device registration |
| `app/Services/Devices/DeviceChangeService.php` | Change requests |
| `app/Http/Middleware/JwtMobileMiddleware.php` | Auth device validation |
| `app/Http/Controllers/Mobile/DeviceChangeRequestController.php` | Mobile endpoint |
| `app/Http/Controllers/Admin/DeviceChangeRequestController.php` | Admin endpoints |
| `app/Models/UserDevice.php` | Device model |
| `app/Models/DeviceChangeRequest.php` | Request model |

---

## Testing

```bash
# Run device-related tests
./vendor/bin/sail test --filter="Device"

# Test files
tests/Feature/Admin/DeviceChangeRequestTest.php
tests/Feature/Mobile/DeviceChangeRequestTest.php
```

---

## Reinstall Detection

### How It Works

When a user reinstalls the app, they get a new `device_id`. The system detects reinstalls by matching device fingerprint (model name):

```php
// DeviceService.php
public function handleReinstall(User $user, string $newDeviceId, string $model, string $osVersion): ?UserDevice
{
    // Find existing device with same model for this user
    $existing = $this->findByFingerprint($user, $model, $osVersion);

    if ($existing && $existing->device_id !== $newDeviceId) {
        // Update device_id to new one, keeping the device active
        $existing->update(['device_id' => $newDeviceId, ...]);

        // Create audit log
        AuditLog::create([...]);

        return $existing;
    }

    return null;
}
```

### Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Reinstall Detection Flow                          │
└─────────────────────────────────────────────────────────────────────┘

1. User reinstalls app → gets new device_id
2. User logs in with new device_id but same device model
3. DeviceService.register() calls handleReinstall()
4. If active device found with same model:
   - Update device_id to new value
   - Create audit log entry
   - User continues without device change request
5. If no match found:
   - Normal device mismatch error
   - User must submit device change request
```

### Audit Log

Reinstall detections are logged for security:

```json
{
    "action": "device_uuid_updated",
    "entity_type": "App\\Models\\UserDevice",
    "entity_id": 123,
    "metadata": {
        "old_device_id": "old-uuid-here",
        "new_device_id": "new-uuid-here",
        "reason": "reinstall_detected"
    }
}
```

---

## Future Considerations

### Multi-Device Support

If needed in future:
1. Update `device_limit` enforcement in `DeviceService`
2. Modify `JwtMobileMiddleware` to allow multiple active devices
3. Consider playback concurrency rules across devices
