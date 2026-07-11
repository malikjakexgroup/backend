<?php

if (!defined('ABSPATH')) {
    exit;
}

// Transients ARE the cache (Redis-backed automatically if an object cache is installed).

function bb_cache_key(string $kind, string $val): string
{
    return 'bb_' . $kind . '_' . md5(strtolower(trim($val)));
}

/** Returns the cached value, or null on a miss. */
function bb_cache_get(string $key)
{
    $value = get_transient($key);
    return $value === false ? null : $value;
}

function bb_cache_set(string $key, $value): void
{
    set_transient($key, $value, BB_CACHE_TTL);
}
