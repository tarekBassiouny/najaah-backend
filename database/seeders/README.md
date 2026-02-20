# Seeder Modes

## Default (clean baseline)

The default `DatabaseSeeder` now seeds only baseline data:

- roles
- permissions
- role-permission mapping
- one system admin user
- system settings

Run:

```bash
php artisan migrate:fresh --seed
```

## Optional demo/sample data

If you want noisy sample data for manual testing, enable:

```bash
SEED_DEMO_DATA=true php artisan migrate:fresh --seed
```

This additionally runs `DemoDataSeeder` and creates sample centers, users, courses, sections, videos, enrollments, devices, logs, and OTP/JWT records.

## Optional seeded admin credentials

You can override seeded baseline admin values:

- `SEED_ADMIN_EMAIL`
- `SEED_ADMIN_PASSWORD`
- `SEED_ADMIN_PHONE`
- `SEED_ADMIN_COUNTRY_CODE`
