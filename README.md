# backend — WordPress (the API)

> 🔌 The **backend** of the Book Platform — a WordPress plugin that *is* the API. Serves the **[frontend](https://github.com/malikjakexgroup/frontend)**.
> Repos: [frontend](https://github.com/malikjakexgroup/frontend) · **backend** · [api](https://github.com/malikjakexgroup/api)

[![CI](https://github.com/malikjakexgroup/backend/actions/workflows/ci.yml/badge.svg)](https://github.com/malikjakexgroup/backend/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](composer.json)

The entire backend: a WordPress plugin that proxies Google Books. WordPress supplies
the pieces the old multi-service design needed separately:

| Concern | WordPress-native |
|---------|------------------|
| REST API | `register_rest_route` → `/wp-json/books/v1/*` |
| Cache (was Redis) | Transients API (24h TTL; Redis-backed if an object cache is installed) |
| Storage (was Postgres) | `book` Custom Post Type in MySQL |
| Admin panel (was book-admin) | **wp-admin → Books** (free listing/edit/delete) |

## REST endpoints
- `GET /wp-json/books/v1/search?q=`
- `GET /wp-json/books/v1/book/{google_id}`
- `GET /wp-json/books/v1/categories`

### Auth (token-based, no dependency)
- `POST /wp-json/books/v1/register` — `{name, email, password}` → `{token, user}` (creates a WordPress user).
- `POST /wp-json/books/v1/login` — `{email, password}` → `{token, user}`.
- `GET  /wp-json/books/v1/me` — send `Authorization: Bearer <token>` → the current user, or `401`.

A random token is issued on register/login; its SHA-256 hash is stored in user meta.
The frontend saves the token and sends it as a Bearer header.

## Flow
1. Transient hit → return.
2. Miss → Google Books (`wp_remote_get`) → upsert into the `book` CPT → cache 24h → return.
3. Google down and nothing cached → **503** (never fabricated). A stale CPT row is
   served if it exists.

## Files
```
book-backend.php          # plugin header, wiring, dev CORS
includes/
  google-books.php        # wp_remote_get + normalize()
  store.php               # book CPT + upsert + freshness
  cache.php               # transient helpers
  rest.php                # route registration + handlers
bb-selftest.php           # standalone: php bb-selftest.php -> "normalize() ok"
```

## Install
Via docker-compose from the repo root (mounts this folder as a plugin), or copy this
folder into `wp-content/plugins/` and activate **Book Backend** in wp-admin.

Optional Google API key: `define('BB_GOOGLE_API_KEY', '...');` in `wp-config.php`.

## Deferred (v1)
Search logging table, auth/API keys/rate limiting. wp-admin already covers browsing
cached books, so a separate admin app isn't needed.
