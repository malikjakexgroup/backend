<?php

if (!defined('ABSPATH')) {
    exit;
}

function bb_register_routes(): void
{
    register_rest_route('books/v1', '/search', array(
        'methods'             => 'GET',
        'callback'            => 'bb_rest_search',
        'permission_callback' => '__return_true',
        'args'                => array('q' => array('required' => true)),
    ));
    register_rest_route('books/v1', '/book/(?P<id>[^/]+)', array(
        'methods'             => 'GET',
        'callback'            => 'bb_rest_get_book',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('books/v1', '/categories', array(
        'methods'             => 'GET',
        'callback'            => 'bb_rest_categories',
        'permission_callback' => '__return_true',
    ));
}

/**
 * PERMANENT store first: if WordPress already has matching books, serve them forever
 * (no Google call, no expiry). Only brand-new queries go to Google, then are stored.
 */
function bb_rest_search(WP_REST_Request $req)
{
    $q = trim((string) $req->get_param('q'));
    if ($q === '') {
        return new WP_Error('bad_request', 'q required', array('status' => 400));
    }

    // 1) Already stored in WordPress? Serve it permanently.
    $stored = bb_search_stored($q);
    if ($stored) {
        return rest_ensure_response($stored);
    }

    // 2) New query -> fetch from Google, store permanently, return.
    $items = bb_google_search($q);
    if ($items === null) {
        return new WP_Error('unavailable', 'Google Books unavailable and nothing stored yet', array('status' => 503));
    }
    $out = array();
    foreach ($items as $item) {
        $out[] = bb_book_to_array(bb_upsert_book($item));
    }
    return rest_ensure_response($out);
}

/**
 * PERMANENT store first: a book already saved in WordPress is served forever.
 * Only fetch from Google the first time (when it isn't stored yet).
 */
function bb_rest_get_book(WP_REST_Request $req)
{
    $id = (string) $req['id'];

    $post_id = bb_find_book_post($id);
    if ($post_id) {
        return rest_ensure_response(bb_book_to_array($post_id)); // permanent
    }

    $data = bb_google_get($id);
    if ($data === null) {
        return new WP_Error('unavailable', 'Google Books unavailable', array('status' => 503));
    }
    if ($data === 'not_found') {
        return new WP_Error('not_found', 'Book not found', array('status' => 404));
    }
    return rest_ensure_response(bb_book_to_array(bb_upsert_book($data)));
}

function bb_rest_categories()
{
    return rest_ensure_response(array(
        'Fiction', 'Non-Fiction', 'Science', 'History', 'Biography',
        'Technology', 'Business', 'Self-Help', 'Children', 'Poetry',
    ));
}
