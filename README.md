# Laravel Pretty Logger

A [`pretty_dio_logger`](https://pub.dev/packages/pretty_dio_logger)-style HTTP request/response logger for **Laravel**. Every request prints a clean, colored, boxed summary to your terminal (and/or a readable log file) — with passwords, OTPs, tokens and cookies automatically masked.

- Package: `farhanchoksi/laravel-pretty-logger`
- Namespace: `Farhanchoksi\PrettyLogger`
- Config file: `config/pretty-logger.php`

```
┌───────────────────────────────────────────────
│ REQUEST  POST  http://localhost:8000/login
├───────────────────────────────────────────────
│ body: {"phone":"9876543210","password":"******"}
│ headers: content-type: application/x-www-form-urlencoded
└───────────────────────────────────────────────
┌───────────────────────────────────────────────
│ RESPONSE  200  POST  http://localhost:8000/login
├───────────────────────────────────────────────
│ duration: 22ms
│ headers: content-type: text/html; charset=utf-8
│ set-cookie: <redacted>
│ body: <html document, 5,855 bytes — body hidden>
└───────────────────────────────────────────────
```

## Install

```bash
composer require farhanchoksi/laravel-pretty-logger
```

The service provider is auto-discovered — no registration needed. The logger activates automatically when `APP_DEBUG=true`.

### Optional: publish the config

```bash
php artisan vendor:publish --tag=pretty-logger-config
```

This creates `config/pretty-logger.php` so you can customize everything.

## Configuration (`.env`)

| Key | Default | Meaning |
|-----|---------|---------|
| `PRETTY_LOGGER` | *(null = follow `APP_DEBUG`)* | Force the logger `true`/`false` regardless of debug mode. |
| `PRETTY_LOGGER_TERMINAL` | `true` | Stream colored boxes to `php://stderr` (terminal). |
| `PRETTY_LOGGER_FILE` | `true` | Also append to `storage/logs/http.log`. |
| `PRETTY_LOGGER_HTML_BODY` | `false` | Print HTML response bodies (truncated). Off = just byte size. |
| `PRETTY_LOGGER_MAX_BODY` | `600` | Max characters of a JSON/text body to print. |

> **Tip:** to keep logging on in production **without** turning `APP_DEBUG` on, set `APP_DEBUG=false` and `PRETTY_LOGGER=true`.

## Security

The logger masks sensitive data by default (configurable in `config/pretty-logger.php`):
- **Body/query keys** → `password`, `password_confirmation`, `token`, `secret`, `otp`, `api_key`, `access_token`, `refresh_token` → `******`
- **Headers** → `cookie`, `set-cookie`, `authorization`, `x-csrf-token`, `x-xsrf-token` → `<redacted>`
- **URLs** → any masked key appearing as a query param is redacted too
- HTML/binary response bodies are summarized, not dumped

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12 or 13

## License

MIT
