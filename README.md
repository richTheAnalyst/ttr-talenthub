# TTR TalentHub (APP-02)

Mobile-first PWA career hub on Laravel 12: candidate profiles and CV uploads, job search with match scores,
application tracking, job alerts, employer hiring requests, study-abroad lead capture, CRM webhook + queues, admin dashboard, REST API (Sanctum).

## Quick start (PHP 8.2+, Composer, Node 18+, PostgreSQL or SQLite)
```bash
composer setup            # install, .env, key, migrate + seed, storage link, npm install + build CSS
php artisan serve         # app
php artisan queue:work    # alerts, notifications, CRM sync
php artisan schedule:work # daily digest, stale-lead reminders
php artisan test
```
Edit `.env` first for your database (Neon: set `DB_URL`). For a quick local trial use `DB_CONNECTION=sqlite`.

Seeded logins (non-production): admin@ttr.test / ChangeMe!2026, employer@demo.test and candidate@demo.test / Password123.

## Status of this delivery (Part 1)
Written but **not yet executed** (the build sandbox blocked Packagist and npm): run `composer setup` and `php artisan test` and send me any failures.
Verified here: PHP lint on every file, route/view/component cross-check, JS/manifest validation.

Done: backend (models, services, matching, queues, notifications, policies, API v1), migrations, seeders, 52 Blade views,
Tailwind config, PWA (manifest, service worker, offline page, icons), unit + feature tests.
Remaining ideas: browser push notifications, email verification, admin job moderation, API docs, CI.
