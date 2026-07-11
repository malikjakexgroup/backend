<?php
// Standalone check for bb_normalize — no WordPress needed. Run: php bb-selftest.php
define('ABSPATH', __DIR__);
require __DIR__ . '/includes/google-books.php';

$sample = array(
    'id' => 'abc123',
    'volumeInfo' => array(
        'title'        => 'Atomic Habits',
        'authors'      => array('James Clear'),
        'pageCount'    => 320,
        'imageLinks'   => array('thumbnail' => 'http://img/t.jpg'),
        'averageRating' => 4.5,
        'language'     => 'en',
    ),
);
$n = bb_normalize($sample);
assert($n['google_id'] === 'abc123');
assert($n['authors'] === array('James Clear'));
assert($n['thumbnail'] === 'http://img/t.jpg');
assert($n['pages'] === 320);

// Missing volumeInfo must not crash and must yield safe defaults.
$empty = bb_normalize(array('id' => 'x'));
assert($empty['title'] === null);
assert($empty['authors'] === array());

echo "normalize() ok\n";
