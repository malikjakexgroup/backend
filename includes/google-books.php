<?php

if (!defined('ABSPATH')) {
    exit;
}

const BB_GOOGLE_BASE = 'https://www.googleapis.com/books/v1/volumes';

function bb_google_url($path, array $args): string
{
    if (defined('BB_GOOGLE_API_KEY') && BB_GOOGLE_API_KEY) {
        $args['key'] = BB_GOOGLE_API_KEY;
    }
    return BB_GOOGLE_BASE . $path . '?' . http_build_query($args);
}

/** Map a Google Books `volume` item onto our stored fields. */
function bb_normalize(array $item): array
{
    $vi     = $item['volumeInfo'] ?? array();
    $img    = $vi['imageLinks'] ?? array();
    $access = $item['accessInfo'] ?? array();
    $pdf    = $access['pdf'] ?? array();
    return array(
        'google_id'   => $item['id'],
        'title'       => $vi['title'] ?? null,
        'authors'     => $vi['authors'] ?? array(),
        'publisher'   => $vi['publisher'] ?? null,
        'description' => $vi['description'] ?? null,
        'thumbnail'   => $img['thumbnail'] ?? null,
        'language'    => $vi['language'] ?? null,
        'pages'       => $vi['pageCount'] ?? null,
        'categories'  => $vi['categories'] ?? array(),
        'rating'      => $vi['averageRating'] ?? null,
        // Legal read/download links: preview works for all; pdf only when free.
        'preview_link' => $access['webReaderLink'] ?? ($vi['previewLink'] ?? ($vi['infoLink'] ?? null)),
        'pdf_link'     => (!empty($pdf['isAvailable']) && !empty($pdf['downloadLink']))
            ? $pdf['downloadLink'] : null,
    );
}

/** Returns an array of normalized books, or null if Google is unavailable. */
function bb_google_search(string $q): ?array
{
    $res = wp_remote_get(bb_google_url('', array('q' => $q)), array('timeout' => 10));
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) >= 400) {
        return null;
    }
    $body  = json_decode(wp_remote_retrieve_body($res), true);
    $items = $body['items'] ?? array();
    return array_map('bb_normalize', $items);
}

/** Returns a normalized book, the string 'not_found', or null if Google is unavailable. */
function bb_google_get(string $id)
{
    $res = wp_remote_get(bb_google_url('/' . rawurlencode($id), array()), array('timeout' => 10));
    if (is_wp_error($res)) {
        return null;
    }
    $code = wp_remote_retrieve_response_code($res);
    if ($code === 404) {
        return 'not_found';
    }
    if ($code >= 400) {
        return null;
    }
    return bb_normalize(json_decode(wp_remote_retrieve_body($res), true));
}
