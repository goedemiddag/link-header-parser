<?php

namespace Goedemiddag\LinkHeaderParser\Tests;

use Goedemiddag\LinkHeaderParser\Link;
use Goedemiddag\LinkHeaderParser\LinkHeader;
use PHPUnit\Framework\TestCase;

final class LinkHeaderTest extends TestCase
{
    public function test_links_property_is_accessible(): void
    {
        $next = new Link(uri: 'https://example.com/2', rel: 'next');
        $prev = new Link(uri: 'https://example.com/1', rel: 'prev');

        $header = new LinkHeader(links: ['next' => $next, 'prev' => $prev]);

        $this->assertSame(['next' => $next, 'prev' => $prev], $header->links);
    }

    public function test_links_defaults_to_empty_array(): void
    {
        $header = new LinkHeader();

        $this->assertSame([], $header->links);
    }

    public function test_get_link_returns_matching_link(): void
    {
        $link = new Link(uri: 'https://example.com/2', rel: 'next');
        $header = new LinkHeader(links: ['next' => $link]);

        $this->assertSame($link, $header->getLink('next'));
    }

    public function test_get_link_returns_null_for_unknown_rel(): void
    {
        $header = new LinkHeader(links: []);

        $this->assertNull($header->getLink('next'));
    }
}
