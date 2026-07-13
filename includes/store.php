<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Books are a Custom Post Type so wp-admin gives us the admin panel for free. */
function bb_register_book_cpt(): void
{
    register_post_type('book', array(
        'labels'       => array('name' => 'Books', 'singular_name' => 'Book'),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-book',
        'supports'     => array('title', 'editor'),
    ));
}

function bb_find_book_post(string $google_id): int
{
    $ids = get_posts(array(
        'post_type'   => 'book',
        'post_status' => 'any',
        'numberposts' => 1,
        'fields'      => 'ids',
        'meta_key'    => 'google_id',
        'meta_value'  => $google_id,
    ));
    return $ids ? (int) $ids[0] : 0;
}

/** Insert or update the `book` post for this Google id; returns the post id. */
function bb_upsert_book(array $data): int
{
    $post_id = wp_insert_post(array(
        'ID'           => bb_find_book_post($data['google_id']),
        'post_type'    => 'book',
        'post_status'  => 'publish',
        'post_title'   => $data['title'] ?: 'Untitled',
        'post_content' => $data['description'] ?: '',
    ));

    update_post_meta($post_id, 'google_id', $data['google_id']);
    update_post_meta($post_id, 'authors', $data['authors']);
    update_post_meta($post_id, 'publisher', $data['publisher']);
    update_post_meta($post_id, 'thumbnail', $data['thumbnail']);
    update_post_meta($post_id, 'language', $data['language']);
    update_post_meta($post_id, 'pages', $data['pages']);
    update_post_meta($post_id, 'categories', $data['categories']);
    update_post_meta($post_id, 'rating', $data['rating']);
    update_post_meta($post_id, 'preview_link', $data['preview_link'] ?? '');
    update_post_meta($post_id, 'pdf_link', $data['pdf_link'] ?? '');
    update_post_meta($post_id, 'cached_at', time());

    return $post_id;
}

/** Match stored books by title/content — used as a fallback when Google is unavailable. */
function bb_search_stored(string $q): array
{
    $query = new WP_Query(array(
        'post_type'      => 'book',
        'post_status'    => 'publish',
        's'              => $q,
        'posts_per_page' => 20,
        'fields'         => 'ids',
    ));
    return array_map('bb_book_to_array', $query->posts);
}

function bb_is_fresh(int $post_id): bool
{
    $cached = (int) get_post_meta($post_id, 'cached_at', true);
    return $cached && (time() - $cached) < BB_CACHE_TTL;
}

/** Build the public JSON shape from a stored book post. */
function bb_book_to_array(int $post_id): array
{
    $pages = get_post_meta($post_id, 'pages', true);
    return array(
        'google_id'   => get_post_meta($post_id, 'google_id', true),
        'title'       => get_the_title($post_id),
        'authors'     => get_post_meta($post_id, 'authors', true) ?: array(),
        'publisher'   => get_post_meta($post_id, 'publisher', true) ?: null,
        'description' => get_post_field('post_content', $post_id) ?: null,
        'thumbnail'   => get_post_meta($post_id, 'thumbnail', true) ?: null,
        'language'    => get_post_meta($post_id, 'language', true) ?: null,
        'pages'       => $pages !== '' ? (int) $pages : null,
        'categories'  => get_post_meta($post_id, 'categories', true) ?: array(),
        'rating'      => get_post_meta($post_id, 'rating', true) ?: null,
        'preview_link' => get_post_meta($post_id, 'preview_link', true) ?: null,
        'pdf_link'     => get_post_meta($post_id, 'pdf_link', true) ?: null,
    );
}
