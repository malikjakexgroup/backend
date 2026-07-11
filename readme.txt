=== Book Backend ===
Contributors: mohammadmalik
Tags: books, rest-api, google-books
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Google Books proxy for WordPress: REST API, permanent Custom Post Type storage,
token auth, and a wp-admin panel.

== Description ==

Book Backend turns WordPress into the backend for a book discovery app. It exposes a
small public REST API under `/wp-json/books/v1/`, fetches from Google Books, and
stores every result permanently as a `book` Custom Post Type so repeat lookups never
depend on the external API.

Endpoints:

* `GET /wp-json/books/v1/search?q=` — store-first search (Google only for new queries)
* `GET /wp-json/books/v1/book/{id}` — a stored book, or fetched once then stored
* `GET /wp-json/books/v1/categories`
* `POST /wp-json/books/v1/register` — create a user, returns a token
* `POST /wp-json/books/v1/login` — returns a token
* `GET  /wp-json/books/v1/me` — current user (Bearer token)

== Installation ==

1. Copy this folder to `wp-content/plugins/book-backend`.
2. Activate **Book Backend** in wp-admin → Plugins.
3. (Optional) Add `define('BB_GOOGLE_API_KEY', 'your-key');` to `wp-config.php`.

== Changelog ==

= 0.1.0 =
* Initial release: search, book, categories, auth, permanent store-first storage.
