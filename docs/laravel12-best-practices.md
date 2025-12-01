Laravel 12 + PHP 8.4 Best Practices (AI-Ready Guidelines & Full Test Coverage)

Version 2025 – Comprehensive Engineering Standards

Table of Contents

PHP 8.4 Best Practices

Laravel 12 Best Practices

Laravel 12 New Capabilities

AI-Assisted Development & Code Generation

Recommended Project Structure

Security & Compliance

Deployment Best Practices

Logging & Observability

Coding Standards

Unit & Integration Testing (Full Coverage)

Summary Checklist

1. PHP 4 Best Practices
1.1 Core Language Modernization

Strict typing in all PHP files:

declare(strict_types=1);


Typed properties, union types, intersection types, and never return type.

Use readonly properties for immutability.

Final classes for non-extendable logic.

Prefer attributes instead of docblock annotations.

Use enums instead of constant-only classes.

Use promoted constructor parameters in small DTO classes.

1.2 Performance

Prefer match over long switch chains.

Use generators for memory-friendly large data processing.

Use array_is_list() where applicable.

Turn on OPcache + JIT for production.

Use static analysis (PHPStan Level 8 / Psalm max level).

Avoid unnecessary copying of arrays; use references when appropriate.

1.3 Security

Use Sodium or OpenSSL for crypto operations.

Avoid dynamic code execution.

Use filter_var() and strict type casting when handling external input.

Always sanitize environment variables.

2. Laravel 12 Best Practices
2.1 Architecture

Follow Service Layer Pattern for all business rules.

Keep controllers thin:

Transform requests → forward to Service/Action → return response.

Use Actions for single use-case operations.

Use FormRequest classes for validation and authorization.

Only use repositories when needed (e.g., complex datasource abstraction).

2.2 Blade & Frontend

Use Blade Components (with typed attributes).

Use Tailwind CSS + Vite.

Extract JS logic into modules under resources/js/modules.

Use Livewire v4 or Inertia when dynamic UI is required.

2.3 Eloquent & Database

Prefer ULIDs for IDs (Laravel 12 native support):

$table->ulid('id')->primary();


Use lazy() and cursor() for large result sets.

Use model casting for clean attribute transformations.

Enforce constraints in migrations:

foreign keys

unique indexes

composite indexes

Avoid N+1 queries — enforce load() and with().

2.4 API Development

Always version APIs (e.g., /api/v1/*).

Use API Resources for formatting.

Include pagination links + meta.

Enforce consistent field naming (snake_case for DB, camelCase for JSON).

2.5 Caching & Performance

Use Cache Tags, Atomic Locks, Rate Limiting.

Cache heavy calculations:

Cache::remember('key', now()->addMinutes(5), fn() => ...);


Use database caching strategy (cache invalidation rules defined per model).

2.6 Queues & Jobs

All long-running tasks must be queued.

Jobs should only pass IDs or simple data structures.

Use dedicated queues for heavy workloads.

Use retry strategies and failure logging.

2.7 Testing

Use Pest for a cleaner syntax.

Write both unit + integration tests for each module.

Use factories everywhere.

3. Laravel 12 New Features
3.1 Auto-Discovery Improvements

Faster and safer class discovery.

Lightweight service bootstrapping.

3.2 ULID Support

Native ULID handling in:

Models

Migrations

Factories

3.3 Batching Improvements

Better job batch tracking.

Batch cancellation support.

3.4 ORM Enhancements

More powerful scope chaining.

Inline casting improvements.

Improved map relationships for pivot tables.

3.5 CLI Improvements

Faster optimize, cache, config commands.

New debugging utilities.

4. AI-Assisted Development Guidelines
4.1 AI Code Generation

When asking AI to generate code:

Require PHP 8.4 strict typing

PSR-12 formatting

Laravel 12 conventions

Service layer architecture

No business logic inside controllers

Use DTOs for structured data

Enforce dependency injection everywhere

4.2 Validation of AI Output

Always:

Run Laravel Pint

Run PHPStan

Run Pest tests

Review for:

hidden mass assignment

missing validation

direct DB queries in controllers

weak typing

security issues

4.3 AI Documentation Generation

Generated documentation must:

Use Markdown

Contain headings, code blocks, lists

Avoid redundant explanations

Provide diagrams where helpful

5. Recommended Project Structure
app/
  Actions/
  DTOs/
  Services/
  Enums/
  Http/
    Controllers/
    Requests/
    Resources/
database/
  migrations/
  seeders/
resources/
  views/components/
  js/modules/
routes/
  api.php
  web.php
tests/
  Unit/
  Feature/
  Integration/

6. Security & Compliance
6.1 Environment & Secrets

Never commit .env.

Use AWS/GCP Secrets Manager.

Rotate keys periodically.

6.2 Authentication

Use Laravel Fortify or Breeze.

Enable MFA where possible.

Rotate sessions after login.

6.3 API Security

Use OAuth2 or token-based authentication.

Use strict rate limiting.

Allowlist production callbacks.

7. Deployment Best Practices
7.1 CI/CD Pipeline Steps

Composer install (no-dev in production)

NPM build (Vite)

Pint (lint)

PHPStan

Unit tests

Integration tests

Package artifacts

Deploy via SSH/Pipeline Runner

Run migrations

Queue restart

7.2 Server Setup

PHP 8.4 FPM

MySQL 8.4 or PostgreSQL 16

Nginx HTTP/3

Redis for queues/cache

7.3 Zero-Downtime Deployment

Use Envoy, Vapor, or Deployer.

Blue-Green Deployment strategy recommended.

8. Logging & Observability
8.1 Logging

Use JSON logs.

Use Monolog channels.

Structure logs with request ID correlation.

8.2 Observability

Monitor:

Queue throughput

Error frequencies

DB slow logs

Memory usage

Response latency

Tools:

Telescope (local only)

Sentry / Bugsnag

Prometheus + Grafana

9. Coding Standards

PSR-12

Laravel Pint (strict mode)

Avoid unused imports

Only document complex logic

Single-responsibility principle per class

10. Unit & Integration Testing (Full Coverage)
10.1 Test Strategy

Each class must have:

Unit test

Integration test (if touching DB, queue, filesystem)

Coverage for:

success cases

invalid cases

boundary cases

exceptions

10.2 Unit Tests

Use mocks for dependencies:

it('calculates taxed amount', function () {
    $taxRepo = mock(TaxRepository::class)
       ->shouldReceive('rate')
       ->andReturn(0.20)
       ->getMock();

    $service = new PriceService($taxRepo);

    expect($service->calculate(100))->toBe(120);
});

10.3 Integration Tests

Use real database, real files:

it('imports CSV into the database', function () {
    Storage::fake('local');

    Storage::put('import.csv', "date,amount,type\n2025-01-01,100,income");

    $service = app(TransactionImport::class);
    $service->import(storage_path('app/import.csv'));

    expect(Transaction::count())->toBe(1);
});

10.4 Feature (HTTP) Tests
it('returns paginated results', function () {
    Transaction::factory()->count(10)->create();

    $this->getJson('/api/v1/transactions')
         ->assertOk()
         ->assertJsonStructure([
             'data',
             'meta' => ['current_page', 'total']
         ]);
});

10.5 Event & Queue Testing
Event::fake();
event(new UserRegistered($user));
Event::assertDispatched(UserRegistered::class);

Queue::fake();
dispatch(new ProcessOrderJob($id));
Queue::assertPushed(ProcessOrderJob::class);

10.6 Database Constraint Testing
expect(Schema::hasColumns('transactions', [
    'id', 'amount', 'type'
]))->toBeTrue();

10.7 API Contract Testing

Guarantees consistent response shapes:

$response->assertJson(fn ($json) =>
    $json->hasAll(['data.id', 'data.amount', 'data.created_at'])
);

10.8 Coverage Requirements

90% minimum project-wide

100% for:

Authentication

Money calculations

Imports/exports

Search sync

Queue jobs

Command:

php artisan test --coverage --min=90

10.9 AI-Assisted Test Generation Rules

When generating tests with AI:

Use Pest

Unit tests must use mocks

Integration tests must use real DB

Include success, failure, edge cases

Include data providers

Validate JSON structure

Validate DB writes

Validate queue + event outputs

Validate exceptions

11. Summary Checklist
Area	Status
Strict PHP 8.4 typing	✔️
Laravel 12 architecture	✔️
Strong DB conventions	✔️
Queues + caching	✔️
API versioning	✔️
AI rules for coding	✔️
Unit test coverage	✔️
Integration test coverage	✔️
Event/Queue tests	✔️
API contract tests	✔️
CI/CD coverage enforcement	✔️