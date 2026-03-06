# Student Profile Enrichment

> Add educational profile data (grades, schools, colleges) to students via center-scoped lookup tables with full admin filtering and mobile selection capabilities.

## Overview

This feature enriches student profiles with educational information:
- **Grades**: Academic levels (Grade 1-12, University Year 1-6, Graduate)
- **Schools**: K-12 educational institutions
- **Colleges**: Universities and higher education institutions

All entities are center-scoped, allowing each center to manage their own lists.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                  STUDENT PROFILE ENRICHMENT                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CENTER-SCOPED LOOKUP TABLES                                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │   grades    │  │   schools   │  │  colleges   │              │
│  ├─────────────┤  ├─────────────┤  ├─────────────┤              │
│  │ center_id   │  │ center_id   │  │ center_id   │              │
│  │ name_trans  │  │ name_trans  │  │ name_trans  │              │
│  │ slug        │  │ slug        │  │ slug        │              │
│  │ stage       │  │ type        │  │ type        │              │
│  │ order       │  │ address     │  │ address     │              │
│  │ is_active   │  │ is_active   │  │ is_active   │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
│         │                │                │                     │
│         └────────────────┼────────────────┘                     │
│                          ▼                                      │
│                  ┌─────────────────┐                            │
│                  │  users (students)│                           │
│                  ├─────────────────┤                            │
│                  │ + grade_id (FK) │                            │
│                  │ + school_id (FK)│                            │
│                  │ + college_id(FK)│                            │
│                  └─────────────────┘                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### grades

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Center scope |
| `name_translations` | JSON | Localized name `{"en": "Grade 9", "ar": "..."}` |
| `slug` | varchar | URL-friendly identifier |
| `stage` | tinyint | 0=elementary, 1=middle, 2=high_school, 3=university, 4=graduate |
| `order` | int | Sort order within stage |
| `is_active` | boolean | Default true |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [center_id, slug]`
- `[center_id, stage, is_active]`
- `[center_id, order]`

### schools

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Center scope |
| `name_translations` | JSON | Localized name |
| `slug` | varchar | URL-friendly identifier |
| `type` | tinyint | 0=public, 1=private, 2=international, 3=other |
| `address` | text | Optional address |
| `is_active` | boolean | Default true |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [center_id, slug]`
- `[center_id, type, is_active]`

### colleges

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `center_id` | FK → centers | Center scope |
| `name_translations` | JSON | Localized name |
| `slug` | varchar | URL-friendly identifier |
| `type` | tinyint | Optional categorization |
| `address` | text | Optional address |
| `is_active` | boolean | Default true |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**Indexes:**
- `UNIQUE [center_id, slug]`
- `[center_id, is_active]`

### users (modifications)

| Column | Type | Description |
|--------|------|-------------|
| `grade_id` | FK → grades | Nullable, student's current grade |
| `school_id` | FK → schools | Nullable, student's school |
| `college_id` | FK → colleges | Nullable, student's college/university |

**Indexes:**
- `[grade_id]`
- `[school_id]`
- `[college_id]`

---

## Enums

### EducationalStage

```php
enum EducationalStage: int
{
    case Elementary = 0;   // Grades 1-5
    case Middle = 1;       // Grades 6-8
    case HighSchool = 2;   // Grades 9-12
    case University = 3;   // Year 1-6
    case Graduate = 4;     // Masters, PhD
}
```

### SchoolType

```php
enum SchoolType: int
{
    case Public = 0;
    case Private = 1;
    case International = 2;
    case Other = 3;
}
```

---

## API Endpoints

### Admin CRUD - Grades

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/grades` | List with pagination |
| POST | `/api/v1/admin/centers/{center}/grades` | Create grade |
| GET | `/api/v1/admin/centers/{center}/grades/{grade}` | Get single |
| PUT | `/api/v1/admin/centers/{center}/grades/{grade}` | Update |
| DELETE | `/api/v1/admin/centers/{center}/grades/{grade}` | Soft delete |

**List Filters:**
- `search` - Search in name
- `stage` - Filter by educational stage
- `is_active` - Filter by status
- `page`, `per_page` - Pagination

### Admin CRUD - Schools

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/schools` | List with pagination |
| POST | `/api/v1/admin/centers/{center}/schools` | Create school |
| GET | `/api/v1/admin/centers/{center}/schools/{school}` | Get single |
| PUT | `/api/v1/admin/centers/{center}/schools/{school}` | Update |
| DELETE | `/api/v1/admin/centers/{center}/schools/{school}` | Soft delete |

**List Filters:**
- `search` - Search in name
- `type` - Filter by school type
- `is_active` - Filter by status
- `page`, `per_page` - Pagination

### Admin CRUD - Colleges

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/centers/{center}/colleges` | List with pagination |
| POST | `/api/v1/admin/centers/{center}/colleges` | Create college |
| GET | `/api/v1/admin/centers/{center}/colleges/{college}` | Get single |
| PUT | `/api/v1/admin/centers/{center}/colleges/{college}` | Update |
| DELETE | `/api/v1/admin/centers/{center}/colleges/{college}` | Soft delete |

**List Filters:**
- `search` - Search in name
- `is_active` - Filter by status
- `page`, `per_page` - Pagination

### Admin Lookup Endpoints (Dropdowns)

| Method | Endpoint | Filters | Response |
|--------|----------|---------|----------|
| GET | `/api/v1/admin/centers/{center}/grades/lookup` | `?search=`, `?stage=`, `?is_active=` | `[{id, name, stage, stage_label, is_active}]` |
| GET | `/api/v1/admin/centers/{center}/schools/lookup` | `?search=`, `?type=`, `?is_active=` | `[{id, name, type, type_label, is_active}]` |
| GET | `/api/v1/admin/centers/{center}/colleges/lookup` | `?search=`, `?is_active=` | `[{id, name, is_active}]` |

**Notes:**
- Returns all items (no pagination) for dropdown population
- Includes inactive items by default (for edit forms showing previously selected values)
- Sorted by `order` (grades) or `name` (schools/colleges)

### Mobile Lookup Endpoints (Student Selection)

| Method | Endpoint | Filters | Response |
|--------|----------|---------|----------|
| GET | `/api/v1/centers/{center}/grades` | `?search=`, `?stage=` | `[{id, name}]` |
| GET | `/api/v1/centers/{center}/schools` | `?search=`, `?type=` | `[{id, name}]` |
| GET | `/api/v1/centers/{center}/colleges` | `?search=` | `[{id, name}]` |

**Notes:**
- Returns ONLY active items
- Name is localized based on request locale
- Sorted by `order` (grades) or `name` (schools/colleges)
- No pagination (lightweight for mobile)

### Student Profile Update

| Method | Endpoint | Description |
|--------|----------|-------------|
| PATCH | `/api/v1/profile/education` | Student updates their educational info |

**Request Body:**
```json
{
    "grade_id": 5,
    "school_id": 12,
    "college_id": null
}
```

**Validation:**
- IDs must belong to student's center (or unbranded centers)
- IDs must reference active records

### Admin Student List Filters (Enhancement)

Add to existing `GET /api/v1/admin/centers/{center}/students`:

| Filter | Type | Description |
|--------|------|-------------|
| `grade_id` | int | Filter by specific grade |
| `school_id` | int | Filter by specific school |
| `college_id` | int | Filter by specific college |
| `stage` | int | Filter by educational stage (all grades in stage) |

---

## Service Layer

### GradeService

```php
interface GradeServiceInterface
{
    // CRUD
    public function list(Center $center, array $filters): LengthAwarePaginator;
    public function lookup(Center $center, array $filters): Collection;
    public function create(Center $center, array $data): Grade;
    public function update(Grade $grade, array $data): Grade;
    public function delete(Grade $grade): void;

    // Validation
    public function existsAndActive(int $gradeId, int $centerId): bool;
}
```

### SchoolService

```php
interface SchoolServiceInterface
{
    // CRUD
    public function list(Center $center, array $filters): LengthAwarePaginator;
    public function lookup(Center $center, array $filters): Collection;
    public function create(Center $center, array $data): School;
    public function update(School $school, array $data): School;
    public function delete(School $school): void;

    // Validation
    public function existsAndActive(int $schoolId, int $centerId): bool;
}
```

### CollegeService

```php
interface CollegeServiceInterface
{
    // CRUD
    public function list(Center $center, array $filters): LengthAwarePaginator;
    public function lookup(Center $center, array $filters): Collection;
    public function create(Center $center, array $data): College;
    public function update(College $college, array $data): College;
    public function delete(College $college): void;

    // Validation
    public function existsAndActive(int $collegeId, int $centerId): bool;
}
```

---

## Request/Response Examples

### Create Grade

**POST** `/api/v1/admin/centers/1/grades`

```json
{
    "name_translations": {
        "en": "Grade 9",
        "ar": "الصف التاسع"
    },
    "stage": 2,
    "order": 9,
    "is_active": true
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Grade 9",
        "name_translations": {
            "en": "Grade 9",
            "ar": "الصف التاسع"
        },
        "slug": "grade-9",
        "stage": 2,
        "stage_label": "High School",
        "order": 9,
        "is_active": true,
        "students_count": 0,
        "created_at": "2026-03-03T10:00:00Z"
    }
}
```

### Admin Grades Lookup

**GET** `/api/v1/admin/centers/1/grades/lookup?stage=2`

**Response:**
```json
{
    "success": true,
    "data": [
        {"id": 1, "name": "Grade 9", "stage": 2, "stage_label": "High School", "is_active": true},
        {"id": 2, "name": "Grade 10", "stage": 2, "stage_label": "High School", "is_active": true},
        {"id": 3, "name": "Grade 11", "stage": 2, "stage_label": "High School", "is_active": false},
        {"id": 4, "name": "Grade 12", "stage": 2, "stage_label": "High School", "is_active": true}
    ]
}
```

### Mobile Grades Lookup

**GET** `/api/v1/centers/1/grades?stage=3`
**Header:** `Accept-Language: ar`

**Response:**
```json
{
    "success": true,
    "data": [
        {"id": 5, "name": "السنة الأولى"},
        {"id": 6, "name": "السنة الثانية"},
        {"id": 7, "name": "السنة الثالثة"},
        {"id": 8, "name": "السنة الرابعة"}
    ]
}
```

### Student Update Education

**PATCH** `/api/v1/profile/education`

```json
{
    "grade_id": 5,
    "school_id": null,
    "college_id": 3
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "grade": {"id": 5, "name": "Year 1"},
        "school": null,
        "college": {"id": 3, "name": "Cairo University"}
    },
    "message": "Educational profile updated successfully."
}
```

---

## Error Codes

| Code | HTTP | Cause |
|------|------|-------|
| `GRADE_NOT_FOUND` | 404 | Grade ID not found |
| `SCHOOL_NOT_FOUND` | 404 | School ID not found |
| `COLLEGE_NOT_FOUND` | 404 | College ID not found |
| `GRADE_INACTIVE` | 422 | Cannot assign inactive grade |
| `SCHOOL_INACTIVE` | 422 | Cannot assign inactive school |
| `COLLEGE_INACTIVE` | 422 | Cannot assign inactive college |
| `GRADE_HAS_STUDENTS` | 422 | Cannot delete grade with assigned students |
| `SCHOOL_HAS_STUDENTS` | 422 | Cannot delete school with assigned students |
| `COLLEGE_HAS_STUDENTS` | 422 | Cannot delete college with assigned students |
| `DUPLICATE_SLUG` | 422 | Slug already exists in center |

---

## Implementation Checklist

### Phase 1: Database Architecture
- [ ] Create `grades` table migration
- [ ] Create `schools` table migration
- [ ] Create `colleges` table migration
- [ ] Add `grade_id`, `school_id`, `college_id` to `users` table

### Phase 2: Enums & Models
- [ ] Create `EducationalStage` enum
- [ ] Create `SchoolType` enum
- [ ] Create `Grade` model with relationships and scopes
- [ ] Create `School` model with relationships and scopes
- [ ] Create `College` model with relationships and scopes
- [ ] Update `User` model with grade/school/college relations

### Phase 3: Service Layer
- [ ] Create `GradeServiceInterface` and `GradeService`
- [ ] Create `SchoolServiceInterface` and `SchoolService`
- [ ] Create `CollegeServiceInterface` and `CollegeService`
- [ ] Update student query service with educational filters

### Phase 4: Admin CRUD API
- [ ] `GradeController` with CRUD endpoints
- [ ] `SchoolController` with CRUD endpoints
- [ ] `CollegeController` with CRUD endpoints
- [ ] Form requests for grades (Store, Update, List)
- [ ] Form requests for schools (Store, Update, List)
- [ ] Form requests for colleges (Store, Update, List)
- [ ] Admin resources for grades, schools, colleges
- [ ] Routes registration

### Phase 5: Lookup & Profile API
- [ ] Add `lookup` action to `GradeController`
- [ ] Add `lookup` action to `SchoolController`
- [ ] Add `lookup` action to `CollegeController`
- [ ] Create mobile lookup controllers
- [ ] Create mobile lookup resources (localized)
- [ ] Add educational filters to admin student list
- [ ] Create `UpdateEducationRequest` for mobile
- [ ] Add `updateEducation` to profile controller
- [ ] Mobile routes registration

### Phase 6: Quality & Testing
- [ ] Create `GradeFactory`
- [ ] Create `SchoolFactory`
- [ ] Create `CollegeFactory`
- [ ] Update `UserFactory` with educational fields
- [ ] Write `GradeTest` feature tests
- [ ] Write `SchoolTest` feature tests
- [ ] Write `CollegeTest` feature tests
- [ ] Write student education filter tests
- [ ] Write mobile lookup tests
- [ ] Register services in `AppServiceProvider`
- [ ] Run quality checks

---

## File Summary

### New Files (~45)

```
Migrations (4):
- database/migrations/YYYY_MM_DD_create_grades_table.php
- database/migrations/YYYY_MM_DD_create_schools_table.php
- database/migrations/YYYY_MM_DD_create_colleges_table.php
- database/migrations/YYYY_MM_DD_add_educational_fields_to_users_table.php

Enums (2):
- app/Enums/EducationalStage.php
- app/Enums/SchoolType.php

Models (3):
- app/Models/Grade.php
- app/Models/School.php
- app/Models/College.php

Services (6):
- app/Services/Education/Contracts/GradeServiceInterface.php
- app/Services/Education/GradeService.php
- app/Services/Education/Contracts/SchoolServiceInterface.php
- app/Services/Education/SchoolService.php
- app/Services/Education/Contracts/CollegeServiceInterface.php
- app/Services/Education/CollegeService.php

Controllers (6):
- app/Http/Controllers/Admin/GradeController.php
- app/Http/Controllers/Admin/SchoolController.php
- app/Http/Controllers/Admin/CollegeController.php
- app/Http/Controllers/Mobile/GradeLookupController.php
- app/Http/Controllers/Mobile/SchoolLookupController.php
- app/Http/Controllers/Mobile/CollegeLookupController.php

Form Requests (10):
- app/Http/Requests/Admin/Education/StoreGradeRequest.php
- app/Http/Requests/Admin/Education/UpdateGradeRequest.php
- app/Http/Requests/Admin/Education/ListGradesRequest.php
- app/Http/Requests/Admin/Education/StoreSchoolRequest.php
- app/Http/Requests/Admin/Education/UpdateSchoolRequest.php
- app/Http/Requests/Admin/Education/ListSchoolsRequest.php
- app/Http/Requests/Admin/Education/StoreCollegeRequest.php
- app/Http/Requests/Admin/Education/UpdateCollegeRequest.php
- app/Http/Requests/Admin/Education/ListCollegesRequest.php
- app/Http/Requests/Mobile/UpdateEducationRequest.php

Resources (6):
- app/Http/Resources/Admin/Education/GradeResource.php
- app/Http/Resources/Admin/Education/GradeLookupResource.php
- app/Http/Resources/Admin/Education/SchoolResource.php
- app/Http/Resources/Admin/Education/SchoolLookupResource.php
- app/Http/Resources/Admin/Education/CollegeResource.php
- app/Http/Resources/Admin/Education/CollegeLookupResource.php
- app/Http/Resources/Mobile/Education/GradeResource.php
- app/Http/Resources/Mobile/Education/SchoolResource.php
- app/Http/Resources/Mobile/Education/CollegeResource.php

Routes (2):
- routes/api/v1/admin/education.php
- routes/api/v1/mobile/education.php

Factories (3):
- database/factories/GradeFactory.php
- database/factories/SchoolFactory.php
- database/factories/CollegeFactory.php

Tests (5):
- tests/Feature/Admin/GradeTest.php
- tests/Feature/Admin/SchoolTest.php
- tests/Feature/Admin/CollegeTest.php
- tests/Feature/Admin/StudentEducationFilterTest.php
- tests/Feature/Mobile/EducationLookupTest.php
```

### Modified Files (3)
```
- app/Models/User.php (add grade/school/college relations)
- app/Providers/AppServiceProvider.php (register services)
- app/Http/Controllers/Mobile/ProfileController.php (add updateEducation)
```

---

## Testing Plan

```bash
# Run all education tests
php artisan test --filter="Education"
php artisan test --filter="Grade"
php artisan test --filter="School"
php artisan test --filter="College"

# With coverage
php artisan test --filter="Education" --coverage
```

### Test Scenarios

| Scenario | Type | Priority |
|----------|------|----------|
| Admin creates grade | Feature | High |
| Admin creates school with type | Feature | High |
| Admin creates college | Feature | High |
| Admin lookup with filters | Feature | High |
| Mobile lookup returns localized names | Feature | High |
| Mobile lookup returns active only | Feature | High |
| Student updates education profile | Feature | High |
| Cannot assign inactive grade | Feature | High |
| Cannot delete grade with students | Feature | Medium |
| Slug auto-generation and uniqueness | Unit | Medium |
| Stage filter returns correct grades | Feature | Medium |
| Search filter works case-insensitive | Feature | Medium |
