# Schema Patterns

## Table Baseline
```php
$table->id();
$table->timestamps();
$table->softDeletes();
```

## Foreign Keys
```php
$table->foreignId('center_id')
    ->constrained('centers')
    ->cascadeOnUpdate()
    ->cascadeOnDelete();
```

## Status Fields
```php
$table->tinyInteger('status')->default(0);
```

Keep database status storage integer-based and define named constants in code.

## Common Indexes
- all foreign keys
- `deleted_at`
- status columns used in lists or cleanup jobs
- composite indexes for user and content lookups
- expiry columns used by cleanup or token validation

## Relationship Patterns
- Use typed Laravel relations in models
- For pivot models, add casts for pivot attributes and soft deletes when the pivot is stateful
- Keep multi-tenant linkage explicit in both parent and child models

## JSON and Translation Fields
- Cast JSON columns to arrays
- Use translation maps where the model already follows that pattern
- Keep locale resolution outside the migration and resource hardcoding

## Checklist
- migration matches naming conventions
- relations and casts updated
- indexes support the expected queries
- factories and tests updated if schema changed
