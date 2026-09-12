<?php

namespace DTFormat\PhpDtformat\Tests\Parsers;

use DTFormat\PhpDtformat\Parsers\JsDateStringParser;
use PHPUnit\Framework\TestCase;

class JsDateStringParserTest extends TestCase
{
    private JsDateStringParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new JsDateStringParser;
    }

    public function test_parses_js_date_string(): void
    {
        $result = $this->parser->parse('Tue Dec 01 2026 17:07:18 GMT-0700 (Pacific Daylight Time)');
        $this->assertNotNull($result);
        $this->assertSame('js-date-string', $result->formatSlug);
        $this->assertSame('2026-12-01T17:07:18-07:00', $result->carbon->toIso8601String());
        $this->assertCount(8, $result->segments);
        
        // Assert order (must be sorted by start)
        $prevStart = -1;
        foreach ($result->segments as $segment) {
            $this->assertGreaterThanOrEqual($prevStart, $segment->start);
            $prevStart = $segment->start;
        }

        // Assert expected keys
        $keys = array_column($result->segments, 'key');
        $this->assertEquals(['weekday_abbr', 'month', 'day', 'year', 'hour', 'minute', 'second', 'offset'], $keys);
    }
    
    public function test_parses_js_date_string_with_extra_quotes(): void
    {
        $result = $this->parser->parse("'Tue Dec 01 2026 17:07:18 GMT-0700 (Pacific Daylight Time)'");
        $this->assertNotNull($result);
        $this->assertSame('2026-12-01T17:07:18-07:00', $result->carbon->toIso8601String());
    }

    public function test_returns_null_on_invalid_date(): void
    {
        $this->assertNull($this->parser->parse('not a date'));
    }
}
