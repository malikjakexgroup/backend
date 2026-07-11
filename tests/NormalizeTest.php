<?php

use PHPUnit\Framework\TestCase;

final class NormalizeTest extends TestCase
{
    public function test_maps_core_fields(): void
    {
        $item = [
            'id' => 'abc123',
            'volumeInfo' => [
                'title'        => 'Atomic Habits',
                'authors'      => ['James Clear'],
                'publisher'    => 'Penguin',
                'pageCount'    => 320,
                'imageLinks'   => ['thumbnail' => 'http://img/t.jpg'],
                'averageRating' => 4.5,
                'language'     => 'en',
                'categories'   => ['Self-Help'],
            ],
        ];

        $n = bb_normalize($item);

        $this->assertSame('abc123', $n['google_id']);
        $this->assertSame('Atomic Habits', $n['title']);
        $this->assertSame(['James Clear'], $n['authors']);
        $this->assertSame('Penguin', $n['publisher']);
        $this->assertSame('http://img/t.jpg', $n['thumbnail']);
        $this->assertSame(320, $n['pages']);
        $this->assertSame(4.5, $n['rating']);
        $this->assertSame(['Self-Help'], $n['categories']);
    }

    public function test_missing_volume_info_is_safe(): void
    {
        $n = bb_normalize(['id' => 'x']);

        $this->assertNull($n['title']);
        $this->assertNull($n['thumbnail']);
        $this->assertSame([], $n['authors']);
        $this->assertSame([], $n['categories']);
    }
}
