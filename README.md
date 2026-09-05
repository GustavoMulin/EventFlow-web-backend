# EventFlow API (backend)

Headless Laravel 12 API for the EventFlow SPA (`../EventFlow-web-frontend`).
Authentication is **Sanctum bearer tokens**; Laravel Fortify provides the
underlying actions for registration, password reset, e-mail verification and
two-factor authentication, exposed as JSON under `/api`.

## Requirements

- PHP 8.3+
- Composer
- MySQL (via Laravel Sail / `compose.yaml`) or SQLite for local dev

## Setup

```bash
composer setup          # install, .env, key:generate, sqlite file, migrate
php artisan serve        # http://localhost:8000
# or: ./vendor/bin/sail up
```

Set `FRONTEND_URL` in `.env` (default `http://localhost:5173`) — it drives CORS
and the password-reset / e-mail-verification links.

## Quality gate

```bash
composer ci:check        # pint --test + phpstan (level 7) + phpunit
```

## API

All routes are prefixed with `/api`. Send `Accept: application/json`. Protected
routes require `Authorization: Bearer <token>`.

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/register` | – | Create account → `{ token, user }` |
| POST | `/login` | – | `{ token, user }`, or `{ two_factor: true }` when a 2FA code is required (resend with `code` or `recovery_code`) |
| POST | `/logout` | token | Revoke the current token |
| GET | `/user` | token | `{ user, two_factor_enabled, email_verified }` |
| POST | `/forgot-password` | – | E-mail a reset link (points at the SPA) |
| POST | `/reset-password` | – | `token`, `email`, `password`, `password_confirmation` |
| GET | `/email/verify/{id}/{hash}` | signed | Verifies, then redirects to `FRONTEND_URL/verify-email?status=…` |
| POST | `/email/verification-notification` | token | Resend the verification e-mail |
| PATCH | `/user/profile` | token | Update `name` / `email` |
| PUT | `/user/password` | token | `current_password` + new `password`; revokes tokens and returns a fresh `{ token }` |
| DELETE | `/user` | token | Delete account (requires `password`) |
| POST | `/user/two-factor` | token | Begin 2FA setup → `{ svg, secret_key, recovery_codes }` |
| POST | `/user/two-factor/confirm` | token | Confirm with `code` |
| DELETE | `/user/two-factor` | token | Disable 2FA |
| GET | `/user/two-factor/qr-code` \| `/secret-key` \| `/recovery-codes` | token | 2FA details |
| POST | `/user/two-factor/recovery-codes` | token | Regenerate recovery codes |

Verification and password-reset e-mails are **queued** — run `php artisan
queue:work` (or use the `sync` queue driver) for them to actually send.
