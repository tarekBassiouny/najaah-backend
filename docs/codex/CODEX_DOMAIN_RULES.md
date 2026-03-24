# Najaah LMS — Domain Rules

Codex must enforce the following rules across the entire repo.

---

# 1. MODELS

### Required on ALL models:
- `use HasFactory;`
- `use SoftDeletes;`
- Typed `$fillable`
- Typed `$casts`
- JSON translation fields:
  - `title_translations`
  - `description_translations`
- Strict return types
- Full generic relation types:
  - `HasMany<Model, ThisModel>`
  - `BelongsTo<Model, ThisModel>`
  - `BelongsToMany<Model, ThisModel>`

No dynamic properties allowed.

---

# 2. MIGRATIONS

- Use `string()` for tokens to allow indexing
- Always include:
  - `$table->softDeletes();`
  - `$table->timestamps();`
- All foreign keys must:
  - cascadeOnDelete()
  - cascadeOnUpdate()

---

# 3. FACTORIES

- Must never produce duplicate unique fields  
  (e.g., course_code, emails)
- Must generate full JSON objects for translation fields
- Must not use `$this->faker->optional()->...`
- Must always generate valid, consistent data
- Relationships must use:
  - `Model::factory()`

---

# 4. SEEDERS

- Use collections with →each()
- Never produce duplicate unique values
- Make sure seed counts:
  - Users: 500
  - Centers: 20
  - Videos: 1000
  - PDFs: 800
  - Playback Sessions: 2000
  - Courses: 200
  - Sections: 300
  - Categories: 50

---

# 5. CONTROLLERS

- No `app()` calls
- Use constructor injection only
- Must be thin:  
  Only:
  - Validating using Form Requests  
  - Calling Services  
  - Returning Resources

No business logic in controllers.

---

# 6. SERVICES

All services must:

- Have an interface  
  in `/app/Services/Contracts`
- Use constructor injection  
- Have strict return types  
- Have no mixed inputs  
- Throw custom exceptions  
- Have unit tests

Services required:

- `OtpServiceInterface`
- `JwtServiceInterface`
- `DeviceServiceInterface`

---

# 7. RESOURCES

All resources must:
- Add `@property` PHPDoc
- Accept typed models
- Never access dynamic properties
- Always return a typed array

---

# 8. MIDDLEWARE

- Must be fully typed
- Must return `Illuminate\Http\Response|JsonResponse`

---

# 9. TESTS

Codex must generate:

### Feature Tests:
- send OTP
- verify OTP
- login
- device register
- jwt refresh
- jwt invalid token
- jwt expired
- otp invalid
- admin login
- admin protected routes

### Unit Tests:
- OtpServiceTest
- JwtServiceTest
- DeviceServiceTest

---

# 9B. PARENT-STUDENT RELATIONSHIPS

- Parents are `User` records with `is_parent = true` (same table as students).
- A user can be both student and parent simultaneously (`is_student = true` AND `is_parent = true`).
- Links are stored in `parent_student_links` table, scoped by `center_id`.
- Link statuses use `ParentLinkStatus` enum: `Active` (0), `PendingApproval` (1), `Revoked` (2).
- Link methods use `ParentLinkMethod` enum: `AdminManaged` (0), `AutoMatched` (1), `ParentRequested` (2).
- Auto-match runs on parent registration: links parent to students whose `parent_phone` matches.
- Parents access student data only through `Active` links — `PendingApproval` and `Revoked` are blocked.
- Parent web auth uses guard `jwt.web.parent` with `TokenPlatform::Web`.
- Parents have no device binding (no device limit enforcement).

---

# 9C. WEB PORTAL

- Web student auth guard: `jwt.web.student`.
- Web parent auth guard: `jwt.web.parent`.
- Web devices use `DeviceType::Web` enum value, separate pool from `DeviceType::Mobile`.
- `TokenPlatform` enum: `Mobile` (0), `Web` (1) — tracked on JWT tokens.
- Center feature flags control access: `features.web_access`, `features.web_playback`, `features.parent_portal`.
- Center settings: `allow_web_access`, `allow_web_playback`, `allow_parent_portal`, `web_device_limit`.
- Feature flags **override** center settings via governance layer — if `features.web_access` is `false`, web access is disabled even if `allow_web_access` is `true`.
- Web student routes mirror mobile routes under `/api/v1/web/` prefix, reusing the same controllers.
- Parent routes are at `/api/v1/web/students`, `/api/v1/web/links`, etc.

---

# 10. QA

Codex must ensure:

### Pint  
`./vendor/bin/sail pint --test` → 0 issues

### PHPStan  
`phpstan analyse` → 0 errors

### Tests  
`php artisan test` → green

---

Codex must adhere strictly to all rules above during the refactor.
