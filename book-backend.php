<?php
/**
 * Plugin Name: Book Backend
 * Description: Google Books proxy — REST API (/wp-json/books/v1/*), Custom Post Type storage, Transient cache.
 * Version:     0.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BB_CACHE_TTL', 24 * HOUR_IN_SECONDS);
// Optional: put `define('BB_GOOGLE_API_KEY', '...');` in wp-config.php to raise Google's rate limit.

require_once __DIR__ . '/includes/google-books.php';
require_once __DIR__ . '/includes/store.php';
require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/auth.php';

add_action('init', 'bb_register_book_cpt');
add_action('rest_api_init', 'bb_register_routes');
add_action('rest_api_init', 'bb_register_auth_routes');

// Dev CORS: the React app runs on a different origin (e.g. :5173) and must be able
// to call the REST API. ponytail: allow-all is fine for local dev — tighten before deploy.
add_action('rest_api_init', function () {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($served) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        return $served;
    });
}, 15);
