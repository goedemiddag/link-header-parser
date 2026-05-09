<?php

namespace Goedemiddag\LinkHeaderParser\Tests;

use Goedemiddag\LinkHeaderParser\Link;
use PHPUnit\Framework\TestCase;

final class LinkTest extends TestCase
{
    public function test_properties_are_accessible(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next', attributes: ['type' => 'text/html']);

        $this->assertSame('https://example.com', $link->uri);
        $this->assertSame('next', $link->rel);
        $this->assertSame(['type' => 'text/html'], $link->attributes);
    }

    public function test_attributes_defaults_to_empty_array(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next');

        $this->assertSame([], $link->attributes);
    }

    public function test_get_attribute_returns_value_for_existing_key(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next', attributes: ['hreflang' => 'en']);

        $this->assertSame('en', $link->getAttribute('hreflang'));
    }

    public function test_get_attribute_returns_null_for_missing_key(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next');

        $this->assertNull($link->getAttribute('type'));
    }

    public function test_has_attribute_returns_true_for_existing_key(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next', attributes: ['type' => 'text/html']);

        $this->assertTrue($link->hasAttribute('type'));
    }

    public function test_has_attribute_returns_false_for_missing_key(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next');

        $this->assertFalse($link->hasAttribute('type'));
    }

    public function test_has_attribute_returns_true_for_attribute_with_empty_string_value(): void
    {
        $link = new Link(uri: 'https://example.com', rel: 'next', attributes: ['title' => '']);

        $this->assertTrue($link->hasAttribute('title'));
        $this->assertSame('', $link->getAttribute('title'));
    }
}
