# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Simple Wishlist is a Laravel 12 SaaS application where users can create and manage wishlists with privacy controls, OAuth authentication, multi-currency support, and AI-powered wish description generation.

## Development Commands

### PHP / Laravel
```bash
php artisan serve          # Start local dev server
php artisan migrate        # Run database migrations
php artisan tinker         # Laravel REPL
```

### Frontend
```bash
npm run dev    # Start Vite dev server (hot reload)
npm run build  # Production asset build
```

### Testing
```bash
vendor/bin/phpunit                          # Run all tests
vendor/bin/phpunit tests/Unit/              # Run unit tests only
vendor/bin/phpunit tests/Feature/           # Run feature tests only
vendor/bin/phpunit --filter TestClassName   # Run a single test class
```

### Code Quality (run before committing)
```bash
vendor/bin/pint             # Laravel Pint code formatter
vendor/bin/phpstan analyse  # PHPStan static analysis
```

### Docker
```bash
docker-compose up -d  # Start full development environment
```

## Architecture

### Key Layers

- **Controllers** (`app/Http/Controllers/`) — thin controllers that delegate to services
- **Services** (`app/Services/`) — business logic lives here; injected into controllers
- **Repositories** (`app/Repositories/`) — data access abstraction over Eloquent
- **Models** (`app/Models/`) — `User`, `Wish`, `Wishlist`, `Currency`, `UserAttributes`

### Core Models

- **Wishlist** — belongs to User; has slug and `is_private` flag; default slug is `my-wishlist`
- **Wish** — belongs to Wishlist; uses slug as route key; supports `is_completed`, currency, URL, and optionally a local image file
- **UserAttributes** — one-to-one extension of User for extra profile data

### Authentication

Uses Laravel Breeze for email/password plus Socialite for OAuth:
- Google (`GoogleController`), GitHub (`GithubController`), Telegram (`TelegramController`)
- User email can be null (for OAuth-only users — migration `2024_01_13_162021`)

### Services of Note

- `GptService` — calls OpenAI API to generate wish descriptions (`/generate-description` route)
- `WishService` — handles wish creation logic including image downloading and slug generation
- `CurrencyService` — provides currency data to the frontend

### Routes

Web routes are split across:
- `routes/web.php` — main app routes (public wishlist views + authenticated user routes)
- `routes/auth.php` — Breeze auth routes
- `routes/api.php` — minimal, mostly unused

Public routes do not require auth. Authenticated routes use `auth` + `verified` middleware.

### Database

- **Development**: MySQL (configured in `.env`)
- **Testing**: SQLite in-memory (configured in `phpunit.xml`)
- **Production**: SQLite at `/var/www/html/storage/database/database.sqlite`

### Frontend Stack

Blade templates + Tailwind CSS 3 + Alpine.js + Flowbite components, bundled with Vite.

## CI/CD

GitHub Actions (`.github/workflows/simplewl.yml`) runs on every push/PR to `main`:
1. **Test job**: Pint format check → PHPStan → PHPUnit (SQLite in-memory)
2. **Deploy job** (main branch only, after tests pass): builds and pushes Docker image, deploys to server

## Mail

Supports MailerSend and Mailgun (configured via env vars). Local dev uses Mailpit over SMTP.
