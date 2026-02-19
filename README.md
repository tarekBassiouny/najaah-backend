<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Mail Delivery and Queue Workers

This project sends admin invitation/reset emails asynchronously through queued jobs.

- Set `MAIL_MAILER=mailgun`
- Set `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT` (`api.eu.mailgun.net` for EU domains)
- Set `MAIL_QUEUE_CONNECTION=database` (or `redis`) and `MAIL_QUEUE=mail`

Run queue workers in production so emails are processed after API responses:

```bash
php artisan queue:work --queue=mail,default --tries=3 --timeout=30
```

After deployment:

```bash
php artisan queue:restart
```

## Production Debugging (No Extra Cost)

Use Laravel daily file logs + queue failed jobs for production observability without paid tools.

### 1) Environment

Set in production `.env`:

```bash
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=30
LOG_REQUESTS_ENABLED=true
LOG_REQUESTS_CHANNEL=requests
LOG_REQUESTS_LEVEL=info
LOG_REQUESTS_DAYS=30
LOG_REQUESTS_SLOW_MS=1500
LOG_REQUESTS_EXCLUDE_PATHS=up,health
LOG_JOBS_ENABLED=true
LOG_JOBS_CHANNEL=jobs
LOG_JOBS_LEVEL=info
LOG_JOBS_DAYS=30
QUEUE_FAILED_DRIVER=database-uuids
```

### 2) Forge deploy safety

- Keep `storage` shared across releases (`storage/logs` must be persistent).
- Do not remove `storage/logs` in deploy script.
- After every deploy:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan queue:restart
```

### 3) Queue worker

Run worker as a Forge background process:

```bash
php8.4 /home/forge/<site>/current/artisan queue:work database --queue=mail,default --sleep=3 --tries=3 --timeout=60 --daemon --quiet
```

### 4) Debug commands

```bash
php artisan queue:failed
php artisan queue:retry all
tail -f storage/logs/laravel-*.log
tail -f storage/logs/requests-*.log
tail -f storage/logs/jobs-*.log
```

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
