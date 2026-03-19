# Analytics Backend Enrichment

## Overview
Enrich analytics API responses with time-series trends, period comparison, and translated labels for the frontend dashboard charts.

## Phases

### Phase 1: Shared Infrastructure
- `generateDateSeries()` helper in `AnalyticsSupportService` — fills date gaps with zeros
- `previousPeriodDates()` helper — computes equivalent preceding date range
- Create `lang/en/analytics.php` and `lang/ar/analytics.php` for static UI labels
- Update `AnalyticsSupportServiceInterface`

### Phase 2: Overview Trends + Previous Period + Labels
- `enrollments_over_time`: GROUP BY DATE(enrolled_at)
- `active_learners_over_time`: GROUP BY DATE(started_at) from playback_sessions
- `courses_created_over_time`: GROUP BY DATE(created_at)
- `previous_period`: re-run overview queries with shifted dates
- `labels`: translated strings (Published, Unpublished, Branded, Unbranded, No centers)

### Phase 3: Learners & Enrollments Trends + Labels
- `registrations_over_time`: GROUP BY DATE(created_at) on students
- `enrollments.over_time`: GROUP BY DATE(enrolled_at), status pivot (active/pending/cancelled/deactivated)
- `labels`: Active, Pending, Deactivated, Cancelled, Najaah App

### Phase 4: Devices & Requests Trends + Labels
- `devices.registrations_over_time`: GROUP BY DATE(user_devices.created_at)
- `extra_views.over_time`: GROUP BY DATE(created_at), status pivot (pending/approved/rejected)
- `labels`: Pending, Approved, Rejected, Pre-approved, Mobile, OTP, Admin

### Phase 5: Courses & Media Labels
- `labels`: Draft, Uploading, Ready, Published, Archived, Pending, Processing, Videos, PDFs

### Phase 6: Quality
- Update existing analytics tests for new response fields
- PHPStan + Pint compliance

## Data Format Conventions
- **TimeSeriesPoint**: `{ "date": "YYYY-MM-DD", "count": number }`
- **StatusTimeSeriesPoint**: `{ "date": "YYYY-MM-DD", "active": number, "pending": number, "cancelled": number, "deactivated": number }`
- **RequestTimeSeriesPoint**: `{ "date": "YYYY-MM-DD", "pending": number, "approved": number, "rejected": number }`
- All `over_time` arrays contain one entry per day within the requested from/to date range
- Labels are locale-aware via `__('analytics.key')` using X-Locale header resolution

## Design Decisions
- Time series generated in service layer, resources are pass-through
- Previous period computed inside overview service (cached together)
- All new fields are additive — no breaking changes
- Date bucketing converts UTC results back to request timezone
