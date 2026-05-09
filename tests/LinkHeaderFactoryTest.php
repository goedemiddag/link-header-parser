<?php

namespace Goedemiddag\LinkHeaderParser\Tests;

use Goedemiddag\LinkHeaderParser\LinkHeaderFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LinkHeaderFactoryTest extends TestCase
{
    public function test_parses_single_link(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>; rel="next"');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertSame('https://example.com/page/2', $link->uri);
        $this->assertSame('next', $link->rel);
    }

    public function test_parses_multiple_links(): void
    {
        $header = LinkHeaderFactory::fromHeader(
            '<https://example.com/page/2>; rel="next", <https://example.com/page/1>; rel="prev"'
        );

        $this->assertNotNull($header->getLink('next'));
        $this->assertNotNull($header->getLink('prev'));
        $this->assertSame('https://example.com/page/2', $header->getLink('next')->uri);
        $this->assertSame('https://example.com/page/1', $header->getLink('prev')->uri);
    }

    public function test_strips_link_header_prefix(): void
    {
        $header = LinkHeaderFactory::fromHeader('Link: <https://example.com/page/2>; rel="next"');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_strips_link_header_prefix_case_insensitive(): void
    {
        $header = LinkHeaderFactory::fromHeader('LINK: <https://example.com/page/2>; rel="next"');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_parses_additional_attributes(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/feed>; rel="alternate"; type="application/rss+xml"; title="RSS"');

        $link = $header->getLink('alternate');
        $this->assertNotNull($link);
        $this->assertSame('application/rss+xml', $link->getAttribute('type'));
        $this->assertSame('RSS', $link->getAttribute('title'));
    }

    public function test_attribute_names_are_lowercased(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/feed>; rel="alternate"; Type="application/rss+xml"');

        $link = $header->getLink('alternate');
        $this->assertNotNull($link);
        $this->assertSame('application/rss+xml', $link->getAttribute('type'));
        $this->assertNull($link->getAttribute('Type'));
    }

    public function test_ignores_segment_without_rel(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>; type="text/html"');

        $this->assertNull($header->getLink('next'));
    }

    public function test_ignores_malformed_uri_without_angle_brackets(): void
    {
        $header = LinkHeaderFactory::fromHeader('https://example.com/page/2; rel="next"');

        $this->assertNull($header->getLink('next'));
    }

    public function test_ignores_segment_with_only_uri(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>');

        $this->assertNull($header->getLink('next'));
    }

    public function test_handles_quoted_comma_in_attribute_value(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; title="Page, 2"');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertSame('Page, 2', $link->getAttribute('title'));
    }

    public function test_handles_escaped_quote_in_attribute_value(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; title="Say \\"hello\\""');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertSame('Say "hello"', $link->getAttribute('title'));
    }

    public function test_returns_empty_link_header_for_empty_string(): void
    {
        $header = LinkHeaderFactory::fromHeader('');

        $this->assertNull($header->getLink('next'));
    }

    public function test_later_duplicate_rel_overwrites_earlier(): void
    {
        $header = LinkHeaderFactory::fromHeader(
            '<https://example.com/a>; rel="next", <https://example.com/b>; rel="next"'
        );

        $this->assertSame('https://example.com/b', $header->getLink('next')->uri);
    }

    public function test_get_link_returns_null_for_unknown_rel(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>; rel="next"');

        $this->assertNull($header->getLink('prev'));
    }

    public function test_has_attribute_and_get_attribute_on_link(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; hreflang="en"');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertTrue($link->hasAttribute('hreflang'));
        $this->assertFalse($link->hasAttribute('type'));
        $this->assertSame('en', $link->getAttribute('hreflang'));
        $this->assertNull($link->getAttribute('type'));
    }

    public function test_rel_without_quotes_is_accepted(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>; rel=next');

        $this->assertNotNull($header->getLink('next'));
    }

    #[DataProvider('whitespaceVariantsProvider')]
    public function test_tolerates_extra_whitespace(string $input): void
    {
        $header = LinkHeaderFactory::fromHeader($input);

        $this->assertNotNull($header->getLink('next'));
    }

    public static function whitespaceVariantsProvider(): array
    {
        return [
            'spaces around semicolons' => ['<https://example.com> ; rel="next"'],
            'spaces around commas'     => [' <https://example.com/a> ; rel="prev" , <https://example.com/b> ; rel="next" '],
            'leading/trailing spaces'  => ['  <https://example.com>; rel="next"  '],
        ];
    }

    public function test_ignores_uri_missing_closing_angle_bracket(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2; rel="next"');

        $this->assertNull($header->getLink('next'));
    }

    public function test_ignores_uri_missing_opening_angle_bracket(): void
    {
        $header = LinkHeaderFactory::fromHeader('https://example.com/page/2>; rel="next"');

        $this->assertNull($header->getLink('next'));
    }

    public function test_attribute_value_containing_equals_sign(): void
    {
        // URL query strings contain `=`; the second `=` must not split the value.
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; callback="https://api.example.com/?page=2&sort=asc"');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertSame('https://api.example.com/?page=2&sort=asc', $link->getAttribute('callback'));
    }

    public function test_skips_attribute_segment_without_equals_sign(): void
    {
        // A bare token with no `=` (e.g. an extension flag) must not cause a parse error.
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; nocache');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertFalse($link->hasAttribute('nocache'));
    }

    public function test_skips_empty_segments_from_consecutive_semicolons(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com>;; rel="next";;');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_skips_attribute_with_empty_name(): void
    {
        // A segment starting with `=` produces an empty name after splitting.
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; ="orphan"');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertSame([], $link->attributes);
    }

    public function test_rel_param_name_is_case_insensitive(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>; Rel="next"');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_rel_value_is_case_insensitive(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com/page/2>; rel="Next"');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_link_prefix_is_stripped_with_mixed_casing(): void
    {
        $header = LinkHeaderFactory::fromHeader('lInK: <https://example.com/page/2>; rel="next"');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_accepts_relative_uri(): void
    {
        $header = LinkHeaderFactory::fromHeader('</api/page/2>; rel="next"');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertSame('/api/page/2', $link->uri);
    }

    public function test_valid_links_are_parsed_when_mixed_with_invalid_ones(): void
    {
        $header = LinkHeaderFactory::fromHeader('garbage, <https://example.com>; rel="next", also-garbage');

        $this->assertNotNull($header->getLink('next'));
    }

    public function test_whitespace_only_input_returns_empty_header(): void
    {
        $header = LinkHeaderFactory::fromHeader('   ');

        $this->assertNull($header->getLink('next'));
    }

    public function test_real_world_github_pagination_header(): void
    {
        $raw = '<https://api.github.com/repos/octocat/hello/issues?page=2>; rel="next", '
             . '<https://api.github.com/repos/octocat/hello/issues?page=5>; rel="last", '
             . '<https://api.github.com/repos/octocat/hello/issues?page=1>; rel="first", '
             . '<https://api.github.com/repos/octocat/hello/issues?page=1>; rel="prev"';

        $header = LinkHeaderFactory::fromHeader($raw);

        $this->assertSame('https://api.github.com/repos/octocat/hello/issues?page=2', $header->getLink('next')->uri);
        $this->assertSame('https://api.github.com/repos/octocat/hello/issues?page=5', $header->getLink('last')->uri);
        $this->assertSame('https://api.github.com/repos/octocat/hello/issues?page=1', $header->getLink('first')->uri);
        $this->assertSame('https://api.github.com/repos/octocat/hello/issues?page=1', $header->getLink('prev')->uri);
    }

    public function test_attribute_with_empty_string_value(): void
    {
        $header = LinkHeaderFactory::fromHeader('<https://example.com>; rel="next"; title=""');

        $link = $header->getLink('next');
        $this->assertNotNull($link);
        $this->assertTrue($link->hasAttribute('title'));
        $this->assertSame('', $link->getAttribute('title'));
    }
}
