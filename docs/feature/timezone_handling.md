# Timezone Handling Feature

## Overview

Enable timezone configuration at both system and center levels, with standardized ISO 8601 date formatting across all API responses. Currently operating in Egypt (Africa/Cairo), with planned expansion to KSA (Asia/Riyadh).

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Timezone Scope | System + Center | System default, each center can override |
| Mobile Dates | Center's timezone | Students see dates in their center's timezone |
| Date Format | ISO 8601 with timezone | Consistent: `2026-03-08T14:30:00+02:00` |
| Storage | UTC always | Best practice, convert at boundaries |

---

## Current State (Before Implementation)

| Aspect | Current Implementation |
|--------|----------------------|
| **App Timezone** | UTC (config/app.php) |
| **Database Storage** | All dates in UTC ✓ |
| **System Setting** | `timezone` exists in `system_settings`, scope='system', default='UTC' |
| **Center Timezone** | NOT supported (system-scope only) |
| **Analytics API** | Already accepts `timezone` param, converts properly ✓ |
| **Other APIs** | Return raw Carbon objects (no timezone context) |
| **Mobile APIs** | No timezone parameter support |

---

## Database Schema

### Table: `centers` (Add Column)

| Column | Type | Description |
|--------|------|-------------|
| timezone | VARCHAR(64) | Center timezone (e.g., "Africa/Cairo"), defaults to "UTC" |

```sql
ALTER TABLE centers ADD COLUMN timezone VARCHAR(64) DEFAULT 'UTC' AFTER slug;
```

### Supported Timezones

| Region | Timezone ID | UTC Offset | DST |
|--------|-------------|------------|-----|
| Egypt | `Africa/Cairo` | UTC+2 | No (currently) |
| Saudi Arabia | `Asia/Riyadh` | UTC+3 | No |
| UAE | `Asia/Dubai` | UTC+4 | No |
| Kuwait | `Asia/Kuwait` | UTC+3 | No |
| Jordan | `Asia/Amman` | UTC+2/+3 | Yes |

---

## API Endpoints

### Admin API - Center Timezone Management

#### Get Center (includes timezone)

```http
GET /api/v1/admin/centers/{center}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Cairo Learning Center",
    "slug": "cairo-center",
    "timezone": "Africa/Cairo",
    "effective_timezone": "Africa/Cairo",
    ...
  }
}
```

#### Update Center Timezone

```http
PUT /api/v1/admin/centers/{center}
Content-Type: application/json

{
  "timezone": "Africa/Cairo"
}
```

**Validation:**
- Must be a valid PHP timezone identifier
- Examples: `Africa/Cairo`, `Asia/Riyadh`, `UTC`

#### Update via Center Settings

```http
PATCH /api/v1/admin/centers/{center}/settings
Content-Type: application/json

{
  "timezone": "Africa/Cairo"
}
```

### System API - Default Timezone

```http
GET /api/v1/admin/settings?key=timezone
```

**Response:**
```json
{
  "success": true,
  "data": {
    "key": "timezone",
    "value": {
      "timezone": "UTC"
    }
  }
}
```

---

## Date Format Specification

### ISO 8601 with Timezone Offset

All datetime fields in API responses follow this format:

```
2026-03-08T14:30:00+02:00
```

| Component | Example | Description |
|-----------|---------|-------------|
| Date | `2026-03-08` | YYYY-MM-DD |
| Separator | `T` | Date/time separator |
| Time | `14:30:00` | HH:mm:ss (24-hour) |
| Offset | `+02:00` | Timezone offset from UTC |

### Examples by Timezone

| Timezone | UTC Time | Local Display |
|----------|----------|---------------|
| UTC | `2026-03-08T12:00:00Z` | `2026-03-08T12:00:00+00:00` |
| Africa/Cairo | `2026-03-08T12:00:00Z` | `2026-03-08T14:00:00+02:00` |
| Asia/Riyadh | `2026-03-08T12:00:00Z` | `2026-03-08T15:00:00+03:00` |

---

## Timezone Resolution Priority

The system resolves timezone in the following order:

```
┌─────────────────────────────────────────────────────────────────────┐
│                      Timezone Resolution Chain                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Request Parameter ─────────────────────────────────────────────▶│
│     ?timezone=Africa/Cairo                                           │
│     (Highest priority - explicit override)                           │
│                                                                      │
│  2. Student's Center Timezone ─────────────────────────────────────▶│
│     For authenticated mobile requests                                │
│     student.center.timezone                                          │
│                                                                      │
│  3. Route-bound Center Timezone ───────────────────────────────────▶│
│     For admin APIs with {center} in route                            │
│     /api/v1/admin/centers/{center}/...                               │
│                                                                      │
│  4. System Default Timezone ───────────────────────────────────────▶│
│     From system_settings.key='timezone'                              │
│     (Lowest priority - fallback)                                     │
│                                                                      │
│  5. UTC (Ultimate Fallback) ───────────────────────────────────────▶│
│     If no setting found                                              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Service Architecture

### TimezoneServiceInterface

```php
interface TimezoneServiceInterface
{
    /**
     * Get system-wide default timezone
     */
    public function getSystemTimezone(): string;

    /**
     * Get timezone for a specific center
     */
    public function getCenterTimezone(Center $center): string;

    /**
     * Resolve timezone with fallback chain
     */
    public function resolveTimezone(?Center $center = null): string;

    /**
     * Format datetime in specified timezone (ISO 8601)
     */
    public function formatDateTime(Carbon $date, ?string $timezone = null): string;

    /**
     * Format date only in specified timezone (YYYY-MM-DD)
     */
    public function formatDate(Carbon $date, ?string $timezone = null): string;
}
```

### FormatsDates Trait (for Resources)

```php
trait FormatsDates
{
    /**
     * Format datetime using request's resolved timezone
     */
    protected function formatDateTime(?Carbon $date): ?string
    {
        if (!$date) return null;
        $timezone = $this->resolveTimezone();
        return $date->copy()->setTimezone($timezone)->toIso8601String();
    }

    /**
     * Format date-only using request's resolved timezone
     */
    protected function formatDate(?Carbon $date): ?string
    {
        if (!$date) return null;
        $timezone = $this->resolveTimezone();
        return $date->copy()->setTimezone($timezone)->toDateString();
    }

    /**
     * Get timezone from request attributes (set by middleware)
     */
    protected function resolveTimezone(): string
    {
        return request()->attributes->get('timezone', 'UTC');
    }
}
```

---

## Middleware: ResolveTimezone

Automatically sets timezone context for all API requests:

```php
class ResolveTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $this->resolveTimezone($request);
        $request->attributes->set('timezone', $timezone);
        return $next($request);
    }

    private function resolveTimezone(Request $request): string
    {
        // 1. Explicit parameter (analytics, reports)
        if ($request->has('timezone')) {
            return $request->input('timezone');
        }

        // 2. Student's center (mobile app)
        if ($student = $request->user('student')) {
            return $student->center?->getEffectiveTimezone() ?? 'UTC';
        }

        // 3. Route-bound center (admin panel)
        if ($center = $request->route('center')) {
            return $center->getEffectiveTimezone();
        }

        // 4. System default
        return app(TimezoneServiceInterface::class)->getSystemTimezone();
    }
}
```

---

## Frontend Integration Guide

### Admin Panel

When displaying dates in the admin panel:

```javascript
// Dates from API are already in the correct timezone
// Just parse and display as-is

const enrollment = await api.get('/enrollments/123');
// enrollment.enrolled_at = "2026-03-08T14:30:00+02:00"

// Display directly (already in Cairo time for Cairo center)
const date = new Date(enrollment.enrolled_at);
console.log(date.toLocaleString('ar-EG')); // Displays in Arabic/Egyptian format
```

### Mobile App

```javascript
// For mobile students, API returns dates in their center's timezone
const courses = await api.get('/mobile/courses');

// Each datetime field is in the student's center timezone
courses.data.forEach(course => {
  console.log(course.created_at); // "2026-03-08T14:30:00+02:00"
});
```

### Timezone Dropdown (Admin)

```javascript
// Common timezones for the region
const SUPPORTED_TIMEZONES = [
  { id: 'UTC', label: 'UTC (Coordinated Universal Time)' },
  { id: 'Africa/Cairo', label: 'Cairo (Egypt) - UTC+2' },
  { id: 'Asia/Riyadh', label: 'Riyadh (Saudi Arabia) - UTC+3' },
  { id: 'Asia/Dubai', label: 'Dubai (UAE) - UTC+4' },
  { id: 'Asia/Kuwait', label: 'Kuwait - UTC+3' },
  { id: 'Asia/Amman', label: 'Amman (Jordan) - UTC+2/+3' },
];

// Render dropdown for center settings
<select name="timezone">
  {SUPPORTED_TIMEZONES.map(tz => (
    <option value={tz.id}>{tz.label}</option>
  ))}
</select>
```

---

## API Response Examples

### Before (Inconsistent)

```json
{
  "enrolled_at": "2026-03-08 12:30:00",
  "created_at": "2026-03-08T12:30:00.000000Z",
  "expires_at": null
}
```

### After (Standardized ISO 8601)

```json
{
  "enrolled_at": "2026-03-08T14:30:00+02:00",
  "created_at": "2026-03-08T14:30:00+02:00",
  "expires_at": null
}
```

### Analytics Meta (Unchanged - Already Correct)

```json
{
  "meta": {
    "range": {
      "from": "2026-03-01",
      "to": "2026-03-08"
    },
    "timezone": "Africa/Cairo",
    "generated_at": "2026-03-08T14:30:00+02:00"
  }
}
```

---

## Files to Create

### New Files (9)

| File | Description |
|------|-------------|
| `database/migrations/2026_03_08_000001_add_timezone_to_centers_table.php` | Add timezone column |
| `app/Services/Timezone/Contracts/TimezoneServiceInterface.php` | Service interface |
| `app/Services/Timezone/TimezoneService.php` | Service implementation |
| `app/Http/Resources/Concerns/FormatsDates.php` | Date formatting trait |
| `app/Http/Middleware/ResolveTimezone.php` | Timezone resolution middleware |
| `tests/Unit/Services/Timezone/TimezoneServiceTest.php` | Unit tests |
| `tests/Feature/Middleware/ResolveTimezoneMiddlewareTest.php` | Feature tests |
| `tests/Feature/Admin/Centers/CenterTimezoneTest.php` | Feature tests |
| `docs/feature/timezone_handling.md` | This documentation |

### Modified Files (~21)

| File | Changes |
|------|---------|
| `app/Models/Center.php` | Add timezone, getEffectiveTimezone() |
| `app/Services/Settings/PolicySettingsService.php` | Add center-scope timezone |
| `app/Providers/AppServiceProvider.php` | Bind TimezoneService |
| `bootstrap/app.php` | Register middleware |
| `app/Http/Requests/Admin/Centers/UpdateCenterRequest.php` | Add timezone validation |
| `app/Http/Resources/Admin/CenterResource.php` | Include timezone field |
| `app/Http/Resources/Admin/*.php` | Add FormatsDates trait (~8 files) |
| `app/Http/Resources/Mobile/*.php` | Add FormatsDates trait (~5 files) |

---

## Backward Compatibility

| Concern | Mitigation |
|---------|-----------|
| Default behavior | Centers without timezone use system default (UTC) |
| Existing analytics | `timezone` parameter still works as before |
| Date format change | ISO 8601 is parseable by all clients |
| Mobile apps | Will receive center timezone automatically |
| Gradual rollout | Centers can be updated one at a time |

---

## Scalability

| Region | Action Required |
|--------|-----------------|
| Egypt (current) | Set center timezone to `Africa/Cairo` |
| KSA (planned) | Set center timezone to `Asia/Riyadh` |
| New regions | Just set appropriate timezone, no code changes |

### DST Handling

PHP/Carbon automatically handles Daylight Saving Time for regions that observe it:

- Egypt: Currently no DST
- Saudi Arabia: No DST
- Jordan: Has DST (automatic conversion)
- European centers: Has DST (automatic conversion)

---

## Testing

### Unit Tests

```php
// tests/Unit/Services/Timezone/TimezoneServiceTest.php
test('returns utc as default system timezone', function () {
    $service = app(TimezoneServiceInterface::class);
    expect($service->getSystemTimezone())->toBe('UTC');
});

test('returns center timezone when set', function () {
    $center = Center::factory()->create(['timezone' => 'Africa/Cairo']);
    $service = app(TimezoneServiceInterface::class);
    expect($service->getCenterTimezone($center))->toBe('Africa/Cairo');
});

test('formats datetime in correct timezone', function () {
    $service = app(TimezoneServiceInterface::class);
    $utcDate = Carbon::parse('2026-03-08 12:00:00', 'UTC');

    $cairoFormatted = $service->formatDateTime($utcDate, 'Africa/Cairo');
    expect($cairoFormatted)->toBe('2026-03-08T14:00:00+02:00');
});
```

### Feature Tests

```php
// tests/Feature/Admin/Centers/CenterTimezoneTest.php
test('can set center timezone', function () {
    $admin = createAdmin();
    $center = Center::factory()->create();

    $response = $this->actingAs($admin)
        ->putJson("/api/v1/admin/centers/{$center->id}", [
            'timezone' => 'Africa/Cairo',
        ]);

    $response->assertOk();
    expect($center->fresh()->timezone)->toBe('Africa/Cairo');
});

test('api responses use center timezone', function () {
    $center = Center::factory()->create(['timezone' => 'Africa/Cairo']);
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'enrolled_at' => '2026-03-08 12:00:00', // UTC
    ]);

    $response = $this->actingAs(createAdmin())
        ->getJson("/api/v1/admin/centers/{$center->id}/enrollments/{$enrollment->id}");

    $response->assertOk();
    // Should be +02:00 (Cairo time)
    expect($response->json('data.enrolled_at'))->toContain('+02:00');
});
```

---

## Verification Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Run PHPStan: `./vendor/bin/phpstan analyse`
- [ ] Run Pint: `./vendor/bin/pint`
- [ ] Run tests: `php artisan test --filter=Timezone`
- [ ] Manual test:
  - [ ] Set system timezone to UTC
  - [ ] Create center with timezone "Africa/Cairo"
  - [ ] Verify API responses show dates in Cairo timezone (+02:00)
  - [ ] Create enrollment, verify mobile app sees Cairo time
  - [ ] Test analytics still works with explicit timezone param
