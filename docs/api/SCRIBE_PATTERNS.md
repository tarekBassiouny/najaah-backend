# Scribe API Documentation Patterns

> Patterns and conventions for documenting API endpoints using Scribe.

## Overview

This project uses [Scribe](https://scribe.knuckles.wtf) for API documentation. Documentation is generated from:
- FormRequest `bodyParameters()` and `queryParameters()` methods
- Route model binding auto-detection
- Scribe config strategies

**No controller annotations are used** (no `@group`, `@response`, etc.).

---

## FormRequest Structure

### Complete Template

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Always true, authorization in controller/service
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'required_field' => ['required', 'string', 'max:255'],
            'optional_field' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'required_field' => [
                'description' => 'Description ending with period.',
                'example' => 'Example value',
            ],
            'optional_field' => [
                'description' => 'Optional description.',
                'example' => 123,
            ],
        ];
    }
}
```

---

## Body Parameters (`bodyParameters()`)

Used for POST/PUT/PATCH request bodies.

### Return Type
```php
/**
 * @return array<string, array<string, mixed>>
 */
public function bodyParameters(): array
```

### Parameter Structure
```php
'field_name' => [
    'description' => 'Clear description ending with period.',
    'example' => 'value',
],
```

### Example Types

| PHP Type | Example Value | Notes |
|----------|---------------|-------|
| Integer | `123` | Use actual int, not string |
| String | `'text value'` | Use single quotes |
| Boolean | `true` / `false` | Lowercase |
| Float | `19.99` | Use actual float |
| Array | `['key' => 'value']` | Associative array |
| Enum | `'beginner'` | Document allowed values in description |

### Examples

**Integer field:**
```php
'session_id' => [
    'description' => 'Playback session identifier.',
    'example' => 123,
],
```

**String field:**
```php
'reason' => [
    'description' => 'Optional reason for the request.',
    'example' => 'Need more time to review.',
],
```

**Enum field:**
```php
'status' => [
    'description' => 'Enrollment status (ACTIVE, DEACTIVATED, or CANCELLED).',
    'example' => 'ACTIVE',
],
```

**Array field:**
```php
'metadata' => [
    'description' => 'Optional metadata object.',
    'example' => ['key' => 'value'],
],
```

---

## Query Parameters (`queryParameters()`)

Used for GET request query strings.

### Return Type
```php
/**
 * @return array<string, array<string, string>>
 */
public function queryParameters(): array
```

### Important: All examples are strings

Query parameters are always strings in HTTP, so examples should be quoted:

```php
'page' => [
    'description' => 'Page number to retrieve.',
    'example' => '1',  // String, not integer
],
'category_id' => [
    'description' => 'Filter by category ID.',
    'example' => '3',  // String, not integer
],
```

### Complete Example with Filters DTO

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use App\Filters\Mobile\CourseFilters;
use Illuminate\Foundation\Http\FormRequest;

class EnrolledCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'integer'],
            'instructor_id' => ['sometimes', 'integer'],
        ];
    }

    public function filters(): CourseFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new CourseFilters(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            instructorId: isset($data['instructor_id']) ? (int) $data['instructor_id'] : null,
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '15',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'category_id' => [
                'description' => 'Filter courses by category ID.',
                'example' => '3',
            ],
            'instructor_id' => [
                'description' => 'Filter courses by instructor ID.',
                'example' => '5',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return []; // Empty for GET requests
    }
}
```

---

## Admin Request with Validation Override

Admin requests override `failedValidation()` for consistent JSON error format:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ExtraViews;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApproveExtraViewRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'granted_views' => ['required', 'integer', 'min:1'],
            'decision_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'granted_views' => [
                'description' => 'Number of extra full plays granted.',
                'example' => 3,
            ],
            'decision_reason' => [
                'description' => 'Optional reason for approval.',
                'example' => 'Verified request validity',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
```

---

## Conventions Summary

| Aspect | Convention |
|--------|------------|
| **authorize()** | Always `return true;` |
| **Authorization** | Handled in controller or service, not FormRequest |
| **rules() PHPDoc** | `@return array<string, array<int, string>\|string>` |
| **bodyParameters() PHPDoc** | `@return array<string, array<string, mixed>>` |
| **queryParameters() PHPDoc** | `@return array<string, array<string, string>>` |
| **Description style** | Short sentence ending with period |
| **Body examples** | Use native PHP types (int, bool, array) |
| **Query examples** | Always strings (`'15'` not `15`) |
| **Empty arrays** | Always define methods, return `[]` if unused |
| **Filters DTO** | Use for complex GET queries |
| **Admin validation** | Override `failedValidation()` for JSON errors |

---

## Scribe Configuration

Key settings in `config/scribe.php`:

```php
return [
    'type' => 'laravel',  // Blade view output

    'routes' => [
        [
            'match' => ['prefixes' => ['/api/v1/*']],
            'include' => ['/api/v1/*'],
            'exclude' => ['sanctum/*'],
        ],
    ],

    'auth' => [
        'enabled' => true,
        'default' => false,  // Not auth by default
        'in' => AuthIn::BEARER->value,
        'name' => 'Authorization',
    ],

    // Custom headers for all requests
    'strategies' => [
        'headers' => [
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Locale' => '{{locale}}',
                'X-Api-Key' => '{{api_key}}',
            ]),
        ],
    ],

    'examples' => [
        'faker_seed' => 1234,  // Deterministic examples
    ],
];
```

---

## Generating Documentation

```bash
# Generate docs
./vendor/bin/sail artisan scribe:generate

# View docs (if SCRIBE_ENABLED=true in .env)
# Visit: http://localhost/docs
```

---

## File Locations

| File | Purpose |
|------|---------|
| `config/scribe.php` | Scribe configuration |
| `app/Http/Requests/Mobile/*.php` | Mobile API FormRequests |
| `app/Http/Requests/Admin/**/*.php` | Admin API FormRequests |
| `storage/app/scribe/` | Generated OpenAPI/Postman files |
| `resources/views/scribe/` | Blade templates (if customized) |
