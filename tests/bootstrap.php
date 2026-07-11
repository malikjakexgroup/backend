<?php
// Test bootstrap: load the plugin's pure (WordPress-independent) functions.
define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../includes/google-books.php';
require_once __DIR__ . '/../includes/cache.php';
