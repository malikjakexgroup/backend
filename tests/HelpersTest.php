<?php

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function test_cache_key_is_case_and_space_insensitive(): void
    {
        $this->assertSame(
            bb_cache_key('search', 'Atomic Habits'),
            bb_cache_key('search', '  atomic habits ')
        );
    }

    public function test_cache_key_has_kind_prefix(): void
    {
        $this->assertStringStartsWith('bb_search_', bb_cache_key('search', 'x'));
        $this->assertStringStartsWith('bb_book_', bb_cache_key('book', 'x'));
    }

    public function test_google_url_builds_query_and_base(): void
    {
        $url = bb_google_url('', ['q' => 'harry potter']);
        $this->assertStringStartsWith('https://www.googleapis.com/books/v1/volumes', $url);
        $this->assertStringContainsString('q=harry+potter', $url);
    }
}
