# EventFlow API (backend)

Headless Laravel 12 API for the EventFlow SPA (`../EventFlow-web-frontend`).
Authentication is **Laravel Sanctum bearer tokens** (no session/cookies).

## Requirements

- PHP 8.3+
- Composer
- MySQL (via Laravel Sail / `compose.yaml`) or SQLite for local dev

## Setup

```bash
composer setup                 # install, .env, key:generate, sqlite file, migrate
php artisan serve              # http://localhost:8000
# or with Docker:
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

Set `FRONTEND_URL` in `.env` (default `http://localhost:5173`) — it whitelists
the SPA origin for CORS (`config/cors.php`).

## Quality gate

```bash
composer ci:check              # pint --test + phpstan (level 7) + phpunit
```

## API

All routes are prefixed with `/api`. Send `Accept: application/json`.
Protected routes require `Authorization: Bearer <token>`.

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/register` | – | Create account → `{ token, user }` |
| POST | `/login` | – | Authenticate → `{ token, user }` |
| POST | `/logout` | token | Revoke the current token |
| GET | `/user` | token | The authenticated user |
| PATCH | `/user/profile` | token | Update `name` / `email` |

## Architecture (MVC)

- **Model** — `app/Models/*` (Eloquent) + `database/migrations` + `database/factories`.
- **Controller** — `app/Http/Controllers/Api/*` + `app/Http/Requests/*` (validation) + `routes/api.php`.
- **View** — served by the separate Vue SPA; this repo returns JSON only.
