# Settings System

> Hierarchical settings resolution with JSON-based overrides.

## Overview

The settings system provides a flexible way to configure behavior at multiple levels:
- System-wide defaults
- Per-center customization
- Per-course overrides
- Per-video fine-tuning
- Per-student exceptions

---

## Architecture

### Resolution Hierarchy

Settings are resolved in order, with later levels overriding earlier ones:

```
┌─────────────────────────────────────────────────────────────────────┐
│                     Settings Resolution Chain                        │
└─────────────────────────────────────────────────────────────────────┘

Level 1: Center Defaults (table columns)
    │
    │   centers.default_view_limit
    │   centers.allow_extra_view_requests
    │   centers.pdf_download_permission
    │   centers.device_limit
    │
    ▼
Level 2: Center Settings (JSON)
    │
    │   center_settings.settings = { "view_limit": 5, ... }
    │
    ▼
Level 3: Course Settings (JSON)
    │
    │   course_settings.settings = { "view_limit": 3, ... }
    │
    ▼
Level 4: Video Settings (JSON)
    │
    │   video_settings.settings = { "view_limit": 2, ... }
    │
    ▼
Level 5: Student Settings (JSON)
    │
    │   student_settings.settings = { "view_limit": 10, ... }
    │
    ▼
Final Resolved Settings
```

---

## Database Schema

### System Settings

Key-value store for global settings.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `key` | varchar | Unique setting key |
| `value` | json | Setting value |
| `is_public` | bool | Exposed to frontend |

**Model:** `App\Models\SystemSetting`

### Center Settings

Per-center JSON blob.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK | Unique per center |
| `settings` | json | Settings object |

**Model:** `App\Models\CenterSetting`

**Note:** Centers also have direct columns:
- `default_view_limit` (int)
- `allow_extra_view_requests` (bool)
- `pdf_download_permission` (bool)
- `device_limit` (int)

### Course Settings

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `course_id` | FK | One per course |
| `settings` | json | Settings object |

**Model:** `App\Models\CourseSetting`

### Video Settings

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `video_id` | FK | One per video |
| `settings` | json | Settings object |

**Model:** `App\Models\VideoSetting`

### Student Settings

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | FK | One per student |
| `settings` | json | Settings object |

**Model:** `App\Models\StudentSetting`

**Special keys:**
- `extra_views` - Object mapping `video_id` to extra view count

---

## Allowed Setting Keys

Only these keys are recognized by the resolver:

```php
// SettingsResolverService.php
private array $allowedKeys = [
    'view_limit',                 // Max video views
    'allow_extra_view_requests',  // Can request extra views
    'pdf_download_permission',    // Can download PDFs
    'device_limit',               // Max devices (not enforced)
    'branding',                   // Logo, colors (array)
];
```

---

## Service Layer

### SettingsResolverService

**Location:** `app/Services/Settings/SettingsResolverService.php`

#### `resolve(?User, ?Video, ?Course, ?Center): array`

Resolves settings by applying the hierarchy.

**Parameters:**
- `$student` - User to resolve for (optional)
- `$video` - Video context (optional)
- `$course` - Course context (optional)
- `$center` - Center context (optional)

**Returns:** Merged settings array

```php
public function resolve(?User $student, ?Video $video = null, ?Course $course = null, ?Center $center = null): array
{
    $course = $course ?? $this->resolveCourseFromVideo($video);
    $center = $center ?? $this->resolveCenter($course, $video);

    $resolved = [];

    if ($center !== null) {
        $resolved = $this->apply($resolved, $this->centerDefaults($center));
        $resolved = $this->apply($resolved, $this->filterCenterSettings($center->setting));
    }

    if ($course !== null) {
        $resolved = $this->apply($resolved, $this->filterSettings($course->setting));
    }

    if ($video !== null) {
        $resolved = $this->apply($resolved, $this->filterSettings($video->setting));
    }

    if ($student !== null) {
        $resolved = $this->apply($resolved, $this->filterSettings($student->studentSetting));
    }

    return $resolved;
}
```

---

## Resolution Examples

### Example 1: View Limit

```
Center columns:       default_view_limit = 10
CenterSetting:        { "view_limit": 5 }
CourseSetting:        (none)
VideoSetting:         { "view_limit": 3 }
StudentSetting:       (none)

Resolved:             view_limit = 3  (video level wins)
```

### Example 2: PDF Permission

```
Center columns:       pdf_download_permission = true
CenterSetting:        (none)
CourseSetting:        { "pdf_download_permission": false }
VideoSetting:         (none)
StudentSetting:       (none)

Resolved:             pdf_download_permission = false  (course level wins)
```

### Example 3: Branding

```
Center columns:       logo_url = "logo.png", primary_color = "#FF0000"
CenterSetting:        { "branding": { "primary_color": "#00FF00" } }

Resolved:             branding = { "logo_url": "logo.png", "primary_color": "#00FF00" }
```

---

## Usage in Services

### ViewLimitService

```php
// Resolves view_limit setting
$settings = $this->settingsResolver->resolve($user, $video, $course, $course->center);
$limit = $settings['view_limit'] ?? null;
```

### Example: Check PDF Permission

```php
$settings = $this->settingsResolver->resolve($student, null, $course, $center);
$canDownload = $settings['pdf_download_permission'] ?? false;

if (!$canDownload) {
    throw new DomainException('PDF download not allowed', 'PDF_DOWNLOAD_DENIED', 403);
}
```

---

## Adding New Settings

### Step 1: Add to Allowed Keys

```php
// SettingsResolverService.php
private array $allowedKeys = [
    'view_limit',
    'allow_extra_view_requests',
    'pdf_download_permission',
    'device_limit',
    'branding',
    'new_setting_key',  // Add here
];
```

### Step 2: Add Center Default (Optional)

If needed as a column on centers table:

```php
// Migration
$table->boolean('new_setting_key')->default(false);

// Add to centerDefaults() in SettingsResolverService
private function centerDefaults(Center $center): array
{
    $defaults = [];
    // ...existing code...
    $defaults['new_setting_key'] = $center->new_setting_key;
    return $defaults;
}
```

### Step 3: Use in Service

```php
$settings = $this->settingsResolver->resolve($user, $video, $course, $center);
$value = $settings['new_setting_key'] ?? 'default_value';
```

---

## Admin Settings Management

### CenterSettingsService

**Location:** `app/Services/Settings/CenterSettingsService.php`

Manages center settings updates.

### AdminSettingsPreviewService

**Location:** `app/Services/Settings/AdminSettingsPreviewService.php`

Previews resolved settings for admin UI.

---

## API Endpoints

### Center Settings

| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/api/v1/admin/centers/{center}/settings` | Get settings |
| PUT | `/api/v1/admin/centers/{center}/settings` | Update settings |

### Settings Preview

| Method | Endpoint | Action |
|--------|----------|--------|
| POST | `/api/v1/admin/settings/preview` | Preview resolution |

---

## Special Handling

### Center Settings vs Column Defaults

Center has both JSON settings and direct columns. Resolution order:

1. Direct columns (`default_view_limit`, etc.)
2. JSON settings (can override columns)

```php
// centerDefaults() uses column values
$defaults['view_limit'] = $center->default_view_limit;

// filterCenterSettings() maps JSON key
if (isset($settings['default_view_limit'])) {
    $settings['view_limit'] = $settings['default_view_limit'];
}
```

### Branding Settings

Branding is an object, not a scalar:

```json
{
    "branding": {
        "logo_url": "https://...",
        "primary_color": "#FF0000"
    }
}
```

Validation ensures branding is always an array:

```php
if ($key === 'branding' && !is_array($value)) {
    continue;
}
```

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Services/Settings/SettingsResolverService.php` | Resolution logic |
| `app/Services/Settings/CenterSettingsService.php` | Center settings CRUD |
| `app/Services/Settings/AdminSettingsPreviewService.php` | Preview for admin |
| `app/Models/SystemSetting.php` | System settings model |
| `app/Models/CenterSetting.php` | Center settings model |
| `app/Models/CourseSetting.php` | Course settings model |
| `app/Models/VideoSetting.php` | Video settings model |
| `app/Models/StudentSetting.php` | Student settings model |

---

## Testing

```bash
# Run settings tests
./vendor/bin/sail test --filter="Settings"

# Test files
tests/Feature/Admin/SettingsTest.php
tests/Unit/Settings/SettingsResolverServiceTest.php
```
