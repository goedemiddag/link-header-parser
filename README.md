# link-header-parser

A PHP package for parsing the HTTP `Link` header ([RFC 8288](https://www.rfc-editor.org/rfc/rfc8288)).

## Requirements

This package requires PHP 8.3+.

## Installation

You can install the package via composer:

```bash
composer require goedemiddag/link-header-parser
```

## Usage

Parse the link header with the `LinkHeaderFactory`, which will return a `LinkHeader` object.

```php
use Goedemiddag\LinkHeaderParser\LinkHeaderFactory;

$header = 'Link: <https://api.example.com/items?page=2>; rel="next", <https://api.example.com/items?page=5>; rel="last"';

$linkHeader = LinkHeaderFactory::fromHeader($header);

$next = $linkHeader->getLink('next'); // Link object
$last = $linkHeader->getLink('last'); // Link object
$previous = $linkHeader->getLink('previous'); // null

echo $next->uri; // https://api.example.com/items?page=2
```

### Retrieving links by relation type

Use `getLink($rel)` to retrieve a `Link` object by its relation type. If the relation type is not found, it returns `null`.

```php
$next = $linkHeader->getLink('next'); // Link object, or null
```

Note: 
- `rel="Next"` and `rel="next"` both resolve via `getLink('next')`.
- When the same `rel` appears more than once, the last entry wins.

### Accessing link attributes

Each `Link` contains the URI, the rel and any optional extra parameters such as `type`, `title`, or `hreflang`.

```php
$linkHeader = LinkHeaderFactory::fromHeader('<https://example.com/feed>; rel="alternate"; type="application/rss+xml"; title="RSS"');

$link = $linkHeader->getLink('alternate');

echo $link->uri; // https://example.com/feed
echo $link->rel; // alternate
echo $link->getAttribute('type'); // application/rss+xml
echo $link->getAttribute('title'); // RSS
echo $link->getAttribute('missing'); // null
```

There is also a `hasAttribute()` helper to check if the `Link` has an attribute:

```php

$link->hasAttribute('type'); // true
$link->hasAttribute('missing'); // false
```

Note:
- Bare token parameters (e.g. `; nocache`) have no value and are silently ignored; only `name=value` pairs are stored as attributes.
- Attribute names are normalised to lowercase: `Type="text/html"` is accessible as `getAttribute('type')`.

### Example: GitHub pagination

```php
// $response is a PSR-7 ResponseInterface
$linkHeaderValue = $response->getHeaderLine('Link');

$linkHeader = LinkHeaderFactory::fromHeader($linkHeaderValue);

$next = $linkHeader->getLink('next');
if ($next instanceof Link)
    // fetch $next->uri for the next page
}
```

## Contributing

Found a bug or want to add a new feature? Great! There are also many other ways to make meaningful contributions such
as reviewing outstanding pull requests and writing documentation. Even opening an issue for a bug you found is
appreciated.

When you create a pull request, make sure it is tested, following the code standard (run `composer code-style:fix` to
take care of that for you) and please create one pull request per feature. In exchange, you will be credited as
contributor.

### Testing

To run the tests, you can use the following command:

```bash
composer test
```

### Security

If you discover any security related issues in this or other packages of Goedemiddag, please email dev@goedemiddag.nl
instead of using the issue tracker.

# About Goedemiddag

[Goedemiddag!](https://www.goedemiddag.nl) is a digital web-agency based in Delft, the Netherlands. We are a team of
professionals who are passionate about the craft of building digital solutions that make a difference for its users.
See our [GitHub organisation](https://github.com/goedemiddag) for more package.
