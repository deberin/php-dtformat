# php-dtformat

Detect, parse, and convert date/time strings across **21+ formats** — from a single input string.

Paste an unknown date string, get back the detected format, a parsed Carbon object, and conversions to every supported representation.

## Supported Formats

| Format | Example |
|---|---|
| ISO 8601 | `2025-12-28T19:06:45Z` |
| Unix Timestamp (s/ms) | `1735408005`, `1735408005000` |
| RFC 3339 | `2025-12-28T19:06:45+03:00` |
| RFC 2822 | `Mon, 28 Dec 2025 19:06:45 +0300` |
| ISO 9075 / SQL | `2025-12-28 19:06:45` |
| Excel Serial Date | `45389.5` |
| Windows Filetime | `133312896000000000` |
| Julian Date | `2460400.5` |
| Tire DOT Code | `2525` |
| Mac HFS+ Timestamp | `3823414018` |
| Human-Readable Text | `January 16th 2026, 11:40 pm` |
| Chinese Date | `2025年3月17日` |
| .NET Ticks | `638562392458670000` |
| Local Date (d.m.Y, d/m/Y) | `28.12.2025` |
| US Date (m/d/Y) | `12/28/2025` |
| OLE Automation Date | `45390.75` |
| NTP Timestamp | `3948480000` |
| GPS Time | `2290 345600` |
| MongoDB ObjectId | `674a1b2c3d4e5f6789abcdef` |
| PDF Date | `D:20180921141013-04'00'` |

## Installation

```bash
composer require deberin/php-dtformat
```

**Requirements:** PHP 8.2+, nesbot/carbon 3.x. Optional: `ext-intl` for multi-locale text date parsing.

## Quick Start

```php
use DTFormat\PhpDtformat\DateDetector;

$detector = new DateDetector();

// Detect format(s)
$results = $detector->detect('2025-12-28T19:06:45Z');

foreach ($results as $result) {
    echo $result->formatSlug;   // "iso-8601", "rfc-3339", ...
    echo $result->carbon;       // Carbon instance
    echo $result->mask;         // "date_time_utc" (optional sub-format)
}

// Convert to all representations
$representations = $detector->allRepresentations($results[0]->carbon);

foreach ($representations as $repr) {
    echo $repr['key'];    // "unix_seconds", "rfc3339", "excel_serial", ...
    echo $repr['value'];  // "1735413005", "2025-12-28T19:06:45+00:00", ...
}
```

## Segment Highlighting

Each `ParseResult` includes `segments` — positional data for highlighting parts of the input:

```php
$result = $detector->detect('2025-12-28T19:06:45Z')[0];

foreach ($result->segments as $segment) {
    echo "{$segment->key}: '{$segment->value}' ({$segment->start}–{$segment->end})";
    // year: '2025' (0–4)
    // month: '12' (5–7)
    // ...
}
```

## Custom Parser Set

Use only the parsers you need:

```php
use DTFormat\PhpDtformat\DateDetector;
use DTFormat\PhpDtformat\Parsers\Iso8601Parser;
use DTFormat\PhpDtformat\Parsers\UnixTimestampParser;

$detector = new DateDetector(
    parsers: [
        'iso-8601' => new Iso8601Parser(),
        'unix-timestamp' => new UnixTimestampParser(),
    ]
);
```

## Custom Formatter Set

```php
use DTFormat\PhpDtformat\DateDetector;
use DTFormat\PhpDtformat\Formatters\Iso8601Formatter;
use DTFormat\PhpDtformat\Formatters\UnixSecondsFormatter;

$detector = new DateDetector(
    formatters: [
        'iso8601' => new Iso8601Formatter(),
        'unix_seconds' => new UnixSecondsFormatter(),
    ]
);
```

## Writing a Custom Parser

Implement `ParserInterface`:

```php
use DTFormat\PhpDtformat\Parsers\ParserInterface;
use DTFormat\PhpDtformat\ParseResult;

class MyCustomParser implements ParserInterface
{
    public function parse(string $input): ?ParseResult
    {
        // Return null if input doesn't match
        // Return ParseResult with Carbon instance if it does
    }
}
```

## Testing

```bash
composer test
# or
vendor/bin/phpunit
```

## License

MIT
