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
