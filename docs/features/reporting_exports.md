# Reporting & Exports System

> Advanced reporting with custom report builder, multiple export formats (CSV, Excel, PDF, JSON), and scheduled email delivery.

## Overview

This feature provides a comprehensive reporting system:
- **Core Reports**: Students, Enrollments, Course Progress, Video Analytics, Quiz/Assignment Results
- **Export Formats**: CSV, Excel (.xlsx), PDF, JSON
- **Custom Builder**: Visual report builder to create custom reports with any fields
- **Scheduling**: Daily/weekly/monthly reports delivered via email
- **Report History**: Download center for generated reports

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        REPORTING & EXPORTS SYSTEM                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  REPORT DEFINITION                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌──────────────┐   ┌──────────────┐   ┌──────────────┐             │   │
│  │  │  Predefined  │   │   Custom     │   │  Scheduled   │             │   │
│  │  │   Reports    │   │   Reports    │   │   Reports    │             │   │
│  │  │  (templates) │   │  (user-made) │   │  (automated) │             │   │
│  │  └──────────────┘   └──────────────┘   └──────────────┘             │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                          │                                                  │
│                          ▼                                                  │
│  REPORT EXECUTION                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐         │   │
│  │  │  Query   │──▶│  Format  │──▶│  Export  │──▶│ Delivery │         │   │
│  │  │  Engine  │   │  Engine  │   │  (file)  │   │ (email)  │         │   │
│  │  └──────────┘   └──────────┘   └──────────┘   └──────────┘         │   │
│  │                                                                      │   │
│  │  Data Sources:                  Formats:                             │   │
│  │  • Students                     • CSV                                │   │
│  │  • Enrollments                  • Excel (.xlsx)                      │   │
│  │  • PlaybackSessions             • PDF                                │   │
│  │  • QuizAttempts                 • JSON                               │   │
│  │  • AssignmentSubmissions                                             │   │
│  │  • Courses/Videos/PDFs                                               │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  REPORT STORAGE                                                            │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                      │   │
│  │  ┌──────────────────────────────────────────────────────────┐       │   │
│  │  │               Generated Reports (Download Center)         │       │   │
│  │  │  • File storage (local/S3/Bunny)                         │       │   │
│  │  │  • Auto-cleanup after 30 days                            │       │   │
│  │  │  • Access control per user/center                        │       │   │
│  │  └──────────────────────────────────────────────────────────┘       │   │
│  │                                                                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### report_definitions

Predefined and custom report templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Null for system reports, FK for center-specific |
| `created_by` | FK → users | Admin who created (null for system) |
| `name` | varchar | Report name |
| `description` | text | Report description |
| `type` | tinyint | 0=system, 1=custom |
| `data_source` | varchar | Primary entity: 'students', 'enrollments', etc. |
| `columns` | JSON | Selected columns configuration |
| `filters` | JSON | Available/default filter configuration |
| `sorting` | JSON | Default sort configuration |
| `grouping` | JSON | Grouping configuration |
| `aggregations` | JSON | Sum, count, avg configurations |
| `is_active` | boolean | Report is available |
| `is_public` | boolean | Visible to all center admins |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[center_id, type, is_active]`
- `[created_by]`

**Columns Configuration Example:**
```json
{
  "columns": [
    {"field": "user.name", "label": "Student Name", "visible": true},
    {"field": "user.email", "label": "Email", "visible": true},
    {"field": "user.phone", "label": "Phone", "visible": false},
    {"field": "enrollment.enrolled_at", "label": "Enrollment Date", "visible": true},
    {"field": "enrollment.status", "label": "Status", "visible": true},
    {"field": "course.title", "label": "Course", "visible": true},
    {"field": "progress.percent", "label": "Progress %", "visible": true}
  ]
}
```

### report_schedules

Scheduled report configurations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `report_definition_id` | FK → report_definitions | Report to run |
| `center_id` | FK → centers | Center scope |
| `created_by` | FK → users | Admin who scheduled |
| `name` | varchar | Schedule name |
| `frequency` | tinyint | 0=daily, 1=weekly, 2=monthly |
| `day_of_week` | tinyint | For weekly (0=Sun, 6=Sat) |
| `day_of_month` | tinyint | For monthly (1-28) |
| `time_of_day` | time | Time to run (in center timezone) |
| `timezone` | varchar | Timezone for scheduling |
| `filters` | JSON | Applied filters for this schedule |
| `export_format` | varchar | 'csv', 'excel', 'pdf', 'json' |
| `recipients` | JSON | Email addresses to send to |
| `include_empty` | boolean | Send even if no data |
| `is_active` | boolean | Schedule is active |
| `last_run_at` | timestamp | Last execution time |
| `next_run_at` | timestamp | Next scheduled execution |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `[center_id, is_active]`
- `[next_run_at, is_active]` (for scheduler)
- `[report_definition_id]`

### report_executions

Generated report instances.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `report_definition_id` | FK → report_definitions | Source report |
| `report_schedule_id` | FK → report_schedules | Schedule (if automated) |
| `center_id` | FK → centers | Center scope |
| `requested_by` | FK → users | Admin who requested (null if scheduled) |
| `status` | tinyint | 0=queued, 1=processing, 2=completed, 3=failed |
| `filters_applied` | JSON | Actual filters used |
| `export_format` | varchar | Format generated |
| `file_path` | varchar | Storage path |
| `file_size_bytes` | bigint | File size |
| `row_count` | int | Number of rows in report |
| `execution_time_ms` | int | Time to generate |
| `error_message` | text | Error if failed |
| `started_at` | timestamp | |
| `completed_at` | timestamp | |
| `expires_at` | timestamp | When file will be deleted |
| `downloaded_at` | timestamp | Last download time |
| `emailed_at` | timestamp | When emailed (if scheduled) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[center_id, status]`
- `[requested_by, created_at]`
- `[report_schedule_id]`
- `[expires_at]` (for cleanup job)

### report_field_definitions

Available fields for custom report builder.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `data_source` | varchar | Entity: 'students', 'enrollments', etc. |
| `field_key` | varchar | Unique field identifier |
| `field_path` | varchar | Database path (e.g., 'user.name', 'enrollment.status') |
| `label_translations` | JSON | Display label |
| `data_type` | varchar | 'string', 'number', 'date', 'boolean', 'enum' |
| `enum_values` | JSON | For enum fields, possible values |
| `is_filterable` | boolean | Can be used as filter |
| `is_sortable` | boolean | Can be sorted |
| `is_groupable` | boolean | Can be grouped by |
| `is_aggregatable` | boolean | Can be summed/averaged |
| `category` | varchar | Field category for UI grouping |
| `order` | int | Display order |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Indexes:**
- `[data_source, category]`
- `UNIQUE [data_source, field_key]`

---

## Enums

### ReportType

```php
enum ReportType: int
{
    case System = 0;    // Predefined system reports
    case Custom = 1;    // User-created reports
}
```

### ReportFrequency

```php
enum ReportFrequency: int
{
    case Daily = 0;
    case Weekly = 1;
    case Monthly = 2;
}
```

### ReportExecutionStatus

```php
enum ReportExecutionStatus: int
{
    case Queued = 0;
    case Processing = 1;
    case Completed = 2;
    case Failed = 3;
}
```

### ExportFormat

```php
enum ExportFormat: string
{
    case CSV = 'csv';
    case Excel = 'excel';
    case PDF = 'pdf';
    case JSON = 'json';
}
```

### DataSource

```php
enum DataSource: string
{
    case Students = 'students';
    case Enrollments = 'enrollments';
    case CourseProgress = 'course_progress';
    case VideoAnalytics = 'video_analytics';
    case QuizResults = 'quiz_results';
    case AssignmentResults = 'assignment_results';
}
```

---

## Predefined System Reports

### 1. Students Report

**Data Source:** `students`

| Field | Path | Type |
|-------|------|------|
| Student ID | `user.id` | number |
| Name | `user.name` | string |
| Email | `user.email` | string |
| Phone | `user.phone` | string |
| Status | `user.status` | enum |
| Grade | `user.grade.name` | string |
| School | `user.school.name` | string |
| College | `user.college.name` | string |
| Registered At | `user.created_at` | date |
| Last Login | `user.last_login_at` | date |
| Courses Enrolled | `enrollments_count` | number |
| Courses Completed | `completed_courses_count` | number |

### 2. Enrollments Report

**Data Source:** `enrollments`

| Field | Path | Type |
|-------|------|------|
| Enrollment ID | `enrollment.id` | number |
| Student Name | `user.name` | string |
| Student Email | `user.email` | string |
| Course Title | `course.title` | string |
| Enrolled At | `enrollment.enrolled_at` | date |
| Expires At | `enrollment.expires_at` | date |
| Status | `enrollment.status` | enum |
| Progress % | `progress.percent` | number |
| Videos Completed | `progress.completed_videos` | number |
| Last Activity | `progress.last_activity_at` | date |

### 3. Course Progress Report

**Data Source:** `course_progress`

| Field | Path | Type |
|-------|------|------|
| Student Name | `user.name` | string |
| Course Title | `course.title` | string |
| Total Videos | `progress.total_videos` | number |
| Completed Videos | `progress.completed_videos` | number |
| Progress % | `progress.percent` | number |
| Is Completed | `progress.is_completed` | boolean |
| Completed At | `progress.completed_at` | date |
| Time Spent (min) | `total_watch_time` | number |
| Certificate Issued | `has_certificate` | boolean |

### 4. Video Analytics Report

**Data Source:** `video_analytics`

| Field | Path | Type |
|-------|------|------|
| Video Title | `video.title` | string |
| Course Title | `course.title` | string |
| Total Views | `views_count` | number |
| Unique Viewers | `unique_viewers` | number |
| Avg Watch % | `avg_progress` | number |
| Full Plays | `full_plays_count` | number |
| Total Watch Time (min) | `total_watch_time` | number |
| Avg Watch Time (min) | `avg_watch_time` | number |

### 5. Quiz Results Report

**Data Source:** `quiz_results`

| Field | Path | Type |
|-------|------|------|
| Student Name | `user.name` | string |
| Quiz Title | `quiz.title` | string |
| Course Title | `course.title` | string |
| Attempts | `attempts_count` | number |
| Best Score % | `best_score` | number |
| Latest Score % | `latest_score` | number |
| Passed | `passed` | boolean |
| Time Spent (min) | `time_spent` | number |
| Completed At | `completed_at` | date |

### 6. Assignment Results Report

**Data Source:** `assignment_results`

| Field | Path | Type |
|-------|------|------|
| Student Name | `user.name` | string |
| Assignment Title | `assignment.title` | string |
| Course Title | `course.title` | string |
| Submission Status | `submission.status` | enum |
| Submitted At | `submission.submitted_at` | date |
| Is Late | `submission.is_late` | boolean |
| Score | `submission.score` | number |
| Score After Penalty | `submission.score_after_penalty` | number |
| Graded By | `grader.name` | string |
| Graded At | `submission.graded_at` | date |

---

## Service Layer

### ReportDefinitionService

```php
interface ReportDefinitionServiceInterface
{
    // System reports
    public function getSystemReports(): Collection;

    // Custom reports CRUD
    public function create(User $admin, Center $center, array $data): ReportDefinition;
    public function update(ReportDefinition $report, array $data): ReportDefinition;
    public function delete(ReportDefinition $report): void;
    public function duplicate(ReportDefinition $report, User $admin): ReportDefinition;

    // Get reports for admin
    public function getAvailableReports(User $admin, ?Center $center): Collection;

    // Field definitions
    public function getFieldsForDataSource(string $dataSource): Collection;
}
```

### ReportExecutionService

```php
interface ReportExecutionServiceInterface
{
    // Execute report
    public function execute(
        ReportDefinition $report,
        User $admin,
        array $filters,
        ExportFormat $format
    ): ReportExecution;

    // Execute scheduled report
    public function executeScheduled(ReportSchedule $schedule): ReportExecution;

    // Get execution status
    public function getStatus(ReportExecution $execution): ReportExecution;

    // Download generated file
    public function download(ReportExecution $execution): StreamedResponse;

    // Get user's executions
    public function getUserExecutions(User $admin, ?Center $center): Collection;

    // Cleanup expired files
    public function cleanupExpired(): int;
}
```

### ReportQueryService

```php
interface ReportQueryServiceInterface
{
    // Build and execute query based on report definition
    public function query(
        ReportDefinition $report,
        array $filters,
        ?int $limit = null
    ): Collection;

    // Get row count for preview
    public function count(ReportDefinition $report, array $filters): int;

    // Preview data (limited rows)
    public function preview(ReportDefinition $report, array $filters, int $limit = 10): Collection;
}
```

### ReportExportService

```php
interface ReportExportServiceInterface
{
    // Export to various formats
    public function toCSV(Collection $data, array $columns): string;
    public function toExcel(Collection $data, array $columns, string $title): string;
    public function toPDF(Collection $data, array $columns, string $title): string;
    public function toJSON(Collection $data, array $columns): string;

    // Store exported file
    public function store(string $content, ExportFormat $format, string $filename): string;
}
```

### ReportScheduleService

```php
interface ReportScheduleServiceInterface
{
    // CRUD
    public function create(ReportDefinition $report, User $admin, array $data): ReportSchedule;
    public function update(ReportSchedule $schedule, array $data): ReportSchedule;
    public function delete(ReportSchedule $schedule): void;
    public function toggleActive(ReportSchedule $schedule): ReportSchedule;

    // Get schedules
    public function getSchedules(User $admin, ?Center $center): Collection;

    // Get due schedules (for scheduler)
    public function getDueSchedules(): Collection;

    // Calculate next run
    public function calculateNextRun(ReportSchedule $schedule): Carbon;
}
```

### ReportEmailService

```php
interface ReportEmailServiceInterface
{
    // Send report via email
    public function send(ReportExecution $execution, array $recipients): void;

    // Build email with attachment
    public function buildEmail(ReportExecution $execution): Mailable;
}
```

---

## API Endpoints

### Admin - Report Definitions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/reports` | List available reports |
| GET | `/api/v1/admin/centers/{center}/reports/system` | List system reports |
| POST | `/api/v1/admin/centers/{center}/reports` | Create custom report |
| GET | `/api/v1/admin/centers/{center}/reports/{report}` | Get report definition |
| PUT | `/api/v1/admin/centers/{center}/reports/{report}` | Update custom report |
| DELETE | `/api/v1/admin/centers/{center}/reports/{report}` | Delete custom report |
| POST | `/api/v1/admin/centers/{center}/reports/{report}/duplicate` | Duplicate report |

### Admin - Report Builder

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/reports/data-sources` | List available data sources |
| GET | `/api/v1/admin/reports/data-sources/{source}/fields` | Get fields for data source |
| POST | `/api/v1/admin/centers/{center}/reports/{report}/preview` | Preview report data |
| GET | `/api/v1/admin/reports/filter-operators` | Get available filter operators |

### Admin - Report Execution

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/admin/centers/{center}/reports/{report}/execute` | Execute report |
| GET | `/api/v1/admin/centers/{center}/report-executions` | List my executions |
| GET | `/api/v1/admin/centers/{center}/report-executions/{execution}` | Get execution status |
| GET | `/api/v1/admin/centers/{center}/report-executions/{execution}/download` | Download report file |
| DELETE | `/api/v1/admin/centers/{center}/report-executions/{execution}` | Delete execution |

### Admin - Report Schedules

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/report-schedules` | List schedules |
| POST | `/api/v1/admin/centers/{center}/reports/{report}/schedules` | Create schedule |
| GET | `/api/v1/admin/centers/{center}/report-schedules/{schedule}` | Get schedule |
| PUT | `/api/v1/admin/centers/{center}/report-schedules/{schedule}` | Update schedule |
| DELETE | `/api/v1/admin/centers/{center}/report-schedules/{schedule}` | Delete schedule |
| POST | `/api/v1/admin/centers/{center}/report-schedules/{schedule}/toggle` | Toggle active |
| POST | `/api/v1/admin/centers/{center}/report-schedules/{schedule}/run-now` | Run immediately |

---

## Custom Report Builder

### Column Configuration

```json
{
  "columns": [
    {
      "field": "user.name",
      "label": "Student Name",
      "visible": true,
      "width": 200
    },
    {
      "field": "enrollment.enrolled_at",
      "label": "Enrolled",
      "visible": true,
      "format": "date",
      "dateFormat": "Y-m-d"
    },
    {
      "field": "progress.percent",
      "label": "Progress",
      "visible": true,
      "format": "percentage"
    }
  ]
}
```

### Filter Configuration

```json
{
  "filters": [
    {
      "field": "enrollment.status",
      "operator": "equals",
      "value": 0,
      "enabled": true
    },
    {
      "field": "enrollment.enrolled_at",
      "operator": "between",
      "value": ["2026-01-01", "2026-03-31"],
      "enabled": true
    },
    {
      "field": "course.id",
      "operator": "in",
      "value": [1, 2, 3],
      "enabled": true
    }
  ]
}
```

### Filter Operators

| Operator | Types | Description |
|----------|-------|-------------|
| `equals` | all | Exact match |
| `not_equals` | all | Not equal |
| `contains` | string | Contains substring |
| `starts_with` | string | Starts with |
| `ends_with` | string | Ends with |
| `greater_than` | number, date | Greater than |
| `less_than` | number, date | Less than |
| `between` | number, date | Between two values |
| `in` | all | In list of values |
| `not_in` | all | Not in list |
| `is_null` | all | Is null |
| `is_not_null` | all | Is not null |

### Grouping & Aggregation

```json
{
  "grouping": {
    "field": "course.id",
    "include_details": false
  },
  "aggregations": [
    {
      "field": "enrollment.id",
      "function": "count",
      "label": "Total Enrollments"
    },
    {
      "field": "progress.percent",
      "function": "avg",
      "label": "Avg Progress"
    }
  ]
}
```

### Aggregation Functions

| Function | Types | Description |
|----------|-------|-------------|
| `count` | all | Count rows |
| `count_distinct` | all | Count unique values |
| `sum` | number | Sum values |
| `avg` | number | Average |
| `min` | number, date | Minimum |
| `max` | number, date | Maximum |

---

## Export Formats

### CSV Format

```csv
"Student Name","Email","Course","Progress %","Enrolled At"
"John Doe","john@example.com","Laravel Basics","85.5","2026-01-15"
"Jane Smith","jane@example.com","Laravel Basics","100.0","2026-01-10"
```

### Excel Format

- Multiple sheets support
- Formatted headers
- Auto-column width
- Number/date formatting
- Totals row (optional)

### PDF Format

- Header with report title and date
- Center logo (if applicable)
- Formatted table
- Page numbers
- Filters applied summary

### JSON Format

```json
{
  "report": {
    "name": "Enrollment Report",
    "generated_at": "2026-03-03T10:30:00Z",
    "filters": {
      "date_range": ["2026-01-01", "2026-03-31"]
    }
  },
  "meta": {
    "total_rows": 150,
    "page": 1,
    "per_page": 50
  },
  "data": [
    {
      "student_name": "John Doe",
      "email": "john@example.com",
      "course": "Laravel Basics",
      "progress_percent": 85.5,
      "enrolled_at": "2026-01-15"
    }
  ]
}
```

---

## Scheduled Reports

### Schedule Configuration

```json
{
  "frequency": "weekly",
  "day_of_week": 1,
  "time_of_day": "08:00",
  "timezone": "Asia/Riyadh",
  "export_format": "excel",
  "recipients": [
    "admin@center.com",
    "manager@center.com"
  ],
  "filters": {
    "date_range": "last_7_days"
  },
  "include_empty": false
}
```

### Dynamic Date Filters

| Value | Description |
|-------|-------------|
| `today` | Current day |
| `yesterday` | Previous day |
| `last_7_days` | Last 7 days |
| `last_30_days` | Last 30 days |
| `this_week` | Current week (Sun-Sat) |
| `last_week` | Previous week |
| `this_month` | Current month |
| `last_month` | Previous month |
| `this_quarter` | Current quarter |
| `last_quarter` | Previous quarter |
| `this_year` | Current year |
| `last_year` | Previous year |

### Email Template

```
Subject: [Najaah] Scheduled Report: {Report Name} - {Date}

Body:
Hi {Recipient Name},

Your scheduled report "{Report Name}" is ready.

Report Details:
- Period: {Date Range}
- Records: {Row Count}
- Format: {Format}

{If has data}
Please find the report attached to this email.
{Else}
No data matched the report criteria for this period.
{End if}

This report was automatically generated by Najaah LMS.

Best regards,
Najaah LMS
```

---

## Jobs & Commands

### ProcessReportExecution Job

```php
class ProcessReportExecution implements ShouldQueue
{
    public function __construct(
        public ReportExecution $execution
    ) {}

    public function handle(
        ReportQueryService $queryService,
        ReportExportService $exportService
    ): void {
        // 1. Update status to processing
        // 2. Execute query
        // 3. Export to format
        // 4. Store file
        // 5. Update execution with results
    }
}
```

### RunScheduledReports Command

```php
// php artisan reports:run-scheduled
// Runs every 5 minutes via scheduler

class RunScheduledReports extends Command
{
    public function handle(ReportScheduleService $scheduleService): int
    {
        $due = $scheduleService->getDueSchedules();

        foreach ($due as $schedule) {
            ProcessScheduledReport::dispatch($schedule);
        }

        return self::SUCCESS;
    }
}
```

### CleanupExpiredReports Command

```php
// php artisan reports:cleanup
// Runs daily via scheduler

class CleanupExpiredReports extends Command
{
    public function handle(ReportExecutionService $service): int
    {
        $deleted = $service->cleanupExpired();
        $this->info("Deleted {$deleted} expired reports.");

        return self::SUCCESS;
    }
}
```

---

## Implementation Checklist

### Phase 1: Database Architecture (4 migrations)
- [ ] Create `report_definitions` table
- [ ] Create `report_schedules` table
- [ ] Create `report_executions` table
- [ ] Create `report_field_definitions` table
- [ ] Seed predefined system reports
- [ ] Seed field definitions for all data sources

### Phase 2: Enums & Models (5 models)
- [ ] Create enums (5 enums)
- [ ] Create `ReportDefinition` model
- [ ] Create `ReportSchedule` model
- [ ] Create `ReportExecution` model
- [ ] Create `ReportFieldDefinition` model

### Phase 3: Query Engine
- [ ] Create `ReportQueryService` - query builder
- [ ] Implement joins for related data
- [ ] Implement filtering logic
- [ ] Implement grouping logic
- [ ] Implement aggregations
- [ ] Implement sorting

### Phase 4: Export Engine
- [ ] Create `ReportExportService`
- [ ] Implement CSV export
- [ ] Implement Excel export (using PhpSpreadsheet or Maatwebsite/Excel)
- [ ] Implement PDF export (using DOMPDF or Snappy)
- [ ] Implement JSON export
- [ ] File storage service

### Phase 5: Scheduling & Email
- [ ] Create `ReportScheduleService`
- [ ] Create `ReportEmailService`
- [ ] Create scheduled report job
- [ ] Create email templates
- [ ] Register scheduler command

### Phase 6: Admin API
- [ ] Report definitions CRUD controller
- [ ] Report builder endpoints
- [ ] Report execution controller
- [ ] Report schedules controller
- [ ] Form requests
- [ ] Resources

### Phase 7: Jobs & Commands
- [ ] ProcessReportExecution job
- [ ] ProcessScheduledReport job
- [ ] RunScheduledReports command
- [ ] CleanupExpiredReports command
- [ ] Register in scheduler

### Phase 8: Quality & Testing
- [ ] Create factories
- [ ] Feature tests for report CRUD
- [ ] Feature tests for execution
- [ ] Feature tests for scheduling
- [ ] Unit tests for query builder
- [ ] Unit tests for export formats
- [ ] Run quality checks

---

## File Summary (~65 files)

```
Migrations (4):
- create_report_definitions_table.php
- create_report_schedules_table.php
- create_report_executions_table.php
- create_report_field_definitions_table.php

Enums (5):
- ReportType.php
- ReportFrequency.php
- ReportExecutionStatus.php
- ExportFormat.php
- DataSource.php

Models (4):
- ReportDefinition.php
- ReportSchedule.php
- ReportExecution.php
- ReportFieldDefinition.php

Services (10):
- ReportDefinitionServiceInterface.php + ReportDefinitionService.php
- ReportExecutionServiceInterface.php + ReportExecutionService.php
- ReportQueryServiceInterface.php + ReportQueryService.php
- ReportExportServiceInterface.php + ReportExportService.php
- ReportScheduleServiceInterface.php + ReportScheduleService.php
- ReportEmailServiceInterface.php + ReportEmailService.php

Controllers (4):
- Admin/ReportDefinitionController.php
- Admin/ReportBuilderController.php
- Admin/ReportExecutionController.php
- Admin/ReportScheduleController.php

Jobs (2):
- ProcessReportExecution.php
- ProcessScheduledReport.php

Commands (2):
- RunScheduledReports.php
- CleanupExpiredReports.php

Mail (1):
- ScheduledReportMail.php

Form Requests (~12)
Resources (~8)
Routes (1)
Factories (4)
Seeders (2):
- SystemReportsSeeder.php
- ReportFieldDefinitionsSeeder.php
Tests (~10)
```

---

## Dependencies

### Required Packages

```json
{
  "maatwebsite/excel": "^3.1",
  "barryvdh/laravel-dompdf": "^2.0"
}
```

Or alternatives:
- `phpoffice/phpspreadsheet` for Excel
- `knplabs/knp-snappy` for PDF (requires wkhtmltopdf)

---

## Testing Plan

```bash
# Run all reporting tests
php artisan test --filter="Report"
php artisan test --filter="Export"
```

### Key Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Create custom report | Feature | High |
| Execute report and get file | Feature | High |
| CSV export format | Unit | High |
| Excel export format | Unit | High |
| PDF export format | Unit | High |
| JSON export format | Unit | High |
| Filter operators work correctly | Unit | High |
| Grouping and aggregations | Unit | High |
| Create schedule | Feature | High |
| Scheduled report executes | Feature | High |
| Email delivery | Feature | High |
| Expired reports cleanup | Feature | Medium |
| Large dataset export (performance) | Feature | Medium |
