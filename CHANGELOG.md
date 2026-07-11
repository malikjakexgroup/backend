# Changelog

All notable changes to this project are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/).

## [0.1.0] - 2026-07-11

### Added
- REST API under `/wp-json/books/v1/`: `search`, `book/{id}`, `categories`.
- Token-based auth: `register`, `login`, `me`.
- `book` Custom Post Type storage (wp-admin panel for free).
- Google Books integration via `wp_remote_get`.
- **Permanent store-first** search: saved books are served forever; Google is queried
  only for brand-new searches. Resilient to Google rate limits/outages.
- Transient cache helpers and self-test for the normalizer.
